<?php

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedSibling;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedRequirement;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Entity\LookupBarangay;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        // --- 1. INITIALIZE APPLICANT ---
        $applicant = new ApplicantBed();
        
        // --- 2. IDENTIFIERS ---
        // Generate AdCon: AD-YYYY-RANDOM
        $adCon = 'AD-' . $yearStart . '-' . str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $applicant->setAdCon($adCon);
        
        // Generate Student Number
        $studentNo = $idGenerator->generateStudentNumber($campus, $yearStart);
        $applicant->setStudentNumber($studentNo);
        
        $applicant->setCampus($campus == 'feu_alabang' ? ApplicantBed::CAMPUS_ALABANG : ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setEnrollmentStep(1);
        $applicant->setAdmissionDate(new \DateTime());

        // --- 3. ACADEMIC INFO ---
        $applicant->setGradeLevel($request->request->get('grade_level'));
        $applicant->setTrackStrand($request->request->get('strand'));
        $applicant->setLrn($request->request->get('lrn'));
        $applicant->setSchoolYearOfEntry($yearStart . '-' . ($yearStart + 1));
        // Admission Status from form (Freshman, Transferee, etc.) - mapped to 'program' or specific field if needed
        // For now, we use the internal STATUS_PENDING for system status

        // --- 4. PERSONAL INFORMATION ---
        $applicant->setLastName($request->request->get('last_name'));
        $applicant->setFirstName($request->request->get('first_name'));
        $applicant->setMiddleName($request->request->get('middle_name'));
        $applicant->setExtensionName($request->request->get('suffix'));
        $applicant->setBirthDate(new \DateTime($request->request->get('birthday')));
        $applicant->setBirthPlace($request->request->get('birth_place'));
        $applicant->setGender($request->request->get('gender') == 'Male' ? ApplicantBed::GENDER_MALE : ApplicantBed::GENDER_FEMALE);
        $applicant->setReligion($request->request->get('religion'));
        $applicant->setCitizenship($request->request->get('citizenship'));
        // $applicant->setCivilStatus($request->request->get('civil_status')); // Removed per instruction
        $applicant->setIndigenousGroup($request->request->get('indigenous_group'));
        
        // Visa (If Foreign)
        if ($request->request->get('citizenship') !== 'Filipino') {
            $applicant->setPassportNumber($request->request->get('passport_number'));
            $applicant->setVisaType($request->request->get('visa_type'));
        }

        // --- 5. CONTACT INFORMATION ---
        $applicant->setMobileNumber($request->request->get('contact_number'));
        $applicant->setPersonalEmail($request->request->get('email'));
        $applicant->setLandLineNumber($request->request->get('landline'));

        // --- 6. ADDRESS TRANSLATION (Codes -> Names) ---
        // We use the repositories to find the name based on the code sent by the form
        $this->hydrateAddress($applicant, $request, $em, 'current');
        
        // Permanent Address
        if ($request->request->get('sameAsCurrent') === 'on') {
            // Copy current to permanent
            $applicant->setPermanentRegion($applicant->getCurrentRegion());
            $applicant->setPermanentProvince($applicant->getCurrentProvince());
            $applicant->setPermanentCity($applicant->getCurrentCity());
            $applicant->setPermanentBarangay($applicant->getCurrentBarangay());
            $applicant->setPermanentAddress($applicant->getCurrentAddress());
            $applicant->setPermanentZip($applicant->getCurrentZip());
        } else {
            // Hydrate distinct permanent address
            $this->hydrateAddress($applicant, $request, $em, 'permanent');
        }

        // --- 7. PARENTS / GUARDIANS ---
        $this->handleGuardian($applicant, $request, 'father', $em);
        $this->handleGuardian($applicant, $request, 'mother', $em);
        $this->handleGuardian($applicant, $request, 'guardian', $em);

        // --- 8. SIBLINGS ---
        $siblingNames = $request->request->all()['sibling_name'] ?? [];
        $siblingSchools = $request->request->all()['sibling_school'] ?? [];
        $siblingStudentNos = $request->request->all()['sibling_student_no'] ?? [];

        if (is_array($siblingNames)) {
            foreach ($siblingNames as $index => $name) {
                if (!empty($name)) {
                    $sibling = new ApplicantBedSibling();
                    $sibling->setAdCon($adCon);
                    $sibling->setSiblingName($name);
                    $sibling->setSchool($siblingSchools[$index] ?? null);
                    $sibling->setFeuStudentNo($siblingStudentNos[$index] ?? null);
                    if (!empty($siblingStudentNos[$index])) {
                        $sibling->setIsFeuStudent(true);
                    }
                    $applicant->addSibling($sibling);
                    $em->persist($sibling);
                }
            }
        }

        // --- 9. EDUCATION HISTORY ---
        if ($request->request->get('prev_school_name')) {
            $school = new ApplicantBedSchool();
            $school->setAdCon($adCon);
            $school->setSchool($request->request->get('prev_school_name'));
            $school->setYearEnd((int)$request->request->get('prev_school_year'));
            // Determine level based on grade applied for
            $school->setLevel(ApplicantBedSchool::LEVEL_ELEMENTARY); // Default/Logic to detect level
            $applicant->addSchool($school);
            $em->persist($school);
        }

        // --- 10. FILE UPLOADS ---
        // 2x2 Picture
        $idFile = $request->files->get('req_id_picture');
        if ($idFile instanceof UploadedFile) {
            $filename = 'ID-' . $adCon . '.' . $idFile->guessExtension();
            try {
                $idFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/avatars', $filename);
                $applicant->setPhotoSlug('uploads/avatars/' . $filename);
            } catch (\Exception $e) { }
        }

        // Documents
        $docMap = [
            'req_psa' => 'PSA Birth Certificate',
            'req_card' => 'Report Card',
            'req_moral' => 'Good Moral Certificate'
        ];

        foreach ($docMap as $field => $label) {
            $file = $request->files->get($field);
            if ($file instanceof UploadedFile) {
                $filename = strtoupper($field) . '-' . $adCon . '.' . $file->guessExtension();
                try {
                    $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/docs', $filename);
                    
                    $req = new ApplicantBedRequirement();
                    $req->setAdCon($adCon);
                    $req->setRequirement($label);
                    $req->setStoredFileName('uploads/docs/' . $filename);
                    $req->setSlug(strtolower(str_replace(' ', '-', $label)));
                    $req->setStatus('S'); // Submitted
                    $req->setDateSubmitted(new \DateTime());
                    $req->setIsRequired(true);
                    
                    $applicant->addRequirement($req);
                    $em->persist($req);
                } catch (\Exception $e) { }
            }
        }

        // --- FINAL SAVE ---
        $em->persist($applicant);
        $em->flush();

        return $this->redirectToRoute('app_enrollment_success', ['adcon' => $adCon]);
    }

    // --- HELPER: Address Hydration ---
    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type)
    {
        $prefix = ($type === 'current') ? 'address' : 'perm'; // Matches form names: address_region vs perm_region
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        // 1. Fetch Objects using ID from form
        $regionId = $request->request->get($type === 'current' ? 'region' : 'perm_region'); // form name exception for current region
        $provId = $request->request->get($prefix . '_province');
        $cityId = $request->request->get($prefix . '_city');
        $brgyId = $request->request->get($prefix . '_barangay');

        // 2. Resolve Names
        if($regionId) {
            $r = $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => $regionId]);
            if($r) $applicant->{'set'.$fieldPrefix.'Region'}($r->getRegionDesc());
        }
        if($provId) {
            $p = $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => $provId]);
            if($p) $applicant->{'set'.$fieldPrefix.'Province'}($p->getProvinceDesc());
        }
        if($cityId) {
            $c = $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => $cityId]);
            if($c) $applicant->{'set'.$fieldPrefix.'City'}($c->getCityDesc());
        }
        if($brgyId) {
            // Note: lookup_barangay usually has integer ID, not code. Assuming ID passed or Code.
            // Based on your SQL: barangayCode is PK.
            $b = $em->getRepository(LookupBarangay::class)->findOneBy(['barangayCode' => $brgyId]); 
            // OR if the form sends the name directly (check JS), currently JS sends Name value.
            // If JS sends name directly:
            $applicant->{'set'.$fieldPrefix.'Barangay'}($brgyId); // If form value is name
        }

        // 3. Street & Zip
        $applicant->{'set'.$fieldPrefix.'Address'}($request->request->get($type === 'current' ? 'address' : 'perm_address'));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type === 'current' ? 'address_zip' : 'perm_zip'));
    }

    // --- HELPER: Guardian Handling ---
    private function handleGuardian(ApplicantBed $applicant, Request $request, string $type, EntityManagerInterface $em)
    {
        $lname = $request->request->get($type . '_lastname'); // father_lastname
        $fname = $request->request->get($type . '_firstname'); // father_firstname
        $fullname = $type === 'guardian' ? $request->request->get('guardian_name') : "$lname, $fname";

        if (!empty($fullname) && $fullname !== ', ') {
            $guardian = new ApplicantBedGuardian();
            $guardian->setAdCon($applicant->getAdCon());
            $guardian->setParentName($fullname);
            
            // Map types
            if ($type === 'father') $guardian->setRelationship('Father');
            elseif ($type === 'mother') $guardian->setRelationship('Mother');
            else $guardian->setRelationship($request->request->get('guardian_relation') ?? 'Guardian');

            $guardian->setOccupation($request->request->get($type . '_occupation'));
            $guardian->setContactNo($request->request->get($type . '_contact'));
            
            // Checkboxes
            if ($request->request->get($type . '_deceased')) $guardian->setDeceased(true);
            if ($request->request->get($type . '_ofw')) $guardian->setOFW(true);

            $applicant->addGuardian($guardian);
            $em->persist($guardian);
        }
    }

    #[Route('/apply/success/{adcon}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $adcon, EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(ApplicantBed::class)->findOneBy(['adCon' => $adcon]);

        if (!$applicant) {
            throw $this->createNotFoundException('Application not found.');
        }

        return $this->render('enrollment-onsite/success.html.twig', [
            'adcon' => $applicant->getAdCon(),
            'student_number' => $applicant->getStudentNumber(),
            'student_name' => $applicant->getFirstName() . ' ' . $applicant->getLastName()
        ]);
    }
}