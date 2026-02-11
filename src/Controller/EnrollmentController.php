<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Entity\StudentParent;
use App\Entity\StudentSibling;
use App\Entity\StudentSchool;
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

        // --- 1. Create Student Profile ---
        $student = new StudentProfile();
        $student->setCampus($campus);
        $student->setLastName($request->request->get('last_name'));
        $student->setFirstName($request->request->get('first_name'));
        $student->setMiddleName($request->request->get('middle_name'));
        $student->setExtensionName($request->request->get('suffix'));
        $student->setBirthDate(new \DateTime($request->request->get('birthday')));
        
        // Detailed Info (Matching DataF.csv)
        $student->setGender($request->request->get('gender'));
        $student->setReligion($request->request->get('religion'));
        $student->setCitizenship($request->request->get('citizenship'));
        $student->setCivilStatus($request->request->get('civil_status'));
        $student->setBirthPlace($request->request->get('birth_place'));
        $student->setLrn($request->request->get('lrn'));

        // Contact
        $student->setPersonalEmail($request->request->get('email'));
        $student->setMobileNumber($request->request->get('contact_number'));
        $student->setLandlineNumber($request->request->get('landline'));
        
        // Address (Assuming Form inputs match these keys)
        $student->setCurrentAddress($request->request->get('address')); 
        $student->setCurrentBarangay($request->request->get('address_barangay'));
        $student->setCurrentCity($request->request->get('address_city'));
        $student->setCurrentProvince($request->request->get('address_province'));
        $student->setCurrentZip($request->request->get('address_zip'));

        // Academic Info
        $student->setGradeLevel($request->request->get('grade_level') ?? 'Not Specified');
        $student->setStrand($request->request->get('strand'));
        $student->setSchoolYearOfEntry($yearStart); 
        $student->setTermOfEntry('1'); 
        $student->setStatus('Pending');

        // --- 2. Generate IDs (Feature 2) ---
        $adCon = 'AD-' . $yearStart . '-' . rand(10000, 99999);
        $student->setAdConNumber($adCon);
        
        $studentNumber = $idGenerator->generateStudentNumber($campus, $yearStart);
        $student->setStudentNumber($studentNumber);

        // --- 3. Previous School ---
        if ($request->request->get('prev_school_name')) {
            $school = new StudentSchool();
            $school->setSchoolName($request->request->get('prev_school_name'));
            $school->setYearEnd($request->request->get('prev_school_year') ?? date('Y')); 
            $student->addPreviousSchool($school);
            $em->persist($school);
        }

        // --- 4. Parents ---
        // Father
        if ($request->request->get('father_lastname')) {
            $father = new StudentParent();
            $father->setName($request->request->get('father_firstname') . ' ' . $request->request->get('father_lastname'));
            $father->setRelationship('Father');
            $father->setOccupation($request->request->get('father_occupation'));
            $father->setMobileNumber($request->request->get('father_contact'));
            $student->addParent($father);
            $em->persist($father);
        }

        // Mother
        if ($request->request->get('mother_lastname')) {
            $mother = new StudentParent();
            $mother->setName($request->request->get('mother_firstname') . ' ' . $request->request->get('mother_lastname'));
            $mother->setRelationship('Mother');
            $mother->setOccupation($request->request->get('mother_occupation'));
            $mother->setMobileNumber($request->request->get('mother_contact'));
            $student->addParent($mother);
            $em->persist($mother);
        }

        // Guardian
        if ($request->request->get('guardian_name')) {
            $guardian = new StudentParent();
            $guardian->setName($request->request->get('guardian_name'));
            $guardian->setRelationship($request->request->get('guardian_relation') ?? 'Guardian');
            $guardian->setMobileNumber($request->request->get('guardian_contact'));
            $guardian->setIsEmergencyContact(true); 
            $student->addParent($guardian);
            $em->persist($guardian);
        }

        // --- 5. Siblings ---
        $siblingNames = $request->request->all()['sibling_name'] ?? [];
        $siblingSchools = $request->request->all()['sibling_school'] ?? [];
        
        if (is_array($siblingNames)) {
            foreach ($siblingNames as $index => $name) {
                if (!empty($name)) {
                    $sibling = new StudentSibling();
                    $sibling->setName($name);
                    $sibling->setSchoolName($siblingSchools[$index] ?? null);
                    // $sibling->setAge($siblingAges[$index]); // Add back if form has age
                    $student->addSibling($sibling);
                    $em->persist($sibling);
                }
            }
        }

        // --- 6. File Uploads ---
        $docMap = [
            'req_psa' => 'PSA Birth Certificate',
            'req_card' => 'Report Card',
            'req_moral' => 'Good Moral Certificate'
        ];

        foreach ($docMap as $field => $label) {
            $file = $request->files->get($field);
            if ($file instanceof UploadedFile) {
                $filename = strtoupper($field) . '-' . uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/docs', $filename);
                    $req = new AdmissionRequirement();
                    $req->setDocumentType($label);
                    $req->setFilePath('uploads/docs/' . $filename);
                    $student->addRequirement($req);
                    $em->persist($req);
                } catch (\Exception $e) { }
            }
        }

        // Profile Picture (2x2)
        $idPictureFile = $request->files->get('req_id_picture');
        if ($idPictureFile instanceof UploadedFile) {
            $newFilename = 'ID-' . uniqid() . '.' . $idPictureFile->guessExtension();
            try {
                $idPictureFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                $student->setProfilePicture('uploads/avatars/' . $newFilename);
            } catch (\Exception $e) { }
        }

        $em->persist($student);
        $em->flush();

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