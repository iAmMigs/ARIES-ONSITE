<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Repository\ApplicantBedRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ApplicantBedRequirement;
use App\Entity\DocumentSetup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ApplicantDeletionService;

#[Route('/diliman-admin')]
class AdminDilimanController extends AbstractController
{
    #[Route('/', name: 'app_admin_diliman_dashboard')]
    public function dashboard(ApplicantBedRepository $repository, SchoolYearRepository $syRepo): Response
    {
        $campus = ApplicantBed::CAMPUS_DILIMAN;
        
        // Basic stats
        $qb = $repository->createQueryBuilder('a')
            ->select('count(a.studentNumber)')
            ->where('a.campus = :campus')
            ->setParameter('campus', $campus);

        $total = (clone $qb)->getQuery()->getSingleScalarResult();
        $today = (clone $qb)->andWhere('a.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))->getQuery()->getSingleScalarResult();
        $week = (clone $qb)->andWhere('a.createdAt >= :week')
            ->setParameter('week', new \DateTime('monday this week'))->getQuery()->getSingleScalarResult();
        $month = (clone $qb)->andWhere('a.createdAt >= :month')
            ->setParameter('month', new \DateTime('first day of this month'))->getQuery()->getSingleScalarResult();

        // Optimized Chart Data
        $sevenDaysAgo = (new \DateTime())->modify('-6 days')->setTime(0, 0);
        $rawChart = $repository->createQueryBuilder('a')
            ->select("SUBSTRING(a.createdAt, 1, 10) as dateStr, count(a.studentNumber) as cnt")
            ->where('a.campus = :campus AND a.createdAt >= :start')
            ->setParameter('campus', $campus)
            ->setParameter('start', $sevenDaysAgo)
            ->groupBy('dateStr')
            ->getQuery()
            ->getResult();

        $chartMap = [];
        foreach ($rawChart as $r) { $chartMap[$r['dateStr']] = (int)$r['cnt']; }

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $ds = $date->format('Y-m-d');
            $chartData[] = ['date' => $date->format('M d'), 'count' => $chartMap[$ds] ?? 0];
        }

        // Optimized Summary Data
        $rawSummary = $repository->createQueryBuilder('a')
            ->select('a.admissionType, a.gradeLevel, a.trackStrand, count(a.studentNumber) as cnt')
            ->where('a.campus = :campus')
            ->setParameter('campus', $campus)
            ->groupBy('a.admissionType, a.gradeLevel, a.trackStrand')
            ->getQuery()
            ->getResult();

        $activeSY = $syRepo->findActiveByCampus($campus);
        $summary = [
            'rows' => [],
            'total' => ['new' => 0, 'transferee' => 0, 'current' => 0],
            'grs' => [],
            'jhs' => [],
            'shs' => []
        ];

        $categories = [
            'K to 10' => ['levels' => ['Kinder', 'kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10']],
            'Senior HS' => ['levels' => ['Grade 11', 'Grade 12']]
        ];

        foreach ($categories as $catName => $config) {
            $catNew = 0; $catTrans = 0;
            $targetLevels = $config['levels'] ?? [];
            
            foreach ($rawSummary as $rs) {
                // Case-insensitive and underscore-tolerant matching
                $normalizedLevel = str_replace('_', ' ', strtolower($rs['gradeLevel'] ?? ''));
                $isMatch = false;
                foreach ($targetLevels as $tl) {
                    if (strtolower($tl) === $normalizedLevel) {
                        $isMatch = true;
                        break;
                    }
                }

                if ($isMatch) {
                    if (in_array($rs['admissionType'], ['New Student', 'Freshman'])) $catNew += $rs['cnt'];
                    elseif ($rs['admissionType'] === 'Transferee') $catTrans += $rs['cnt'];
                }
            }
            $summary['rows'][] = [
                'name' => "Diliman - $catName",
                'new' => $catNew,
                'transferee' => $catTrans,
                'current' => $catNew + $catTrans
            ];
            $summary['total']['new'] += $catNew;
            $summary['total']['transferee'] += $catTrans;
            $summary['total']['current'] += ($catNew + $catTrans);
        }

        // Breakdown Logic
        $grsLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        $jhsLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
        $strands = ['STEM', 'ABM', 'HUMSS', 'GAS', 'Sports Track'];

        foreach ($grsLevels as $l) {
            $lNew = 0; $lTrans = 0;
            foreach ($rawSummary as $rs) {
                if (strcasecmp($rs['gradeLevel'], $l) === 0) {
                    if (in_array($rs['admissionType'], ['New Student', 'Freshman'])) $lNew += $rs['cnt'];
                    elseif ($rs['admissionType'] === 'Transferee') $lTrans += $rs['cnt'];
                }
            }
            $summary['grs'][] = ['name' => $l, 'new' => $lNew, 'transferee' => $lTrans, 'total' => $lNew + $lTrans];
        }

        foreach ($jhsLevels as $l) {
            $lNew = 0; $lTrans = 0;
            foreach ($rawSummary as $rs) {
                if (strcasecmp($rs['gradeLevel'], $l) === 0) {
                    if (in_array($rs['admissionType'], ['New Student', 'Freshman'])) $lNew += $rs['cnt'];
                    elseif ($rs['admissionType'] === 'Transferee') $lTrans += $rs['cnt'];
                }
            }
            $summary['jhs'][] = ['name' => $l, 'new' => $lNew, 'transferee' => $lTrans, 'total' => $lNew + $lTrans];
        }

        foreach ($strands as $s) {
            $sNew = 0; $sTrans = 0;
            foreach ($rawSummary as $rs) {
                if ($rs['trackStrand'] === $s) {
                    if (in_array($rs['admissionType'], ['New Student', 'Freshman'])) $sNew += $rs['cnt'];
                    elseif ($rs['admissionType'] === 'Transferee') $sTrans += $rs['cnt'];
                }
            }
            $summary['shs'][] = ['name' => $s, 'new' => $sNew, 'transferee' => $sTrans, 'total' => $sNew + $sTrans];
        }

        // Discovery Stats (Marketing Source)
        $rawDiscovery = $repository->createQueryBuilder('a')
            ->select('a.marketingSource as source, count(a.studentNumber) as cnt')
            ->where('a.campus = :campus')
            ->setParameter('campus', $campus)
            ->groupBy('a.marketingSource')
            ->getQuery()
            ->getResult();

        $sources = [
            'Brochures/Flyers', 'Banner', 'Campus Tour', 'Events', 'Social Media',
            'Google/Website', 'Immersion', 'Phone Inquiry', 'Poster',
            'Friends', 'Family/Relatives',
            'School Visit/Career Talk', 'Visibility: Signage/Billboard', 'Walk-in', 'Other'
        ];

        $discoveryMap = [];
        foreach ($sources as $s) { $discoveryMap[$s] = 0; }
        
        foreach ($rawDiscovery as $rd) {
            $srcStr = trim((string)($rd['source'] ?? ''));
            if ($srcStr === '') continue;
            $cnt = (int)$rd['cnt'];

            $individualSources = array_map('trim', explode(',', $srcStr));
            foreach ($individualSources as $singleSrc) {
                if ($singleSrc === '') continue;
                if (isset($discoveryMap[$singleSrc])) {
                    $discoveryMap[$singleSrc] += $cnt;
                } else {
                    $discoveryMap['Other'] += $cnt;
                }
            }
        }

        $discoveryStats = [];
        foreach ($discoveryMap as $source => $cnt) {
            $discoveryStats[] = ['source' => $source, 'cnt' => $cnt];
        }

        // Sort discovery stats by count DESC
        usort($discoveryStats, function($a, $b) { return $b['cnt'] <=> $a['cnt']; });

        return $this->render('admin-onsite/diliman/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData,
            'summary' => $summary,
            'activeSY' => $activeSY,
            'discoveryStats' => $discoveryStats
        ]);
    }

    #[Route('/registrations', name: 'app_admin_diliman_registrations')]
    public function registrations(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
            ->orderBy('a.createdAt', 'DESC');

        if ($search = $request->query->get('search')) {
            $qb->andWhere('a.firstName LIKE :search OR a.lastName LIKE :search OR a.studentNumber LIKE :search')
               ->setParameter('search', "%$search%");
        }

        if ($eduType = $request->query->get('education_type')) {
            if ($eduType === 'Primary') {
                $primaryGrades = [
                    'kinder', 'Kinder', 'grade_1', 'Grade 1', 'grade_2', 'Grade 2',
                    'grade_3', 'Grade 3', 'grade_4', 'Grade 4', 'grade_5', 'Grade 5',
                    'grade_6', 'Grade 6'
                ];
                $qb->andWhere("a.educationType IN ('Kinder', 'Grade School', 'K-10', 'Primary') OR a.gradeLevel IN (:primaryGrades)")
                   ->setParameter('primaryGrades', $primaryGrades);
            } elseif ($eduType === 'Secondary') {
                $secondaryGrades = [
                    'grade_7', 'Grade 7', 'grade_8', 'Grade 8', 'grade_9', 'Grade 9',
                    'grade_10', 'Grade 10', 'grade_11', 'Grade 11', 'grade_12', 'Grade 12'
                ];
                $qb->andWhere("a.educationType IN ('Junior High School', 'Senior High School', 'SHS', 'Secondary') OR a.gradeLevel IN (:secondaryGrades)")
                   ->setParameter('secondaryGrades', $secondaryGrades);
            } else {
                $qb->andWhere('a.educationType = :eduType')
                   ->setParameter('eduType', $eduType);
            }
        }

        $grades = array_filter($request->query->all()['grade_levels'] ?? []);
        if (!empty($grades)) {
            $mappedGrades = [];
            foreach ($grades as $g) {
                $mappedGrades[] = $g;
                $mappedGrades[] = strtolower($g);
                $mappedGrades[] = str_replace('_', ' ', $g);
                $mappedGrades[] = ucwords(str_replace('_', ' ', $g));
                $mappedGrades[] = str_replace(' ', '_', strtolower($g));
            }
            $mappedGrades = array_values(array_unique($mappedGrades));
            $qb->andWhere('a.gradeLevel IN (:grades)')
               ->setParameter('grades', $mappedGrades);
        }

        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')
               ->setParameter('date', "$date%");
        }

        if ($promissory = $request->query->get('promissory')) {
            if ($promissory === 'complied') {
                $qb->andWhere('a.documentsAgreedDate IS NULL');
            } elseif ($promissory === 'not_complied') {
                $qb->andWhere('a.documentsAgreedDate IS NOT NULL');
            }
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $limit);

        return $this->render('admin-onsite/diliman/registrations.html.twig', [
            'registrations' => $paginator,
            'filters' => $request->query->all(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems
        ]);
    }

    #[Route('/registrations/export', name: 'app_admin_diliman_registrations_export')]
    public function export(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->leftJoin('a.guardians', 'g')->addSelect('g')
            ->leftJoin('a.schools', 's')->addSelect('s')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
            ->orderBy('a.createdAt', 'DESC');

        if ($search = $request->query->get('search')) {
            $qb->andWhere('a.firstName LIKE :search OR a.lastName LIKE :search OR a.studentNumber LIKE :search')
               ->setParameter('search', "%$search%");
        }

        if ($eduType = $request->query->get('education_type')) {
            if ($eduType === 'Primary') {
                $primaryGrades = [
                    'kinder', 'Kinder', 'grade_1', 'Grade 1', 'grade_2', 'Grade 2',
                    'grade_3', 'Grade 3', 'grade_4', 'Grade 4', 'grade_5', 'Grade 5',
                    'grade_6', 'Grade 6'
                ];
                $qb->andWhere("a.educationType IN ('Kinder', 'Grade School', 'K-10', 'Primary') OR a.gradeLevel IN (:primaryGrades)")
                   ->setParameter('primaryGrades', $primaryGrades);
            } elseif ($eduType === 'Secondary') {
                $secondaryGrades = [
                    'grade_7', 'Grade 7', 'grade_8', 'Grade 8', 'grade_9', 'Grade 9',
                    'grade_10', 'Grade 10', 'grade_11', 'Grade 11', 'grade_12', 'Grade 12'
                ];
                $qb->andWhere("a.educationType IN ('Junior High School', 'Senior High School', 'SHS', 'Secondary') OR a.gradeLevel IN (:secondaryGrades)")
                   ->setParameter('secondaryGrades', $secondaryGrades);
            } else {
                $qb->andWhere('a.educationType = :eduType')
                   ->setParameter('eduType', $eduType);
            }
        }

        $grades = array_filter($request->query->all()['grade_levels'] ?? []);
        if (!empty($grades)) {
            $mappedGrades = [];
            foreach ($grades as $g) {
                $mappedGrades[] = $g;
                $mappedGrades[] = strtolower($g);
                $mappedGrades[] = str_replace('_', ' ', $g);
                $mappedGrades[] = ucwords(str_replace('_', ' ', $g));
                $mappedGrades[] = str_replace(' ', '_', strtolower($g));
            }
            $mappedGrades = array_values(array_unique($mappedGrades));
            $qb->andWhere('a.gradeLevel IN (:grades)')
               ->setParameter('grades', $mappedGrades);
        }

        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')
               ->setParameter('date', "$date%");
        }

        if ($promissory = $request->query->get('promissory')) {
            if ($promissory === 'complied') {
                $qb->andWhere('a.documentsAgreedDate IS NULL');
            } elseif ($promissory === 'not_complied') {
                $qb->andWhere('a.documentsAgreedDate IS NOT NULL');
            }
        }

        $results = $qb->getQuery()->getResult();

        $handle = fopen('php://temp', 'r+');
        
        // UTF-8 BOM so Excel opens with proper UTF-8 encoding
        fputs($handle, "\xEF\xBB\xBF");

        // Comprehensive CSV Headers
            fputcsv($handle, [
                'Student Number',
                'Campus',
                'School Year',
                'Admission Status',
                'Admission Type',
                'Education Level',
                'Grade Level',
                'Track/Strand',
                'LRN',
                'Exam Score',
                'Exam Date',
                'Last Name',
                'First Name',
                'Middle Name',
                'Extension/Suffix',
                'Preferred Name',
                'Full Name',
                'Gender',
                'Birthdate',
                'Age',
                'Birthplace',
                'Country of Birth',
                'Civil Status',
                'Religion',
                'Nationality',
                'Citizenship',
                'Indigenous Group',
                'Passport Number',
                'Visa Type',
                'Visa Status',
                'Personal Email',
                'Mobile Number',
                'Landline Number',
                'Current Address',
                'Current Barangay',
                'Current City',
                'Current Province',
                'Current Region',
                'Current Zip Code',
                'Country of Residence',
                'Permanent Address',
                'Permanent Barangay',
                'Permanent City',
                'Permanent Province',
                'Permanent Region',
                'Permanent Zip Code',
                'Permanent Country',
                'Last School Attended',
                'School Type',
                'Last Grade Completed',
                'General Average',
                'Father Name',
                'Father Contact',
                'Father Occupation',
                'Father Is Deceased',
                'Father Is OFW',
                'Mother Name',
                'Mother Contact',
                'Mother Occupation',
                'Mother Is Deceased',
                'Mother Is OFW',
                'Guardian Name',
                'Guardian Relationship',
                'Guardian Contact',
                'Guardian Email',
                'Guardian Address',
                'Marketing Source',
                'Documents Agreed',
                'Documents Agreed Date',
                'Date Applied'
            ]);

            foreach ($results as $applicant) {
                // Compute age
                $age = '';
                if ($applicant->getBirthDate()) {
                    $age = (string) $applicant->getBirthDate()->diff(new \DateTime('today'))->y;
                }

                // Format Full Name
                $suffix = $applicant->getSuffix() ?: $applicant->getExtensionName();
                $fullName = trim($applicant->getLastName() . ', ' . $applicant->getFirstName() . ' ' . ($applicant->getMiddleName() ?? '') . ($suffix ? ' ' . $suffix : ''));

                // Extract Guardians
                $fatherName = $fatherContact = $fatherOccupation = $fatherDeceased = $fatherOfw = '';
                $motherName = $motherContact = $motherOccupation = $motherDeceased = $motherOfw = '';
                $guardianName = $guardianRel = $guardianContact = $guardianEmail = $guardianAddress = '';

                foreach ($applicant->getGuardians() as $g) {
                    $rel = strtoupper(trim((string)$g->getRelationship()));
                    if ($rel === 'FATHER') {
                        $fatherName = $g->getParentName() ?? '';
                        $fatherContact = $g->getContactNo() ?? '';
                        $fatherOccupation = $g->getOccupation() ?? '';
                        $fatherDeceased = $g->isDeceased() ? 'Yes' : 'No';
                        $fatherOfw = $g->isOFW() ? 'Yes' : 'No';
                    } elseif ($rel === 'MOTHER') {
                        $motherName = $g->getParentName() ?? '';
                        $motherContact = $g->getContactNo() ?? '';
                        $motherOccupation = $g->getOccupation() ?? '';
                        $motherDeceased = $g->isDeceased() ? 'Yes' : 'No';
                        $motherOfw = $g->isOFW() ? 'Yes' : 'No';
                    } elseif ($rel === 'GUARDIAN' || empty($guardianName)) {
                        $guardianName = $g->getParentName() ?? '';
                        $guardianRel = $g->getRelationship() ?? '';
                        $guardianContact = $g->getContactNo() ?? '';
                        $guardianEmail = $g->getEmail() ?? '';
                        $guardianAddress = $g->getAddress() ?? '';
                    }
                }

                // Extract Previous School
                $lastSchoolName = '';
                $schoolType = $applicant->getSchoolType() ?? '';
                foreach ($applicant->getSchools() as $sch) {
                    if ($sch->getSchool()) {
                        $lastSchoolName = $sch->getSchool();
                        if ($sch->getSchoolType()) $schoolType = $sch->getSchoolType();
                        break;
                    }
                }

                $genderLabel = match($applicant->getGender()) {
                    'M', 'Male' => 'Male',
                    'F', 'Female' => 'Female',
                    default => $applicant->getGender() ?? ''
                };

                fputcsv($handle, [
                    $applicant->getStudentNumber(),
                    $applicant->getCampus() === ApplicantBed::CAMPUS_ALABANG ? 'FEU Alabang' : 'FEU Diliman',
                    $applicant->getSchoolYearOfEntry() ?? '',
                    $applicant->getAdmissionStatus() ?? '',
                    $applicant->getAdmissionType() ?? '',
                    $applicant->getEducationType() ?? '',
                    $applicant->getGradeLevel() ? str_replace('_', ' ', $applicant->getGradeLevel()) : '',
                    $applicant->getTrackStrand() ?? '',
                    $applicant->getLrn() ?? '',
                    $applicant->getExaminationScore() !== null ? (string)$applicant->getExaminationScore() : '',
                    $applicant->getExaminationDate() ? $applicant->getExaminationDate()->format('Y-m-d') : '',
                    $applicant->getLastName() ?? '',
                    $applicant->getFirstName() ?? '',
                    $applicant->getMiddleName() ?? '',
                    $suffix ?? '',
                    $applicant->getPreferredName() ?? '',
                    $fullName,
                    $genderLabel,
                    $applicant->getBirthDate() ? $applicant->getBirthDate()->format('Y-m-d') : '',
                    $age,
                    $applicant->getBirthPlace() ?? '',
                    $applicant->getCountryOfBirth() ?? '',
                    $applicant->getCivilStatus() ?? '',
                    $applicant->getReligion() ?? '',
                    $applicant->getNationality() ?? '',
                    $applicant->getCitizenship() ?? '',
                    $applicant->getIndigenousGroup() ?? '',
                    $applicant->getPassportNumber() ?? '',
                    $applicant->getVisaType() ?? '',
                    $applicant->getVisaStatus() ?? '',
                    $applicant->getPersonalEmail() ?? '',
                    $applicant->getMobileNumber() ?? '',
                    $applicant->getLandLineNumber() ?? '',
                    $applicant->getCurrentAddress() ?? '',
                    $applicant->getCurrentBarangay() ?? '',
                    $applicant->getCurrentCity() ?? '',
                    $applicant->getCurrentProvince() ?? '',
                    $applicant->getCurrentRegion() ?? '',
                    $applicant->getCurrentZip() ?? '',
                    $applicant->getCountryOfResidence() ?? '',
                    $applicant->getPermanentAddress() ?? '',
                    $applicant->getPermanentBarangay() ?? '',
                    $applicant->getPermanentCity() ?? '',
                    $applicant->getPermanentProvince() ?? '',
                    $applicant->getPermanentRegion() ?? '',
                    $applicant->getPermanentZip() ?? '',
                    $applicant->getPermanentCountry() ?? '',
                    $lastSchoolName,
                    $schoolType,
                    $applicant->getLastGradeCompleted() ?? '',
                    $applicant->getGeneralAverage() ?? '',
                    $fatherName,
                    $fatherContact,
                    $fatherOccupation,
                    $fatherDeceased,
                    $fatherOfw,
                    $motherName,
                    $motherContact,
                    $motherOccupation,
                    $motherDeceased,
                    $motherOfw,
                    $guardianName,
                    $guardianRel,
                    $guardianContact,
                    $guardianEmail,
                    $guardianAddress,
                    $applicant->getMarketingSource() ?? '',
                    $applicant->isDocumentsAgreed() ? 'Yes' : 'No',
                    $applicant->getDocumentsAgreedDate() ? $applicant->getDocumentsAgreedDate()->format('Y-m-d') : '',
                    $applicant->getCreatedAt() ? $applicant->getCreatedAt()->format('Y-m-d H:i:s') : ''
                ]);
            }
            rewind($handle);
            $csvContent = (string)stream_get_contents($handle);
            fclose($handle);

            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="diliman_applicants_export.csv"; filename*=UTF-8\'\'diliman_applicants_export.csv');
            $response->headers->set('Content-Length', (string)strlen($csvContent));
            $response->headers->set('Cache-Control', 'max-age=0, must-revalidate, private');
            $response->headers->set('Pragma', 'public');

            return $response;
    }

    #[Route('/registration/{id}/export-pdf', name: 'app_admin_diliman_registration_pdf')]
    public function exportPdf(string $id, ApplicantBedRepository $repository, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration || $registration->getCampus() !== ApplicantBed::CAMPUS_DILIMAN) {
            throw $this->createNotFoundException('Applicant registration record not found.');
        }

        $documentSetups = $this->getFilteredDocumentSetups($registration, $em, ApplicantBed::CAMPUS_DILIMAN);
        
        $uploadedDocsMap = [];
        foreach ($registration->getRequirements() as $req) {
            if (!$req->isDeleted() && $req->getStoredFileName() && $req->getSlug()) {
                $slugLower = strtolower(trim((string)$req->getSlug()));
                $uploadedDocsMap[$slugLower] = true;
                $uploadedDocsMap[$req->getSlug()] = true;
                foreach ($documentSetups as $setup) {
                    if (strtolower(trim((string)$setup->getSlug())) === $slugLower) {
                        $uploadedDocsMap[$setup->getId()] = true;
                    }
                }
            }
        }

        // Calculate age
        $age = null;
        if ($registration->getBirthDate()) {
            $age = $registration->getBirthDate()->diff(new \DateTime('today'))->y;
        }

        // Extract Guardians
        $father = null;
        $mother = null;
        $guardian = null;
        foreach ($registration->getGuardians() as $g) {
            $rel = strtoupper(trim((string)$g->getRelationship()));
            if ($rel === 'FATHER') $father = $g;
            elseif ($rel === 'MOTHER') $mother = $g;
            elseif ($rel === 'GUARDIAN' || $guardian === null) $guardian = $g;
        }

        // Extract previous school
        $lastSchoolName = '';
        foreach ($registration->getSchools() as $sch) {
            if ($sch->getSchool()) {
                $lastSchoolName = $sch->getSchool();
                break;
            }
        }

        // Photo as base64 for Dompdf offline rendering
        $photoBase64 = null;
        if ($registration->getPhotoSlug()) {
            $publicDir = $this->getParameter('kernel.project_dir') . '/public';
            $photoPath = $publicDir . '/' . ltrim($registration->getPhotoSlug(), '/');
            if (file_exists($photoPath) && is_readable($photoPath)) {
                $type = pathinfo($photoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($photoPath);
                if ($data !== false) {
                    $photoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }
        }

        $activeSY = $em->getRepository(\App\Entity\SchoolYear::class)->findOneBy(['campus' => ApplicantBed::CAMPUS_DILIMAN, 'isActive' => true]);
        $promissoryDeadline = $registration->getDocumentsAgreedDate() ?? ($activeSY ? $activeSY->getPromissoryDeadline() : null);

        $html = $this->renderView('admin-onsite/pdf/student_summary.html.twig', [
            'registration' => $registration,
            'campus' => 'diliman',
            'documentSetups' => $documentSetups,
            'uploadedDocsMap' => $uploadedDocsMap,
            'age' => $age,
            'father' => $father,
            'mother' => $mother,
            'guardian' => $guardian,
            'lastSchoolName' => $lastSchoolName,
            'photoBase64' => $photoBase64,
            'promissoryDeadline' => $promissoryDeadline,
            'activeSY' => $activeSY,
        ]);

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'FEU_Diliman_Registration_%s_%s_%s.pdf',
            $registration->getStudentNumber(),
            preg_replace('/[^A-Za-z0-9]/', '', (string)$registration->getLastName()),
            preg_replace('/[^A-Za-z0-9]/', '', (string)$registration->getFirstName())
        );

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_diliman_registration_view')]
    public function view(string $id, ApplicantBedRepository $repository, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id); 
        if (!$registration || $registration->getCampus() !== ApplicantBed::CAMPUS_DILIMAN) throw $this->createNotFoundException();

        $documentSetups = $this->getFilteredDocumentSetups($registration, $em, ApplicantBed::CAMPUS_DILIMAN);

        return $this->render('admin-onsite/diliman/view_registration.html.twig', [
            'registration' => $registration,
            'documentSetups' => $documentSetups
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_diliman_registration_edit')]
    public function edit(string $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration || $registration->getCampus() !== ApplicantBed::CAMPUS_DILIMAN) throw $this->createNotFoundException();

        $documentSetups = $this->getFilteredDocumentSetups($registration, $em, ApplicantBed::CAMPUS_DILIMAN);
        $nationalities = $em->getRepository(\App\Entity\LookupCitizenship::class)->findBy([], ['citizenshipName' => 'ASC']);
        $countries = $em->getRepository(\App\Entity\LookupCountry::class)->findBy([], ['countryName' => 'ASC']);

        if ($request->isMethod('POST')) {
            $registration->setFirstName($request->request->get('first_name'));
            $registration->setLastName($request->request->get('last_name'));
            $registration->setMiddleName($request->request->get('middle_name'));
            $registration->setAdmissionType($request->request->get('admission_type'));
            $registration->setPersonalEmail($request->request->get('email'));
            $registration->setMobileNumber($request->request->get('mobile'));
            $registration->setGender($request->request->get('gender'));
            $registration->setBirthPlace($request->request->get('birth_place'));
            $registration->setReligion($request->request->get('religion'));
            $registration->setCitizenship($request->request->get('citizenship'));
            $registration->setNationality($request->request->get('nationality'));
            $registration->setPreferredName($request->request->get('preferred_name'));
            $registration->setSuffix($request->request->get('suffix'));
            $registration->setCivilStatus($request->request->get('civil_status'));
            $registration->setCountryOfBirth($request->request->get('country_of_birth'));
            $registration->setCountryOfResidence($request->request->get('country_of_residence'));
            $registration->setLastGradeCompleted($request->request->get('last_grade_completed'));

            $avg = $request->request->get('general_average');
            $registration->setGeneralAverage($avg !== null && trim((string)$avg) !== '' ? trim((string)$avg) : null);

            if (strtoupper($registration->getCitizenship() ?? '') === 'INTERNATIONAL') {
                $passport = $registration->getPassport();
                if (!$passport) {
                    $passport = new \App\Entity\ApplicantBedPassport();
                    $passport->setApplicant($registration);
                    $em->persist($passport);
                    $registration->setPassport($passport);
                }
                $passport->setPassportNumber($request->request->get('passport_number'));
                $passport->setCountryOfIssue($request->request->get('passport_country_issue'));
                
                if ($pdi = $request->request->get('passport_date_issued')) {
                    $passport->setDateIssued(new \DateTime($pdi));
                } else {
                    $passport->setDateIssued(null);
                }
                
                if ($ped = $request->request->get('passport_expiration_date')) {
                    $passport->setExpirationDate(new \DateTime($ped));
                } else {
                    $passport->setExpirationDate(null);
                }
                
                $registration->setVisaType($request->request->get('visa_type'));
                $registration->setVisaStatus($request->request->get('visa_status'));
            } else {
                $registration->setVisaType(null);
                $registration->setVisaStatus(null);
                if ($registration->getPassport()) {
                    $em->remove($registration->getPassport());
                    $registration->setPassport(null);
                }
            }

            $gradeLevel = $request->request->get('grade_level');
            $registration->setGradeLevel($gradeLevel);

            // Determine education type from grade level
            $levelLower = strtolower(str_replace(' ', '_', $gradeLevel));
            if ($levelLower === 'grade_11' || $levelLower === 'grade_12') {
                $registration->setEducationType('SHS');
            } else {
                $registration->setEducationType('K-10');
            }

            $registration->setTrackStrand($request->request->get('track_strand'));
            
            if ($dob = $request->request->get('birth_date')) {
                $registration->setBirthDate(new \DateTime($dob));
            }

            $profileFile = $request->files->get('profile_picture');
            if ($profileFile) {
                if ($profileFile->getSize() > 5242880) {
                    $this->addFlash('error', 'The profile picture exceeds the 5MB limit.');
                    return $this->redirectToRoute('app_admin_diliman_registration_edit', ['id' => $id]);
                }
                $filename = 'ID-' . $registration->getStudentNumber() . '-' . uniqid() . '.' . $profileFile->guessExtension();
                try {
                    $profileFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/onsite-id-pics', $filename);
                    $registration->setPhotoSlug('uploads/onsite-id-pics/' . $filename);
                } catch (\Exception $e) { }
            }
            
            foreach ($documentSetups as $docSetup) {
                $inputName = $docSetup->getSlug();
                $docFile = $request->files->get($inputName);
                
                if ($docFile) {
                    if ($docFile->getSize() > 10485760) {
                        $this->addFlash('error', 'The document ' . $docSetup->getDocumentName() . ' exceeds the 10MB limit.');
                        return $this->redirectToRoute('app_admin_diliman_registration_edit', ['id' => $id]);
                    }
                    $req = $em->getRepository(ApplicantBedRequirement::class)->findOneBy([
                        'applicant' => $registration, 
                        'Slug' => $inputName
                    ]);
                    
                    if (!$req) {
                        $req = new ApplicantBedRequirement();
                        $req->setApplicant($registration);
                        $req->setSlug($inputName);
                        $req->setRequirement($docSetup->getDocumentName());
                        $em->persist($req);
                    }
                    
                    $filename = strtoupper($inputName) . '-' . $registration->getStudentNumber() . '-' . uniqid() . '.pdf';
                    try {
                        $docFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/' . $docSetup->getFolderName(), $filename);
                        $req->setStoredFileName('uploads/' . $docSetup->getFolderName() . '/' . $filename);
                        $req->setIsDeleted(false);
                        $req->setDateSubmitted(new \DateTime());
                        $req->setStatus('S');
                    } catch (\Exception $e) { }
                }
            }

            $examTaken = $request->request->get('exam_taken') === '1';
            $paymentPaid = $request->request->get('payment_paid') === '1';
            $score = $request->request->get('exam_score');
            $examDateStr = $request->request->get('exam_date');

            if ($registration->getEducationType() === 'SHS') {
                if ($examTaken) {
                    if ($score !== null && $score !== '') {
                        $registration->setExaminationScore((float)$score);
                    }
                    if ($examDateStr) {
                        $registration->setExaminationDate(new \DateTime($examDateStr));
                    }
                } else {
                    $registration->setExaminationScore(null);
                    $registration->setExaminationDate(null);
                }

                if ($paymentPaid) {
                    $registration->setAdmissionStatus('Paid');
                } else {
                    $registration->setAdmissionStatus('For Payment');
                }
            } else {
                // K-10
                if ($paymentPaid) {
                    $registration->setAdmissionStatus('Paid');
                } else {
                    $registration->setAdmissionStatus('For Payment');
                }
                $registration->setExaminationScore(null);
                $registration->setExaminationDate(null);
            }

            $this->hydrateAddress($registration, $request, $em, 'current');
            $this->hydrateAddress($registration, $request, $em, 'permanent');

            $guardiansData = $request->request->all('guardians');
            foreach ($registration->getGuardians() as $index => $g) {
                if (isset($guardiansData[$index])) {
                    $data = $guardiansData[$index];
                    
                    $gType = strtoupper($data['guardian_type'] ?? $g->getGuardianType() ?? '');
                    if ($gType === '') {
                        $gType = in_array(strtoupper($g->getRelationship()), ['FATHER', 'MOTHER']) ? strtoupper($g->getRelationship()) : 'GUARDIAN';
                    }
                    $g->setGuardianType($gType);

                    if ($gType === 'GUARDIAN') {
                        $g->setParentName(strtoupper(trim($data['full_name'] ?? '')));
                        if (isset($data['relationship']) && trim($data['relationship']) !== '') {
                            $g->setRelationship(strtoupper(trim($data['relationship'])));
                        }
                    } else {
                        $firstName = trim($data['first_name'] ?? '');
                        $middleName = trim($data['middle_name'] ?? '');
                        $lastName = trim($data['last_name'] ?? '');
                        
                        $firstMid = trim(strtoupper(trim("$firstName $middleName")));
                        $lastName = strtoupper($lastName);

                        if ($lastName && $firstMid) {
                            $g->setParentName("$lastName, $firstMid");
                        } elseif ($lastName) {
                            $g->setParentName($lastName);
                        } elseif ($firstMid) {
                            $g->setParentName($firstMid);
                        } else {
                            $g->setParentName('');
                        }
                    }

                    $g->setOccupation(strtoupper($data['occupation'] ?? ''));
                    $g->setContactNo($data['contact'] ?? '');
                    $g->setDeceased(isset($data['deceased']));
                    $g->setOFW(isset($data['ofw']));
                    $g->setNationality(strtoupper($data['nationality'] ?? 'FILIPINO'));

                    if ($gType === 'GUARDIAN') {
                        $sameAsApplicant = isset($data['same_as_applicant']);
                        $g->setSameAsApplicant($sameAsApplicant);

                        if ($sameAsApplicant) {
                            $g->setCurrentRegion($registration->getCurrentRegion());
                            $g->setCurrentProvince($registration->getCurrentProvince());
                            $g->setCurrentCity($registration->getCurrentCity());
                            $g->setCurrentBarangay($registration->getCurrentBarangay());
                            $g->setCurrentAddress($registration->getCurrentAddress());
                            $g->setCurrentZip($registration->getCurrentZip());
                        } else {
                            $this->hydrateGuardianAddress($g, $data, $em);
                        }
                    } else {
                        if ($g->isDeceased()) {
                            $g->setSameAsApplicant(false);
                            $g->setCurrentRegion(null);
                            $g->setCurrentProvince(null);
                            $g->setCurrentCity(null);
                            $g->setCurrentBarangay(null);
                            $g->setCurrentAddress(null);
                            $g->setCurrentZip(null);
                            $g->setPermanentRegion(null);
                            $g->setPermanentProvince(null);
                            $g->setPermanentCity(null);
                            $g->setPermanentBarangay(null);
                            $g->setPermanentAddress(null);
                            $g->setPermanentZip(null);
                            $g->setPermanentCountry(null);
                        } elseif ($g->isOFW()) {
                            $g->setSameAsApplicant(false);
                            $ofwCountry = $data['ofw_country'] ?? null;
                            $g->setOfwCountry($ofwCountry);
                            $g->setCurrentRegion(null);
                            $g->setCurrentProvince(null);
                            $g->setCurrentCity(null);
                            $g->setCurrentBarangay(null);
                            $g->setCurrentAddress(strtoupper((string)($data['ofw_address'] ?? '')));
                            $g->setCurrentZip((string)($data['ofw_zip'] ?? ''));

                            $ofwPermSame = isset($data['ofw_perm_same']);
                            if ($ofwPermSame) {
                                $g->setPermanentCountry($ofwCountry);
                                $g->setPermanentAddress($g->getCurrentAddress());
                                $g->setPermanentZip($g->getCurrentZip());
                                $g->setPermanentRegion(null);
                                $g->setPermanentProvince(null);
                                $g->setPermanentCity(null);
                                $g->setPermanentBarangay(null);
                            } else {
                                $permInPh = $data['ofw_perm_in_ph'] ?? '';
                                if ($permInPh === 'yes') {
                                    $g->setPermanentCountry(null);
                                    $this->hydrateGuardianPermanentAddress($g, $data, $em);
                                } else {
                                    $g->setPermanentCountry($data['ofw_perm_country'] ?? null);
                                    $g->setPermanentAddress(strtoupper((string)($data['ofw_perm_address'] ?? '')));
                                    $g->setPermanentZip((string)($data['ofw_perm_intl_zip'] ?? $data['ofw_perm_zip'] ?? ''));
                                    $g->setPermanentRegion(null);
                                    $g->setPermanentProvince(null);
                                    $g->setPermanentCity(null);
                                    $g->setPermanentBarangay(null);
                                }
                            }
                        } elseif (isset($data['same_as_applicant_submitted']) || isset($data['same_as_applicant'])) {
                            $sameAsApplicant = isset($data['same_as_applicant']);
                            $g->setSameAsApplicant($sameAsApplicant);

                            if ($sameAsApplicant) {
                                $g->setCurrentRegion($registration->getCurrentRegion());
                                $g->setCurrentProvince($registration->getCurrentProvince());
                                $g->setCurrentCity($registration->getCurrentCity());
                                $g->setCurrentBarangay($registration->getCurrentBarangay());
                                $g->setCurrentAddress($registration->getCurrentAddress());
                                $g->setCurrentZip($registration->getCurrentZip());
                                $g->setPermanentRegion($registration->getPermanentRegion());
                                $g->setPermanentProvince($registration->getPermanentProvince());
                                $g->setPermanentCity($registration->getPermanentCity());
                                $g->setPermanentBarangay($registration->getPermanentBarangay());
                                $g->setPermanentAddress($registration->getPermanentAddress());
                                $g->setPermanentZip($registration->getPermanentZip());
                                $g->setPermanentCountry($registration->getPermanentCountry());
                            } else {
                                $g->setPermanentCountry(null);
                                $this->hydrateGuardianAddress($g, $data, $em);
                            }
                        }
                    }

                    unset($guardiansData[$index]); 
                }
            }

            foreach ($guardiansData as $index => $data) {
                $gType = strtoupper(trim($data['guardian_type'] ?? 'GUARDIAN'));
                $fullName = trim($data['full_name'] ?? '');
                if (!empty($fullName)) {
                    $newG = new \App\Entity\ApplicantBedGuardian();
                    $newG->setApplicant($registration);
                    $newG->setGuardianType($gType);
                    $newG->setRelationship(strtoupper(trim($data['relationship'] ?? 'GUARDIAN')));
                    $newG->setParentName(strtoupper($fullName));
                    $newG->setOccupation(strtoupper($data['occupation'] ?? ''));
                    $newG->setContactNo($data['contact'] ?? '');
                    $newG->setNationality(strtoupper($data['nationality'] ?? 'FILIPINO'));
                    $registration->addGuardian($newG);
                    $em->persist($newG);
                }
            }

            $siblingsData = $request->request->all('siblings');
            foreach ($registration->getSiblings() as $index => $s) {
                if (isset($siblingsData[$index])) {
                    $data = $siblingsData[$index];
                    $s->setSiblingName($data['name']);
                    $s->setSchool($data['school']);
                    $s->setFeuStudentNo($data['feu_id']);
                    $s->setIsFeuStudent(!empty($data['feu_id']));
                }
            }

            $schoolsData = $request->request->all('schools');
            foreach ($registration->getSchools() as $index => $sch) {
                if (isset($schoolsData[$index])) {
                    $data = $schoolsData[$index];
                    $sch->setSchool($data['name']);
                    $sch->setSchoolYear($data['year']);
                    $sch->setSchoolType($data['type'] ?? null);
                    
                    $isInt = !empty($data['is_international']);
                    $sch->setIsInternational($isInt);
                    if ($isInt) {
                        $sch->setCountry($data['country'] ?? null);
                        $sch->setRegion(null);
                        $sch->setProvince(null);
                        $sch->setCity(null);
                    } else {
                        $sch->setCountry(null);
                        $regVal = $data['region'] ?? null;
                        $provVal = $data['province'] ?? null;
                        $cityVal = $data['city'] ?? null;
                        if ($regVal) {
                            $r = is_numeric($regVal)
                                ? $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => (int)$regVal])
                                : $em->getRepository(LookupRegion::class)->findOneBy(['regionDesc' => $regVal]);
                            $sch->setRegion($r ? $r->getRegionDesc() : $regVal);
                        } else {
                            $sch->setRegion(null);
                        }
                        if ($provVal) {
                            $p = is_numeric($provVal)
                                ? $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => (int)$provVal])
                                : $em->getRepository(LookupProvince::class)->findOneBy(['provinceDesc' => $provVal]);
                            $sch->setProvince($p ? $p->getProvinceDesc() : $provVal);
                        } else {
                            $sch->setProvince(null);
                        }
                        if ($cityVal) {
                            $c = is_numeric($cityVal)
                                ? $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => (int)$cityVal])
                                : $em->getRepository(LookupCity::class)->findOneBy(['cityDesc' => $cityVal]);
                            $sch->setCity($c ? $c->getCityDesc() : $cityVal);
                        } else {
                            $sch->setCity(null);
                        }
                    }
                }
            }

            $em->flush();
            $this->addFlash('success', 'Applicant updated successfully.');
            
            $viewRoute = ($registration->getCampus() === ApplicantBed::CAMPUS_ALABANG) 
                ? 'app_admin_alabang_registration_view' 
                : 'app_admin_diliman_registration_view';
                
            return $this->redirectToRoute($viewRoute, ['id' => $registration->getStudentNumber()]);
        }

        return $this->render('admin-onsite/diliman/edit_registration.html.twig', [
            'registration' => $registration,
            'documentSetups' => $documentSetups,
            'nationalities' => $nationalities,
            'countries' => $countries
        ]);
    }

    private function hydrateGuardianAddress(\App\Entity\ApplicantBedGuardian $g, array $data, EntityManagerInterface $em): void
    {
        $regionCode = $data['addr_region'] ?? null;
        $provCode = $data['addr_province'] ?? null;
        $cityCode = $data['addr_city'] ?? null;
        $brgyName = $data['addr_barangay'] ?? null;

        if ($regionCode) {
            $r = is_numeric($regionCode)
                ? $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => (int)$regionCode])
                : $em->getRepository(LookupRegion::class)->findOneBy(['regionDesc' => $regionCode]);
            $g->setCurrentRegion($r ? $r->getRegionDesc() : $regionCode);
        }
        if ($provCode) {
            $p = is_numeric($provCode)
                ? $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => (int)$provCode])
                : $em->getRepository(LookupProvince::class)->findOneBy(['provinceDesc' => $provCode]);
            $g->setCurrentProvince($p ? $p->getProvinceDesc() : $provCode);
        }
        if ($cityCode) {
            $c = is_numeric($cityCode)
                ? $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => (int)$cityCode])
                : $em->getRepository(LookupCity::class)->findOneBy(['cityDesc' => $cityCode]);
            $g->setCurrentCity($c ? $c->getCityDesc() : $cityCode);
        }
        if ($brgyName) {
            $g->setCurrentBarangay($brgyName);
        }

        $g->setCurrentAddress(strtoupper($data['addr_street'] ?? ''));
        $g->setCurrentZip($data['addr_zip'] ?? '');
    }

    private function hydrateGuardianPermanentAddress(\App\Entity\ApplicantBedGuardian $g, array $data, EntityManagerInterface $em): void
    {
        $regionCode = $data['ofw_perm_region'] ?? $data['perm_region'] ?? null;
        $provCode = $data['ofw_perm_province'] ?? $data['perm_province'] ?? null;
        $cityCode = $data['ofw_perm_city'] ?? $data['perm_city'] ?? null;
        $brgyName = $data['ofw_perm_barangay'] ?? $data['perm_barangay'] ?? null;

        if ($regionCode) {
            $r = is_numeric($regionCode)
                ? $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => (int)$regionCode])
                : $em->getRepository(LookupRegion::class)->findOneBy(['regionDesc' => $regionCode]);
            $g->setPermanentRegion($r ? $r->getRegionDesc() : $regionCode);
        }
        if ($provCode) {
            $p = is_numeric($provCode)
                ? $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => (int)$provCode])
                : $em->getRepository(LookupProvince::class)->findOneBy(['provinceDesc' => $provCode]);
            $g->setPermanentProvince($p ? $p->getProvinceDesc() : $provCode);
        }
        if ($cityCode) {
            $c = is_numeric($cityCode)
                ? $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => (int)$cityCode])
                : $em->getRepository(LookupCity::class)->findOneBy(['cityDesc' => $cityCode]);
            $g->setPermanentCity($c ? $c->getCityDesc() : $cityCode);
        }
        if ($brgyName) {
            $g->setPermanentBarangay($brgyName);
        }

        $g->setPermanentAddress(strtoupper($data['ofw_perm_street'] ?? $data['perm_street'] ?? ''));
        $g->setPermanentZip($data['ofw_perm_zip'] ?? $data['perm_zip'] ?? '');
    }

    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type): void
    {
        if ($type === 'permanent' && strtoupper($applicant->getCitizenship() ?? '') === 'INTERNATIONAL') {
            $applicant->setPermanentCountry($request->request->get('perm_country'));
            $applicant->setPermanentProvince($request->request->get('perm_province_text'));
            $applicant->setPermanentCity($request->request->get('perm_city_text'));
            $applicant->setPermanentBarangay($request->request->get('perm_barangay_text'));
            $applicant->setPermanentRegion(null);
            $applicant->setPermanentAddress($request->request->get('permanent_address'));
            $applicant->setPermanentZip($request->request->get('permanent_zip'));
            return;
        }

        $prefix = ($type === 'current') ? 'addr' : 'perm';
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        $regionCode = $request->request->get($prefix . '_region');
        $provCode = $request->request->get($prefix . '_province');
        $cityCode = $request->request->get($prefix . '_city');
        $brgyName = $request->request->get($prefix . '_barangay');

        if ($regionCode) {
            $r = is_numeric($regionCode)
                ? $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => (int)$regionCode])
                : $em->getRepository(LookupRegion::class)->findOneBy(['regionDesc' => $regionCode]);
            $applicant->{'set'.$fieldPrefix.'Region'}($r ? $r->getRegionDesc() : $regionCode);
        } else {
            $applicant->{'set'.$fieldPrefix.'Region'}(null);
        }
        if ($provCode) {
            $p = is_numeric($provCode)
                ? $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => (int)$provCode])
                : $em->getRepository(LookupProvince::class)->findOneBy(['provinceDesc' => $provCode]);
            $applicant->{'set'.$fieldPrefix.'Province'}($p ? $p->getProvinceDesc() : $provCode);
        } else {
            $applicant->{'set'.$fieldPrefix.'Province'}(null);
        }
        if ($cityCode) {
            $c = is_numeric($cityCode)
                ? $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => (int)$cityCode])
                : $em->getRepository(LookupCity::class)->findOneBy(['cityDesc' => $cityCode]);
            $applicant->{'set'.$fieldPrefix.'City'}($c ? $c->getCityDesc() : $cityCode);
        } else {
            $applicant->{'set'.$fieldPrefix.'City'}(null);
        }
        if ($brgyName) {
            $applicant->{'set'.$fieldPrefix.'Barangay'}($brgyName);
        } else {
            $applicant->{'set'.$fieldPrefix.'Barangay'}(null);
        }

        $applicant->{'set'.$fieldPrefix.'Address'}($request->request->get($type . '_address'));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type . '_zip'));
        if ($type === 'permanent') {
            $applicant->setPermanentCountry(null);
        }
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_diliman_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, ApplicantBedRepository $repository, ApplicantDeletionService $service): Response
    {
        $registration = $repository->find($id);
        if (!$registration || $registration->getCampus() !== ApplicantBed::CAMPUS_DILIMAN) {
            throw $this->createNotFoundException();
        }

        $service->deleteApplicant($registration);
        $this->addFlash('success', 'Record deleted.');
        return $this->redirectToRoute('app_admin_diliman_registrations');
    }

    #[Route('/document-setup', name: 'app_admin_diliman_documents', methods: ['GET', 'POST'])]
    public function documentSetup(EntityManagerInterface $em, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $slug = 'req_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $name));
            $slug = preg_replace('/_+/', '_', $slug); 
            
            $doc = new DocumentSetup();
            $doc->setDocumentName($name);
            $doc->setSlug($slug);
            $doc->setFolderName('onsite-' . str_replace('_', '-', $slug));
            $doc->setStudentType($request->request->get('student_type') ?: null);
            $doc->setNationalityType($request->request->get('nationality_type') ?: null);
            $grades = $request->request->all('grade_levels');
            $doc->setGradeLevels(empty($grades) ? null : $grades);
            $doc->setCampus(ApplicantBed::CAMPUS_DILIMAN); 

            $allowedTypesArr = $request->request->all('allowed_file_types');
            if (!empty($allowedTypesArr)) {
                $doc->setAllowedFileTypes(implode(', ', $allowedTypesArr));
            }

            $em->persist($doc);
            $em->flush();
            
            $this->addFlash('success', 'New document configuration added!');
            return $this->redirectToRoute('app_admin_diliman_documents');
        }

        $documents = $em->getRepository(DocumentSetup::class)->findBy(['campus' => [ApplicantBed::CAMPUS_DILIMAN, null]]);
        return $this->render('admin-onsite/diliman/document_setup.html.twig', ['documents' => $documents]);
    }

    #[Route('/document-setup/{id}/delete', name: 'app_admin_diliman_documents_delete', methods: ['POST'])]
    public function deleteDocumentSetup(int $id, EntityManagerInterface $em): Response
    {
        $doc = $em->getRepository(DocumentSetup::class)->find($id);
        if (!$doc || ($doc->getCampus() !== null && $doc->getCampus() !== ApplicantBed::CAMPUS_DILIMAN)) {
            throw $this->createNotFoundException();
        }

        $em->remove($doc);
        $em->flush();
        $this->addFlash('success', 'Configuration permanently deleted.');
        return $this->redirectToRoute('app_admin_diliman_documents');
    }

    #[Route('/document-setup/{id}/update', name: 'app_admin_diliman_documents_update', methods: ['POST'])]
    public function updateDocumentSetup(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $doc = $em->getRepository(DocumentSetup::class)->find($id);
        if (!$doc || ($doc->getCampus() !== null && $doc->getCampus() !== ApplicantBed::CAMPUS_DILIMAN)) {
            throw $this->createNotFoundException();
        }

        $doc->setDocumentName($request->request->get('name'));
        
        $allowedTypesArr = $request->request->all('allowed_file_types');
        $doc->setAllowedFileTypes(implode(', ', $allowedTypesArr));

        $doc->setStudentType($request->request->get('student_type') ?: null);
        $doc->setNationalityType($request->request->get('nationality_type') ?: null);
        $grades = $request->request->all('grade_levels');
        $doc->setGradeLevels(empty($grades) ? null : $grades);
        $em->flush();
        $this->addFlash('success', 'Document configuration updated!');
        return $this->redirectToRoute('app_admin_diliman_documents');
    }

    #[Route('/registration/{id}/document/{slug}/soft-delete', name: 'app_admin_diliman_registration_doc_delete', methods: ['POST'])]
    public function softDeleteApplicantDocument(string $id, string $slug, EntityManagerInterface $em): Response
    {
        $registration = $em->getRepository(ApplicantBed::class)->find($id);
        if (!$registration || $registration->getCampus() !== ApplicantBed::CAMPUS_DILIMAN) {
            throw $this->createNotFoundException();
        }

        $req = $em->getRepository(ApplicantBedRequirement::class)->findOneBy([
            'applicant' => $registration,
            'Slug' => $slug
        ]);
        if ($req) {
            $req->setIsDeleted(true); 
            $em->flush();
            $this->addFlash('success', 'Document soft-deleted successfully.');
        }
        return $this->redirectToRoute('app_admin_diliman_registration_edit', ['id' => $id]);
    }

    private function getFilteredDocumentSetups(ApplicantBed $registration, EntityManagerInterface $em, string $campus): array
    {
        $allSetups = $em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [$campus, null]
        ]);

        $filtered = [];
        $gradeLevel = $registration->getGradeLevel();
        $admissionType = $registration->getAdmissionType(); // 'Freshman' or 'Transferee'
        $citizenship = $registration->getCitizenship(); // 'Filipino', 'Foreign', 'Dual'

        foreach ($allSetups as $setup) {
            /* Check if applicant meets grade level requirement */
            $grades = $setup->getGradeLevels();
            if ($grades && !empty($grades) && !in_array($gradeLevel, $grades)) {
                continue;
            }

            /* Check if applicant meets student admission type requirement */
            $sReq = $setup->getStudentType(); // 'All', 'New', 'Transferee', 'Old'
            if ($sReq && strtoupper($sReq) !== 'ALL') {
                if (strtoupper($sReq) === 'NEW' && strtoupper($admissionType) !== 'FRESHMAN') {
                    continue;
                }
                if (strtoupper($sReq) === 'TRANSFEREE' && strtoupper($admissionType) !== 'TRANSFEREE') {
                    continue;
                }
            }

            /* Check if applicant meets citizenship/nationality requirement */
            $nReq = strtoupper((string) $setup->getNationalityType()); // 'All', 'Local', 'International', 'Filipino', 'Foreign'
            if ($nReq !== '' && $nReq !== 'ALL') {
                $isLocalReq = ($nReq === 'LOCAL' || $nReq === 'FILIPINO');
                $isApplicantLocal = (strtoupper($citizenship) === 'LOCAL' || strtoupper($citizenship) === 'FILIPINO');
                if ($isLocalReq && !$isApplicantLocal) {
                    continue;
                }
                if (!$isLocalReq && $isApplicantLocal) {
                    continue;
                }
            }

            $filtered[] = $setup;
        }

        return $filtered;
    }
}