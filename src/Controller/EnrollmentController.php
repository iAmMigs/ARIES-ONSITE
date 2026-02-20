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

        $applicant = new ApplicantBed();
        
        // --- 1. IDENTIFIERS (Student Number Only) ---
        // Removed AdCon generation
        $studentNo = $idGenerator->generateStudentNumber($campus, $yearStart);
        $applicant->setStudentNumber($studentNo);
        
        $applicant->setCampus($campus == 'feu_alabang' ? ApplicantBed::CAMPUS_ALABANG : ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setAdmissionType($request->request->get('admission_type'));
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setAdmissionDate(new \DateTime());

        // --- 2. ACADEMIC INFO ---
        $applicant->setEducationType($request->request->get('education_type'));
        $applicant->setGradeLevel($request->request->get('grade_level'));
        $applicant->setTrackStrand($request->request->get('strand'));
        $applicant->setLrn($request->request->get('lrn'));
        $applicant->setSchoolYearOfEntry($yearStart . '-' . ($yearStart + 1));

        // --- 3. PERSONAL INFORMATION ---
        $applicant->setLastName($request->request->get('last_name'));
        $applicant->setFirstName($request->request->get('first_name'));
        $applicant->setMiddleName($request->request->get('middle_name'));
        $applicant->setExtensionName($request->request->get('suffix'));
        $applicant->setBirthDate(new \DateTime($request->request->get('birthday')));
        $applicant->setBirthPlace($request->request->get('birth_place'));
        
        $gender = $request->request->get('gender') == 'Male' ? ApplicantBed::GENDER_MALE : ApplicantBed::GENDER_FEMALE;
        $applicant->setGender($gender);
        
        $applicant->setReligion($request->request->get('religion'));
        $citizenship = $request->request->get('citizenship');
        $applicant->setCitizenship($citizenship);
        
        if (in_array(strtolower($citizenship), ['foreign', 'dual citizenship'])) {
            $applicant->setPassportNumber($request->request->get('passport_number'));
            $applicant->setVisaType($request->request->get('visa_type'));
            $applicant->setVisaStatus($request->request->get('visa_status'));
            $applicant->setIndigenousGroup(null); // Force clear indigenous group
        } else {
            $applicant->setIndigenousGroup($request->request->get('indigenous_group'));
            $applicant->setPassportNumber(null); // Force clear foreign fields
            $applicant->setVisaType(null);
            $applicant->setVisaStatus(null);
        }

        $applicant->setIndigenousGroup($request->request->get('indigenous_group'));

        // --- 4. CONTACT INFORMATION ---
        $applicant->setMobileNumber($request->request->get('contact_number'));
        $applicant->setPersonalEmail($request->request->get('email'));
        $applicant->setLandLineNumber($request->request->get('landline'));

        // --- 5. ADDRESS ---
        $this->hydrateAddress($applicant, $request, $em, 'current');
        
        if ($request->request->get('sameAsCurrent') === 'on') {
            $applicant->setPermanentRegion($applicant->getCurrentRegion());
            $applicant->setPermanentProvince($applicant->getCurrentProvince());
            $applicant->setPermanentCity($applicant->getCurrentCity());
            $applicant->setPermanentBarangay($applicant->getCurrentBarangay());
            $applicant->setPermanentAddress($applicant->getCurrentAddress());
            $applicant->setPermanentZip($applicant->getCurrentZip());
        } else {
            $this->hydrateAddress($applicant, $request, $em, 'permanent');
        }

        // --- 6. GUARDIANS ---
        $this->handleGuardian($applicant, $request, 'father', $em);
        $this->handleGuardian($applicant, $request, 'mother', $em);
        $this->handleGuardian($applicant, $request, 'guardian', $em);

        // --- 7. SIBLINGS ---
        $siblingNames = $request->request->all()['sibling_name'] ?? [];
        $siblingSchools = $request->request->all()['sibling_school'] ?? [];
        $siblingStudentNos = $request->request->all()['sibling_student_no'] ?? [];

        if (is_array($siblingNames)) {
            foreach ($siblingNames as $index => $name) {
                if (!empty($name)) {
                    $sibling = new ApplicantBedSibling();
                    $sibling->setApplicant($applicant);
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

        // --- 8. EDUCATION HISTORY ---
        if ($request->request->get('prev_school_name')) {
            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool($request->request->get('prev_school_name'));
            $school->setYearEnd((int)$request->request->get('prev_school_year'));
            $school->setLevel(ApplicantBedSchool::LEVEL_ELEMENTARY); 
            $applicant->addSchool($school);
            $em->persist($school);
        }

        // --- 9. FILE UPLOADS ---
        
        // 2x2 Picture -> onsite-id-pics
        $idFile = $request->files->get('req_id_picture');
        if ($idFile instanceof UploadedFile) {
            $filename = 'ID-' . $studentNo . '.' . $idFile->guessExtension();
            try {
                $folder = 'onsite-id-pics';
                $idFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/' . $folder, $filename);
                $applicant->setPhotoSlug('uploads/' . $folder . '/' . $filename);
            } catch (\Exception $e) { }
        }

        // Docs
        $docMap = [
            'req_psa' => [
                'label' => 'PSA Birth Certificate', 
                'folder' => 'onsite-psa'
            ],
            'req_card' => [
                'label' => 'Report Card', 
                'folder' => 'onsite-cards'
            ],
            'req_moral' => [
                'label' => 'Good Moral Certificate', 
                'folder' => 'onsite-moral'
            ]
        ];

        foreach ($docMap as $field => $config) {
            $file = $request->files->get($field);
            if ($file instanceof UploadedFile) {
                $label = $config['label'];
                $folderName = $config['folder'];
                
                $filename = strtoupper($field) . '-' . $studentNo . '.' . $file->guessExtension();
                
                try {
                    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $folderName;
                    $file->move($targetDir, $filename);
                    
                    $req = new ApplicantBedRequirement();
                    $req->setApplicant($applicant);
                    $req->setRequirement($label);
                    $req->setStoredFileName('uploads/' . $folderName . '/' . $filename);
                    $req->setSlug(strtolower(str_replace(' ', '-', $label)));
                    $req->setStatus('S');
                    $req->setDateSubmitted(new \DateTime());
                    $req->setIsRequired(true);
                    
                    $applicant->addRequirement($req);
                    $em->persist($req);
                } catch (\Exception $e) { }
            }
        }

        $em->persist($applicant);
        $em->flush();

        return $this->redirectToRoute('app_enrollment_success', ['studentNumber' => $studentNo]);
    }

    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type)
    {
        $prefix = ($type === 'current') ? 'address' : 'perm';
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        $regionId = $request->request->get($type === 'current' ? 'region' : 'perm_region');
        $provId = $request->request->get($prefix . '_province');
        $cityId = $request->request->get($prefix . '_city');
        $brgyId = $request->request->get($prefix . '_barangay');

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
            $applicant->{'set'.$fieldPrefix.'Barangay'}($brgyId); 
        }

        $applicant->{'set'.$fieldPrefix.'Address'}($request->request->get($type === 'current' ? 'address' : 'perm_address'));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type === 'current' ? 'address_zip' : 'perm_zip'));
    }

    private function handleGuardian(ApplicantBed $applicant, Request $request, string $type, EntityManagerInterface $em)
    {
        $lname = $request->request->get($type . '_lastname');
        $fname = $request->request->get($type . '_firstname');
        $fullname = ($type === 'guardian') 
            ? $request->request->get('guardian_name') 
            : (($lname || $fname) ? "$lname, $fname" : '');

        if (empty($fullname) || $fullname === ', ') {
            return;
        }

        if ($type === 'father') $relationship = 'Father';
        elseif ($type === 'mother') $relationship = 'Mother';
        else $relationship = $request->request->get('guardian_relation') ?: 'Guardian';

        $guardian = null;
        foreach ($applicant->getGuardians() as $existingGuardian) {
            if (strcasecmp($existingGuardian->getRelationship(), $relationship) === 0) {
                $guardian = $existingGuardian;
                break;
            }
        }

        if (!$guardian) {
            $guardian = new ApplicantBedGuardian();
            $guardian->setApplicant($applicant);
            $guardian->setRelationship($relationship);
            $applicant->addGuardian($guardian);
        }

        $guardian->setParentName($fullname);
        $guardian->setOccupation($request->request->get($type . '_occupation'));
        $guardian->setContactNo($request->request->get($type . '_contact'));
        
        if ($request->request->get($type . '_deceased')) $guardian->setDeceased(true);
        if ($request->request->get($type . '_ofw')) $guardian->setOFW(true);

        $em->persist($guardian);
    }

    #[Route('/apply/success/{studentNumber}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $studentNumber, EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(ApplicantBed::class)->findOneBy(['studentNumber' => $studentNumber]);

        if (!$applicant) {
            throw $this->createNotFoundException('Application not found.');
        }

        return $this->render('enrollment-onsite/success.html.twig', [
            'student_number' => $applicant->getStudentNumber(),
            'student_name' => $applicant->getFirstName() . ' ' . $applicant->getLastName()
        ]);
    }

    #[Route('/api/check-lrn', name: 'app_enrollment_check_lrn', methods: ['GET'])]
    public function checkLrn(Request $request, EntityManagerInterface $em): Response
    {
        $lrn = $request->query->get('lrn');
        
        if (!$lrn) {
            return $this->json(['exists' => false]);
        }

        $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrn]);
        
        return $this->json(['exists' => $existing !== null]);
    }

}