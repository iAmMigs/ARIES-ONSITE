<?php

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Repository\ApplicantBedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ApplicantDeletionService;

#[Route('/diliman-admin')]
class AdminDilimanController extends AbstractController
{
    #[Route('/', name: 'app_admin_diliman_dashboard')]
    public function dashboard(ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->select('count(a.studentNumber)')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN);

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
                ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getSingleScalarResult();
            $chartData[] = ['date' => $date->format('M d'), 'count' => $count];
        }

        return $this->render('admin-onsite/diliman/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData
        ]);
    }

    #[Route('/registrations', name: 'app_admin_diliman_registrations')]
    public function registrations(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
            ->orderBy('a.createdAt', 'DESC');

        if ($grade = $request->query->get('grade')) {
            $qb->andWhere('a.gradeLevel = :grade')->setParameter('grade', $grade);
        }
        
        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')->setParameter('date', "$date%");
        }

        return $this->render('admin-onsite/diliman/registrations.html.twig', [
            'registrations' => $qb->getQuery()->getResult(),
            'filters' => $request->query->all()
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

        if ($request->isMethod('POST')) {
            // 1. Basic Info
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

            // 2. Profile Picture Upload
            $profileFile = $request->files->get('profile_picture');
            if ($profileFile) {
                $filename = 'ID-' . $registration->getStudentNumber() . '-' . uniqid() . '.' . $profileFile->guessExtension();
                try {
                    $profileFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/onsite-id-pics', $filename);
                    $registration->setPhotoSlug('uploads/onsite-id-pics/' . $filename);
                } catch (\Exception $e) { /* Handle error */ }
            }

            // 3. Document Replacements (PDF Only)
            $docMap = [
                'req_psa' => 'onsite-psa',
                'req_card' => 'onsite-cards',
                'req_moral' => 'onsite-moral'
            ];
            
            // Loop through existing requirements to check for updates
            foreach ($registration->getRequirements() as $req) {
                // Determine which input name corresponds to this requirement based on slug or name
                $inputName = null;
                if (stripos($req->getRequirement(), 'PSA') !== false) $inputName = 'req_psa';
                elseif (stripos($req->getRequirement(), 'Report Card') !== false) $inputName = 'req_card';
                elseif (stripos($req->getRequirement(), 'Good Moral') !== false) $inputName = 'req_moral';

                if ($inputName) {
                    $docFile = $request->files->get($inputName);
                    if ($docFile) {
                        $filename = strtoupper(str_replace('req_', 'REQ_', $inputName)) . '-' . $registration->getStudentNumber() . '-' . uniqid() . '.pdf';
                        try {
                            $docFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/' . $docMap[$inputName], $filename);
                            $req->setStoredFileName('uploads/' . $docMap[$inputName] . '/' . $filename);
                            $req->setDateSubmitted(new \DateTime());
                            $req->setStatus('S'); // Ensure it is marked as submitted
                        } catch (\Exception $e) { /* Handle error */ }
                    }
                }
            }

            // 4. Exam Status Logic
            $examTaken = $request->request->get('exam_taken') === '1';
            $score = $request->request->get('exam_score');

            if ($examTaken && $score !== null && $score !== '') {
                $registration->setExaminationScore((float)$score);
                $registration->setAdmissionStatus(ApplicantBed::STATUS_COMPLETED);
            } else {
                $registration->setExaminationScore(null);
                $registration->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            }

            // 5. Address Lookup Hydration
            $this->hydrateAddress($registration, $request, $em, 'current');
            $this->hydrateAddress($registration, $request, $em, 'permanent');

            // 6. Guardians (Split Name Handling)
            $guardiansData = $request->request->all('guardians');
            foreach ($registration->getGuardians() as $index => $g) {
                if (isset($guardiansData[$index])) {
                    $data = $guardiansData[$index];
                    
                    // Stitch split names back together
                    $firstName = $data['first_name'] ?? '';
                    $lastName = $data['last_name'] ?? '';
                    if ($firstName || $lastName) {
                        $g->setParentName($lastName . ', ' . $firstName);
                    }

                    $g->setOccupation($data['occupation'] ?? $g->getOccupation());
                    $g->setContactNo($data['contact'] ?? $g->getContactNo());
                    $g->setDeceased(isset($data['deceased']));
                    $g->setOFW(isset($data['ofw']));
                }
            }

            // 7. Siblings
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

            // 8. Schools
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

        return $this->render('admin-onsite/diliman/edit_registration.html.twig', [
            'registration' => $registration
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
}