<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Entity\StudentParent;
use App\Entity\AdmissionRequirement;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/enrollment')]
class EnrollmentController extends AbstractController
{
    private function getStrandsByCampus(string $campus): array
    {
        $strands = [
            'feu_alabang' => [
                'SHS_ABM'   => 'ABM - Accountancy, Business and Management',
                'SHS_BAE'   => 'BAE - Business And Entrepreneurship',
                'SHS_CSMA'  => 'CSMA - Computer Studies And Multimedia Arts',
                'SHS_ENG'   => 'ENG - Engineering',
                'SHS_GAS'   => 'GAS - General Academic Strand',
                'SHS_HA'    => 'HA - Health Allied',
                'SHS_HUMSS' => 'HUMSS - Humanities and Social Sciences',
                'SHS_SSH'   => 'SSH - Social Sciences And Humanities',
                'SHS_STEM'  => 'STEM - Science, Technology, Engineering and Mathematics',
            ],
            'feu_diliman' => [
                'SHS_ABM'    => 'ABM - Accountancy, Business and Management',
                'SHS_GAS'    => 'GAS - General Academic Strand',
                'SHS_HUMSS'  => 'HUMSS - Humanities and Social Sciences',
                'SHS_SPORTS' => 'SPORTS - Sports Track',
                'SHS_STEM'   => 'STEM - Science, Technology, Engineering and Mathematics',
            ],
        ];

        return $strands[$campus] ?? [];
    }

    #[Route('/apply', name: 'app_enrollment_apply', methods: ['GET'])]
    public function apply(Request $request): Response
    {
        $selectedCampus = $request->query->get('campus');

        return $this->render('enrollment-onsite/enroll.html.twig', [
            'selected_campus' => $selectedCampus,
            'available_strands' => $selectedCampus ? $this->getStrandsByCampus($selectedCampus) : []
        ]);
    }

    #[Route('/apply/submit', name: 'app_enrollment_apply_submit', methods: ['POST'])]
    public function submit(
        Request $request, 
        LoggerInterface $logger, 
        EntityManagerInterface $entityManager,
        StudentIdGenerator $idGenerator
    ): Response
    {
        // 1. Extract Context
        $campus = $request->request->get('campus_selected');
        $educationLevel = $request->request->get('education_level');
        
        // 2. File Upload Handling (Simplified for brevity - assume validation passed)
        $documents = [
            'psa'   => $request->files->get('req_psa'),
            'card'  => $request->files->get('req_card'),
            'moral' => $request->files->get('req_moral'),
        ];

        // 3. Create Student Entity
        $student = new StudentProfile();
        $student->setCampus($campus);
        $student->setFirstName($request->request->get('first_name'));
        $student->setLastName($request->request->get('last_name'));
        $student->setMiddleName($request->request->get('middle_name'));
        $student->setExtensionName($request->request->get('suffix'));
        $student->setGradeLevel('SHS'); // Assuming SHS based on context, dynamic if needed
        
        // Date Logic
        $yearStart = date('Y'); // 2025
        $student->setSchoolYearStart($yearStart);
        
        // 4. Generate Numbers
        // Generate AdCon: AD-YYYY-RANDOM (Simple unique check logic might be needed in real prod)
        $adcon = 'AD-' . $yearStart . '-' . rand(1000, 9999); 
        $student->setAdConNumber($adcon);

        // Generate Student Number using the Service
        $studentNumber = $idGenerator->generateStudentNumber($campus, $yearStart);
        $student->setStudentNumber($studentNumber);

        // 5. Handle Parents
        if ($request->request->get('father_lastname')) {
            $father = new StudentParent();
            $father->setName($request->request->get('father_firstname') . ' ' . $request->request->get('father_lastname'));
            $father->setRelationship('Father');
            $student->addParent($father);
            $entityManager->persist($father);
        }

        // 6. Handle Documents (Save paths)
        foreach ($documents as $type => $file) {
            if ($file instanceof UploadedFile) {
                // In production: $file->move() to a directory
                $doc = new AdmissionRequirement();
                $doc->setDocumentType(strtoupper($type));
                $doc->setFilePath('uploads/temp/' . $file->getClientOriginalName()); // Placeholder path
                $doc->setStudentProfile($student);
                $entityManager->persist($doc);
                $student->addRequirement($doc);
            }
        }

        $entityManager->persist($student);
        $entityManager->flush();

        $logger->info('ARIES Application Processed', [
            'adcon' => $adcon,
            'student_id' => $studentNumber
        ]);

        return $this->redirectToRoute('app_enrollment_success', ['adcon' => $adcon]); 
    }

    #[Route('/apply/success/{adcon}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $adcon, EntityManagerInterface $entityManager): Response
    {
        // Fetch the student to display their ID
        $student = $entityManager->getRepository(StudentProfile::class)->findOneBy(['adConNumber' => $adcon]);

        if (!$student) {
            throw $this->createNotFoundException('Application not found');
        }

        return $this->render('enrollment-onsite/success.html.twig', [
            'adcon' => $adcon,
            'student_number' => $student->getStudentNumber()
        ]);
    }
}