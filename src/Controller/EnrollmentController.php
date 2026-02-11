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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/enrollment')]
class EnrollmentController extends AbstractController
{
    #[Route('/', name: 'app_enrollment_apply', methods: ['GET'])]
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

        // --- 1. STUDENT PROFILE (Personal) ---
        $student = new StudentProfile();
        $student->setCampus($campus);
        $student->setLastName($request->request->get('last_name'));
        $student->setFirstName($request->request->get('first_name'));
        $student->setMiddleName($request->request->get('middle_name'));
        $student->setExtensionName($request->request->get('suffix'));
        $student->setBirthDate(new \DateTime($request->request->get('birthday')));
        $student->setBirthPlace($request->request->get('birth_place'));
        $student->setBirthCountry($request->request->get('birth_country')); // You might need to add this field to form
        $student->setGender($request->request->get('gender'));
        $student->setReligion($request->request->get('religion'));
        $student->setCitizenship($request->request->get('citizenship'));
        $student->setCivilStatus($request->request->get('civil_status'));
        $student->setLrn($request->request->get('lrn'));

        // --- 2. CONTACT & ADDRESS ---
        $student->setPersonalEmail($request->request->get('email'));
        $student->setMobileNumber($request->request->get('contact_number'));
        $student->setLandlineNumber($request->request->get('landline'));
        
        $student->setAddressStreet($request->request->get('address_street') ?? $request->request->get('address')); // Fallback
        $student->setAddressBarangay($request->request->get('address_barangay'));
        $student->setAddressCity($request->request->get('address_city'));
        $student->setAddressProvince($request->request->get('address_province'));
        $student->setAddressZip($request->request->get('address_zip'));

        // --- 3. ACADEMIC INFO ---
        $student->setGradeLevel($request->request->get('grade_level') ?? 'Not Specified');
        $student->setStrand($request->request->get('strand'));
        $student->setSchoolYearStart($yearStart);
        $student->setTerm('1'); // Default term
        $student->setStatus('Pending');

        // --- 4. GENERATE IDs ---
        $adCon = 'AD-' . $yearStart . '-' . rand(10000, 99999);
        $student->setAdConNumber($adCon);
        $studentNumber = $idGenerator->generateStudentNumber($campus, $yearStart);
        $student->setStudentNumber($studentNumber);

        // --- 5. PREVIOUS SCHOOL ---
        if ($request->request->get('prev_school_name')) {
            $school = new StudentSchool();
            $school->setSchoolName($request->request->get('prev_school_name'));
            // Add level/year if you have fields for them in form
            $school->setLevel($request->request->get('grade_level')); // Assuming previous level
            $student->addPreviousSchool($school);
            $em->persist($school);
        }

        // --- 6. PARENTS ---
        // Father
        if ($request->request->get('father_lastname')) {
            $father = new StudentParent();
            $father->setName($request->request->get('father_firstname') . ' ' . $request->request->get('father_lastname'));
            $father->setRelationship('Father');
            $father->setOccupation($request->request->get('father_occupation'));
            $father->setContactNumber($request->request->get('father_contact'));
            // Note: Add is_ofw / deceased if entity supports it and form sends it
            $student->addParent($father);
            $em->persist($father);
        }

        // Mother
        if ($request->request->get('mother_lastname')) {
            $mother = new StudentParent();
            $mother->setName($request->request->get('mother_firstname') . ' ' . $request->request->get('mother_lastname'));
            $mother->setRelationship('Mother');
            $mother->setOccupation($request->request->get('mother_occupation'));
            $mother->setContactNumber($request->request->get('mother_contact'));
            $student->addParent($mother);
            $em->persist($mother);
        }

        // Guardian
        if ($request->request->get('guardian_name')) {
            $guardian = new StudentParent();
            $guardian->setName($request->request->get('guardian_name'));
            $guardian->setRelationship($request->request->get('guardian_relation') ?? 'Guardian');
            $guardian->setContactNumber($request->request->get('guardian_contact'));
            $student->addParent($guardian);
            $em->persist($guardian);
        }

        // --- 7. SIBLINGS (Handling Dynamic Arrays) ---
        $siblingNames = $request->request->all()['sibling_name'] ?? [];
        $siblingAges  = $request->request->all()['sibling_age'] ?? [];
        $siblingSchools = $request->request->all()['sibling_school'] ?? [];

        if (is_array($siblingNames)) {
            foreach ($siblingNames as $index => $name) {
                if (!empty($name)) {
                    $sibling = new StudentSibling();
                    $sibling->setName($name);
                    $sibling->setAge($siblingAges[$index] ?? null);
                    $sibling->setSchoolOrCompany($siblingSchools[$index] ?? null);
                    $student->addSibling($sibling);
                    $em->persist($sibling);
                }
            }
        }

        // --- 8. FILE UPLOADS ---
        
        // 2x2 ID Picture (Profile Entity)
        $idPictureFile = $request->files->get('req_id_picture');
        if ($idPictureFile instanceof UploadedFile) {
            $newFilename = 'ID-' . uniqid() . '.' . $idPictureFile->guessExtension();
            try {
                $idPictureFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/avatars', $newFilename);
                $student->setProfilePicture('uploads/avatars/' . $newFilename);
            } catch (\Exception $e) { /* Log error */ }
        }

        // Requirements (AdmissionRequirement Entity)
        $docMap = [
            'req_psa' => 'PSA Birth Certificate',
            'req_card' => 'Report Card',
            'req_moral' => 'Good Moral Certificate'
        ];

        foreach ($docMap as $field => $label) {
            $file = $request->files->get($field);
            if ($file instanceof UploadedFile) {
                $docFilename = strtoupper($field) . '-' . uniqid() . '.' . $file->guessExtension();
                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/docs', $docFilename);
                    
                    $req = new AdmissionRequirement();
                    $req->setDocumentType($label);
                    $req->setFilePath('uploads/docs/' . $docFilename);
                    $student->addRequirement($req);
                    $em->persist($req);
                } catch (\Exception $e) { /* Log error */ }
            }
        }

        // --- 9. FINAL PERSIST ---
        $em->persist($student);
        $em->flush();

        return $this->redirectToRoute('app_enrollment_success', ['adcon' => $adCon]); 
    }

    #[Route('/apply/success/{adcon}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $adcon, EntityManagerInterface $em): Response
    {
        $student = $em->getRepository(StudentProfile::class)->findOneBy(['adConNumber' => $adcon]);

        if (!$student) {
            throw $this->createNotFoundException('Application not found');
        }

        return $this->render('enrollment-onsite/success.html.twig', [
            'adcon' => $adcon,
            'student_number' => $student->getStudentNumber(),
            'student_name' => $student->getFirstName() . ' ' . $student->getLastName()
        ]);
    }
}