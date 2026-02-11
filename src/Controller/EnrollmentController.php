<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Entity\StudentParent;
use App\Entity\AdmissionRequirement;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/enrollment')]
class EnrollmentController extends AbstractController
{
    #[Route('/apply', name: 'app_enrollment_apply', methods: ['GET'])]
    public function apply(Request $request): Response
    {
        return $this->render('enrollment-onsite/enroll.html.twig', [
            'selected_campus' => $request->query->get('campus')
        ]);
    }

    #[Route('/apply/submit', name: 'app_enrollment_apply_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        StudentIdGenerator $idGenerator
    ): Response
    {
        $campus = $request->request->get('campus_selected');
        $yearStart = date('Y');

        // 1. Create Student
        $student = new StudentProfile();
        $student->setCampus($campus);
        $student->setLastName($request->request->get('last_name'));
        $student->setFirstName($request->request->get('first_name'));
        $student->setMiddleName($request->request->get('middle_name'));
        $student->setExtensionName($request->request->get('suffix'));
        $student->setBirthDate(new \DateTime($request->request->get('birthday')));
        $student->setGender($request->request->get('gender'));
        $student->setPersonalEmail($request->request->get('email'));
        $student->setMobileNumber($request->request->get('contact_number'));
        
        // Academic Info
        $student->setGradeLevel($request->request->get('grade_level') ?? 'Not Specified');
        $student->setStrand($request->request->get('strand'));
        $student->setSchoolYearStart($yearStart);
        $student->setStatus('Pending');

        $documents = [
        'psa'   => $request->files->get('req_psa'),
        'card'  => $request->files->get('req_card'),
        'moral' => $request->files->get('req_moral'),
        // Add ID Picture here to loop safely later if you want, 
        // OR handle it separately as it goes to the Profile entity, not Requirements entity
    ];
    
    // Handle 2x2 Picture Upload (Specific logic for Profile Entity)
    $idPictureFile = $request->files->get('req_id_picture');
        if ($idPictureFile) {
            $newFilename = 'ID-' . uniqid() . '.' . $idPictureFile->guessExtension();
            try {
                $idPictureFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                $student->setProfilePicture('uploads/avatars/' . $newFilename);
            } catch (\Exception $e) {
                // Handle error if necessary
            }
        }

        // 2. Generate IDs (Feature 2)
        $adCon = 'AD-' . $yearStart . '-' . rand(10000, 99999);
        $student->setAdConNumber($adCon);
        
        $studentNumber = $idGenerator->generateStudentNumber($campus, $yearStart);
        $student->setStudentNumber($studentNumber);

        // 3. Persist
        $em->persist($student);
        $em->flush();

        // 4. Redirect to Success
        return $this->redirectToRoute('app_enrollment_success', ['adcon' => $adCon]);
    }

    #[Route('/apply/success/{adcon}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $adcon, EntityManagerInterface $em): Response
    {
        $student = $em->getRepository(StudentProfile::class)->findOneBy(['adConNumber' => $adcon]);

        if (!$student) {
            throw $this->createNotFoundException('Application not found.');
        }

        return $this->render('enrollment-onsite/success.html.twig', [
            'adcon' => $student->getAdConNumber(),
            'student_number' => $student->getStudentNumber(),
            'student_name' => $student->getFirstName() . ' ' . $student->getLastName()
        ]);
    }
}