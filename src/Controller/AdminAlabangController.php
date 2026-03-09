<?php

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Entity\ApplicantBedRequirement;
use App\Repository\ApplicantBedRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DocumentSetup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ApplicantDeletionService;

#[Route('/alabang-admin')]
class AdminAlabangController extends AbstractController
{
    #[Route('/', name: 'app_admin_alabang_dashboard')]
    public function dashboard(ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->select('count(a.studentNumber)')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG);

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
                ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()->getSingleScalarResult();
            $chartData[] = ['date' => $date->format('M d'), 'count' => $count];
        }

        return $this->render('admin-onsite/alabang/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData
        ]);
    }

    #[Route('/registrations', name: 'app_admin_alabang_registrations')]
    public function registrations(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG)
            ->orderBy('a.createdAt', 'DESC');

        // Keyword Search (Name or Student No)
        if ($search = $request->query->get('search')) {
            $qb->andWhere('a.firstName LIKE :search OR a.lastName LIKE :search OR a.studentNumber LIKE :search')
               ->setParameter('search', "%$search%");
        }

        // 2. Education Level Filter
        if ($eduType = $request->query->get('education_type')) {
            $qb->andWhere('a.educationType = :eduType')
               ->setParameter('eduType', $eduType);
        }

        // Grade Level Filter (Array of checkboxes)
        $grades = array_filter($request->query->all()['grade_levels'] ?? []);
        if (!empty($grades)) {
            $qb->andWhere('a.gradeLevel IN (:grades)')
               ->setParameter('grades', $grades);
        }

        // Date Filter
        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')
               ->setParameter('date', "$date%");
        }

        return $this->render('admin-onsite/alabang/registrations.html.twig', [
            'registrations' => $qb->getQuery()->getResult(),
            'filters' => $request->query->all()
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(string $id, ApplicantBedRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();
        return $this->render('admin-onsite/alabang/view_registration.html.twig', ['registration' => $registration]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(string $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        // FIX: Fetch ONLY Alabang documents instead of findAll()
        $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [ApplicantBed::CAMPUS_ALABANG, null]
        ]);

        if ($request->isMethod('POST')) {
            // Basic Info
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

            // --- DYNAMIC DOCUMENT REPLACEMENTS ---
            foreach ($documentSetups as $docSetup) {
                $inputName = $docSetup->getSlug();
                $docFile = $request->files->get($inputName);
                
                if ($docFile) {
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
                        $req->setIsDeleted(false); // Un-delete if it was previously soft-deleted!
                        $req->setDateSubmitted(new \DateTime());
                        $req->setStatus('S');
                    } catch (\Exception $e) { }
                }
            }

            // Profile Picture Upload
            $profileFile = $request->files->get('profile_picture');
            if ($profileFile) {
                $filename = 'ID-' . $registration->getStudentNumber() . '-' . uniqid() . '.' . $profileFile->guessExtension();
                try {
                    $profileFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/onsite-id-pics', $filename);
                    $registration->setPhotoSlug('uploads/onsite-id-pics/' . $filename);
                } catch (\Exception $e) { /* Handle error */ }
            }

            // Exam Status Logic
            $examTaken = $request->request->get('exam_taken') === '1';
            $score = $request->request->get('exam_score');

            if ($examTaken && $score !== null && $score !== '') {
                $registration->setExaminationScore((float)$score);
                $registration->setAdmissionStatus(ApplicantBed::STATUS_COMPLETED);
            } else {
                $registration->setExaminationScore(null);
                $registration->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            }

            // Address Lookup Hydration
            $this->hydrateAddress($registration, $request, $em, 'current');
            $this->hydrateAddress($registration, $request, $em, 'permanent');

            // Guardians (Split Name Handling)
            // Guardians (Split Name Handling)
            $guardiansData = $request->request->all('guardians');
            foreach ($registration->getGuardians() as $index => $g) {
                if (isset($guardiansData[$index])) {
                    $data = $guardiansData[$index];
                    
                    // Identify the slot type definitively
                    $gType = strtoupper($data['guardian_type'] ?? $g->getGuardianType() ?? '');
                    if ($gType === '') {
                        $gType = in_array(strtoupper($g->getRelationship()), ['FATHER', 'MOTHER']) ? strtoupper($g->getRelationship()) : 'GUARDIAN';
                    }
                    $g->setGuardianType($gType);

                    // EMERGENCY CONTACT LOGIC
                    if ($gType === 'GUARDIAN') {
                        $g->setParentName(strtoupper(trim($data['full_name'] ?? '')));
                        if (isset($data['relationship']) && trim($data['relationship']) !== '') {
                            $g->setRelationship(strtoupper(trim($data['relationship'])));
                        }
                    } else {
                        // PARENTS LOGIC
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

            // ADD NEW GUARDIANS (If missing from old records)
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

            // Siblings
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

            // Schools
            $schoolsData = $request->request->all('schools');
            foreach ($registration->getSchools() as $index => $sch) {
                if (isset($schoolsData[$index])) {
                    $data = $schoolsData[$index];
                    $sch->setSchool($data['name']);
                    $sch->setYearEnd((int)$data['year']);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Applicant updated successfully.');
            
            // Redirect to VIEW
            $viewRoute = ($registration->getCampus() === ApplicantBed::CAMPUS_ALABANG) 
                ? 'app_admin_alabang_registration_view' 
                : 'app_admin_diliman_registration_view';
                
            return $this->redirectToRoute($viewRoute, ['id' => $registration->getStudentNumber()]);
        }

        return $this->render('admin-onsite/alabang/edit_registration.html.twig', [
            'registration' => $registration,
            'documentSetups' => $documentSetups
        ]);
    }

    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type)
    {
        $prefix = ($type === 'current') ? 'addr' : 'perm';
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        // Check if user selected new lookup values (these inputs come from the JS lookup)
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
            // Dropdown value for barangay is usually the name itself in the JS logic provided earlier
            $applicant->{'set'.$fieldPrefix.'Barangay'}($brgyName);
        }

        // Street Address & Zip are direct text
        $applicant->{'set'.$fieldPrefix.'Address'}($request->request->get($type . '_address'));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type . '_zip'));
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_alabang_delete', methods: ['POST'])]
    public function delete(string $id, ApplicantBedRepository $repository, ApplicantDeletionService $service): Response
    {
        $registration = $repository->find($id);
        if ($registration) $service->deleteApplicant($registration);
        return $this->redirectToRoute('app_admin_alabang_registrations');
    }

    #[Route('/document-setup', name: 'app_admin_alabang_documents', methods: ['GET', 'POST'])]
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
            $doc->setCampus(ApplicantBed::CAMPUS_ALABANG); 

            $em->persist($doc);
            $em->flush();
            
            $this->addFlash('success', 'New document configuration added!');
            return $this->redirectToRoute('app_admin_alabang_documents');
        }

        $documents = $em->getRepository(DocumentSetup::class)->findBy(['campus' => [ApplicantBed::CAMPUS_ALABANG, null]]);
        return $this->render('admin-onsite/alabang/document_setup.html.twig', ['documents' => $documents]);
    }

    #[Route('/document-setup/{id}/delete', name: 'app_admin_alabang_documents_delete', methods: ['POST'])]
    public function deleteDocumentSetup(int $id, EntityManagerInterface $em): Response
    {
        $doc = $em->getRepository(DocumentSetup::class)->find($id);
        if ($doc) {
            $em->remove($doc);
            $em->flush();
            $this->addFlash('success', 'Configuration permanently deleted.');
        }
        return $this->redirectToRoute('app_admin_alabang_documents');
    }

    // --- NEW: SOFT DELETE DOCUMENT FOR A SPECIFIC APPLICANT ---
    #[Route('/registration/{id}/document/{slug}/soft-delete', name: 'app_admin_alabang_registration_doc_delete', methods: ['POST'])]
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
        return $this->redirectToRoute('app_admin_alabang_registration_edit', ['id' => $id]);
    }
}