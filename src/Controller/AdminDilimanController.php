<?php

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

        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('campus', $campus)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getSingleScalarResult();
            $chartData[] = ['date' => $date->format('M d'), 'count' => $count];
        }

        // --- ENROLLMENT SUMMARY LOGIC ---
        $activeSY = $syRepo->findActiveByCampus($campus);
        $prevSY = null;
        if ($activeSY) {
            $prevSY = $syRepo->findOneBy([
                'campus' => $campus,
                'yearStart' => $activeSY->getYearStart() - 1
            ]);
        }

        $summary = [
            'rows' => [],
            'total' => ['new' => 0, 'old' => 0, 'current' => 0, 'prev' => 0, 'inc' => 0, 'perc' => 0],
            'grs' => [],
            'jhs' => [],
            'shs' => []
        ];

        $categories = [
            'K to 10' => ['levels' => ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10']],
            'Senior HS' => ['levels' => ['Grade 11', 'Grade 12']]
        ];

        foreach ($categories as $catName => $config) {
            $new = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus AND a.gradeLevel IN (:levels) AND a.admissionType != :old')
                ->setParameter('campus', $campus)
                ->setParameter('levels', $config['levels'])
                ->setParameter('old', 'Old Student')
                ->getQuery()->getSingleScalarResult();

            $old = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus AND a.gradeLevel IN (:levels) AND a.admissionType = :old')
                ->setParameter('campus', $campus)
                ->setParameter('levels', $config['levels'])
                ->setParameter('old', 'Old Student')
                ->getQuery()->getSingleScalarResult();

            $currentTotal = $new + $old;
            
            $prevTotal = 0;
            if ($prevSY) {
                $prevTotal = $repository->createQueryBuilder('a')
                    ->select('count(a.studentNumber)')
                    ->where('a.campus = :campus AND a.gradeLevel IN (:levels) AND a.schoolYearOfEntry = :prevLabel')
                    ->setParameter('campus', $campus)
                    ->setParameter('levels', $config['levels'])
                    ->setParameter('prevLabel', $prevSY->getLabel())
                    ->getQuery()->getSingleScalarResult();
            }

            $inc = $currentTotal - $prevTotal;
            $perc = $prevTotal > 0 ? ($inc / $prevTotal) * 100 : 0;

            $summary['rows'][] = [
                'name' => "Diliman - $catName",
                'new' => $new,
                'old' => $old,
                'current' => $currentTotal,
                'prev' => $prevTotal,
                'inc' => $inc,
                'perc' => $perc
            ];

            $summary['total']['new'] += $new;
            $summary['total']['old'] += $old;
            $summary['total']['current'] += $currentTotal;
            $summary['total']['prev'] += $prevTotal;
        }

        $summary['total']['inc'] = $summary['total']['current'] - $summary['total']['prev'];
        $summary['total']['perc'] = $summary['total']['prev'] > 0 ? ($summary['total']['inc'] / $summary['total']['prev']) * 100 : 0;

        // Breakdown Tables
        $grsLevels = ['Kindergarten', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
        foreach ($grsLevels as $l) {
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus AND a.gradeLevel = :lvl')
                ->setParameter('campus', $campus)
                ->setParameter('lvl', $l)
                ->getQuery()->getSingleScalarResult();
            $summary['grs'][] = ['name' => $l, 'count' => $count];
        }

        $jhsLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
        foreach ($jhsLevels as $l) {
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus AND a.gradeLevel = :lvl')
                ->setParameter('campus', $campus)
                ->setParameter('lvl', $l)
                ->getQuery()->getSingleScalarResult();
            $summary['jhs'][] = ['name' => $l, 'count' => $count];
        }

        $strands = ['STEM', 'ABM', 'HUMSS', 'GAS', 'SPORTS', 'ARTS & DESIGN'];
        foreach ($strands as $s) {
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.studentNumber)')
                ->where('a.campus = :campus AND a.trackStrand = :strand')
                ->setParameter('campus', $campus)
                ->setParameter('strand', $s)
                ->getQuery()->getSingleScalarResult();
            $summary['shs'][] = ['name' => $s, 'count' => $count];
        }

        return $this->render('admin-onsite/diliman/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData,
            'summary' => $summary,
            'activeSY' => $activeSY,
            'prevSY' => $prevSY
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
            $qb->andWhere('a.educationType = :eduType')
               ->setParameter('eduType', $eduType);
        }

        $grades = array_filter($request->query->all()['grade_levels'] ?? []);
        if (!empty($grades)) {
            $qb->andWhere('a.gradeLevel IN (:grades)')
               ->setParameter('grades', $grades);
        }

        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')
               ->setParameter('date', "$date%");
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

    #[Route('/registration/{id}/view', name: 'app_admin_diliman_registration_view')]
    public function view(string $id, ApplicantBedRepository $repository): Response
    {
        $registration = $repository->find($id); 
        if (!$registration) throw $this->createNotFoundException();

        return $this->render('admin-onsite/diliman/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_diliman_registration_edit')]
    public function edit(string $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [ApplicantBed::CAMPUS_DILIMAN, null]
        ]);

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
            $registration->setGradeLevel($request->request->get('grade_level'));
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
                        $req->setIsRequired($docSetup->isRequired());
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
            $score = $request->request->get('exam_score');
            $examDateStr = $request->request->get('exam_date');

            if ($examTaken) {
                if ($score !== null && $score !== '') {
                    $registration->setExaminationScore((float)$score);
                }
                if ($examDateStr) {
                    $registration->setExaminationDate(new \DateTime($examDateStr));
                }
                $registration->setAdmissionStatus(ApplicantBed::STATUS_COMPLETED);
            } else {
                $registration->setExaminationScore(null);
                $registration->setExaminationDate(null);
                $registration->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
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
            'documentSetups' => $documentSetups
        ]);
    }

    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type)
    {
        $prefix = ($type === 'current') ? 'addr' : 'perm';
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        $regionCode = $request->request->get($prefix . '_region');
        $provCode = $request->request->get($prefix . '_province');
        $cityCode = $request->request->get($prefix . '_city');
        $brgyName = $request->request->get($prefix . '_barangay');

        if ($regionCode) {
            $r = $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => $regionCode]);
            if ($r) $applicant->{'set'.$fieldPrefix.'Region'}($r->getRegionDesc());
        }
        if ($provCode) {
            $p = $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => $provCode]);
            if ($p) $applicant->{'set'.$fieldPrefix.'Province'}($p->getProvinceDesc());
        }
        if ($cityCode) {
            $c = $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => $cityCode]);
            if ($c) $applicant->{'set'.$fieldPrefix.'City'}($c->getCityDesc());
        }
        if ($brgyName) {
            $applicant->{'set'.$fieldPrefix.'Barangay'}($brgyName);
        }

        $applicant->{'set'.$fieldPrefix.'Address'}($request->request->get($type . '_address'));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type . '_zip'));
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_diliman_delete', methods: ['POST'])]
    public function delete(string $id, ApplicantBedRepository $repository, ApplicantDeletionService $service): Response
    {
        $registration = $repository->find($id);
        if ($registration) {
            $service->deleteApplicant($registration);
            $this->addFlash('success', 'Record deleted.');
        }
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
            $doc->setIsRequired($request->request->get('is_required') === '1');
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
        if ($doc) {
            $em->remove($doc);
            $em->flush();
            $this->addFlash('success', 'Configuration permanently deleted.');
        }
        return $this->redirectToRoute('app_admin_diliman_documents');
    }

    #[Route('/document-setup/{id}/update', name: 'app_admin_diliman_documents_update', methods: ['POST'])]
    public function updateDocumentSetup(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $doc = $em->getRepository(DocumentSetup::class)->find($id);
        if ($doc) {
            $doc->setDocumentName($request->request->get('name'));
            
            $allowedTypesArr = $request->request->all('allowed_file_types');
            $doc->setAllowedFileTypes(implode(', ', $allowedTypesArr));

            $doc->setIsRequired($request->request->get('is_required') === '1');
            $em->flush();
            $this->addFlash('success', 'Document configuration updated!');
        }
        return $this->redirectToRoute('app_admin_diliman_documents');
    }

    #[Route('/registration/{id}/document/{slug}/soft-delete', name: 'app_admin_diliman_registration_doc_delete', methods: ['POST'])]
    public function softDeleteApplicantDocument(string $id, string $slug, EntityManagerInterface $em): Response
    {
        $registration = $em->getRepository(ApplicantBed::class)->find($id);
        if ($registration) {
            $req = $em->getRepository(ApplicantBedRequirement::class)->findOneBy([
                'applicant' => $registration,
                'Slug' => $slug
            ]);
            if ($req) {
                $req->setIsDeleted(true); 
                $em->flush();
                $this->addFlash('success', 'Document soft-deleted successfully.');
            }
        }
        return $this->redirectToRoute('app_admin_diliman_registration_edit', ['id' => $id]);
    }
}