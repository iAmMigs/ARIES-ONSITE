<?php

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedSibling;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedRequirement;
use App\Entity\DocumentSetup;
use App\Entity\LookupRegion;
use App\Entity\LookupProvince;
use App\Entity\LookupCity;
use App\Entity\LookupBarangay;
use App\Entity\LookupReligion;
use App\Entity\LookupCitizenship;
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
    public function apply(Request $request, EntityManagerInterface $em): Response
    {
        $campus = $request->query->get('campus');
        
        $documents = $em->getRepository(DocumentSetup::class)->findAll();
        
        $religions = $em->getRepository(LookupReligion::class)->findBy([], ['religionName' => 'ASC']);
        $citizenships = $em->getRepository(LookupCitizenship::class)->findBy([], ['citizenshipName' => 'ASC']);

        return $this->render('enrollment-onsite/enroll.html.twig', [
            'selected_campus' => $campus,
            'documents' => $documents,
            'religions' => $religions,
            'citizenships' => $citizenships
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

        $lrnInput = $request->request->get('lrn');
        if (!empty($lrnInput)) {
            $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrnInput]);
            if ($existing) {
                $this->addFlash('error', 'The provided LRN is already registered.');
                return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
            }
        }

        $applicant = new ApplicantBed();
        
        // --- 1. IDENTIFIERS ---
        $studentNo = $idGenerator->generateStudentNumber($campus, $yearStart);
        $applicant->setStudentNumber($studentNo);
        $applicant->setCampus($campus == 'feu_alabang' ? ApplicantBed::CAMPUS_ALABANG : ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setAdmissionDate(new \DateTime());

        // --- 2. ACADEMIC INFO ---
        $applicant->setEducationType($request->request->get('education_type'));
        $applicant->setGradeLevel($request->request->get('grade_level'));
        $applicant->setTrackStrand($request->request->get('strand'));
        $applicant->setLrn($lrnInput);
        $applicant->setSchoolYearOfEntry($yearStart . '-' . ($yearStart + 1));
        $applicant->setAdmissionType($request->request->get('admission_type'));

        // --- 3. PERSONAL INFORMATION ---
        $formatName = fn(?string $n) => $n ? strtoupper(trim($n)) : null;

        $applicant->setLastName($formatName($request->request->get('last_name')));
        $applicant->setFirstName($formatName($request->request->get('first_name')));
        $applicant->setMiddleName($formatName($request->request->get('middle_name')));
        $applicant->setExtensionName($formatName($request->request->get('suffix')));
        
        $applicant->setBirthDate(new \DateTime($request->request->get('birthday')));
        $applicant->setBirthPlace($formatName($request->request->get('birth_place')));
        $applicant->setGender($request->request->get('gender') == 'Male' ? ApplicantBed::GENDER_MALE : ApplicantBed::GENDER_FEMALE);
        
        $religion = $request->request->get('religion');
        if (strtoupper($religion) === 'OTHER') {
            $otherReligion = $request->request->get('other_religion');
            $applicant->setReligion($formatName($otherReligion) ?: 'OTHER');
        } else {
            $applicant->setReligion($religion);
        }
        
        $citizenship = $request->request->get('citizenship');
        $applicant->setCitizenship($citizenship);
        
        if (strtoupper($citizenship) !== 'FILIPINO') {
            $applicant->setPassportNumber($request->request->get('passport_number'));
            $applicant->setVisaType($request->request->get('visa_type'));
            $applicant->setVisaStatus($request->request->get('visa_status'));
        } else {
            $applicant->setIndigenousGroup($request->request->get('indigenous_group'));
        }

        // --- 4. CONTACT & ADDRESS ---
        $applicant->setMobileNumber($request->request->get('contact_number'));
        $applicant->setPersonalEmail($request->request->get('email'));
        $applicant->setLandLineNumber($request->request->get('landline'));

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

        // --- 5. FAMILY & EDUCATION ---
        $this->handleGuardian($applicant, $request, 'father', $em);
        $this->handleGuardian($applicant, $request, 'mother', $em);
        $this->handleGuardian($applicant, $request, 'guardian', $em);

        $siblingNames = $request->request->all()['sibling_name'] ?? [];
        $siblingSchools = $request->request->all()['sibling_school'] ?? [];
        $siblingStudentNos = $request->request->all()['sibling_student_no'] ?? [];
        if (is_array($siblingNames)) {
            foreach ($siblingNames as $index => $name) {
                if (!empty($name)) {
                    $sibling = new ApplicantBedSibling();
                    $sibling->setApplicant($applicant);
                    $sibling->setSiblingName($formatName($name));
                    $sibling->setSchool($siblingSchools[$index] ?? null);
                    $sibling->setFeuStudentNo($siblingStudentNos[$index] ?? null);
                    if (!empty($siblingStudentNos[$index])) $sibling->setIsFeuStudent(true);
                    $applicant->addSibling($sibling);
                    $em->persist($sibling);
                }
            }
        }

        if ($request->request->get('prev_school_name')) {
            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool($formatName($request->request->get('prev_school_name')));
            $school->setYearEnd((int)$request->request->get('prev_school_year'));
            $school->setLevel(ApplicantBedSchool::LEVEL_ELEMENTARY); 
            $applicant->addSchool($school);
            $em->persist($school);
        }

        // --- 6. ROBUST FILE UPLOADS ---
        
        // 6A. Process ID Picture
        $idFile = $request->files->get('req_id_picture');
        if ($idFile instanceof UploadedFile) {
            
            // Server-Side Image Format Validation
            $idExtension = strtolower($idFile->getClientOriginalExtension());
            if (!in_array($idExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $this->addFlash('error', 'Invalid ID picture format. Please upload a valid image (JPG, PNG, WEBP).');
                return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
            }
            
            // Server-Side Size Validation (5MB)
            if ($idFile->getSize() > 5242880) {
                $this->addFlash('error', 'ID picture must be strictly less than 5MB.');
                return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/onsite-id-pics';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'ID-' . $studentNo . '.' . $idFile->guessExtension();
            try {
                $idFile->move($uploadDir, $filename);
                $applicant->setPhotoSlug('uploads/onsite-id-pics/' . $filename);
            } catch (\Exception $e) { }
        }

        // 6B. Process Dynamic Document Requirements
        $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [$applicant->getCampus(), null]
        ]);
        
        foreach ($documentSetups as $docSetup) {
            $slug = $docSetup->getSlug();
            $file = $request->files->get($slug);
            
            if ($file instanceof UploadedFile) {
                
                // Server-Side Size Validation (10MB)
                if ($file->getSize() > 10485760) {
                    $this->addFlash('error', 'The document ' . $docSetup->getDocumentName() . ' exceeds the 10MB limit.');
                    return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
                }

                // Dynamic File Type Validation based on Admin Settings
                $allowedTypesString = $docSetup->getAllowedFileTypes();
                if (!empty($allowedTypesString)) {
                    $allowedExtensions = array_map('trim', explode(',', strtolower($allowedTypesString)));
                    $fileExtension = strtolower($file->getClientOriginalExtension());
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        $this->addFlash('error', 'Invalid file format for ' . $docSetup->getDocumentName() . '. Allowed formats: ' . strtoupper($allowedTypesString));
                        return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
                    }
                }

                $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $docSetup->getFolderName();
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $filename = strtoupper($slug) . '-' . $studentNo . '.' . $file->guessExtension();
                try {
                    $file->move($targetDir, $filename);
                    
                    $req = new ApplicantBedRequirement();
                    $req->setApplicant($applicant);
                    $req->setRequirement($docSetup->getDocumentName());
                    $req->setStoredFileName('uploads/' . $docSetup->getFolderName() . '/' . $filename);
                    $req->setSlug($slug);
                    $req->setStatus('S');
                    $req->setDateSubmitted(new \DateTime());
                    $req->setIsRequired($docSetup->isRequired());
                    $req->setIsDeleted(false);
                    
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

        $applicant->{'set'.$fieldPrefix.'Address'}(strtoupper($request->request->get($type === 'current' ? 'address' : 'perm_address')));
        $applicant->{'set'.$fieldPrefix.'Zip'}($request->request->get($type === 'current' ? 'address_zip' : 'perm_zip'));
    }

    private function handleGuardian(ApplicantBed $applicant, Request $request, string $slot, EntityManagerInterface $em)
    {
        $slotType = strtoupper($slot); 

        if ($slot === 'guardian') {
            $fullname = strtoupper(trim($request->request->get('guardian_name', '')));
            $relationship = strtoupper(trim($request->request->get('guardian_relation', 'GUARDIAN')));
            $occupation = '';
        } else {
            $lname = trim($request->request->get($slot . '_lastname', ''));
            $fname = trim($request->request->get($slot . '_firstname', ''));
            $mname = trim($request->request->get($slot . '_middlename', ''));
            
            $firstMid = trim(strtoupper(trim("$fname $mname")));
            $lname = strtoupper($lname);

            if ($lname && $firstMid) {
                $fullname = "$lname, $firstMid";
            } elseif ($lname) {
                $fullname = $lname;
            } elseif ($firstMid) {
                $fullname = $firstMid;
            } else {
                $fullname = '';
            }

            $relationship = strtoupper($slot);
            $occupation = strtoupper(trim($request->request->get($slot . '_occupation', '')));
        }

        if (empty($fullname) || $fullname === ',' || $fullname === ', ') return;

        $guardian = null;
        foreach ($applicant->getGuardians() as $g) {
            $dbType = $g->getGuardianType() ?: strtoupper($g->getRelationship());
            if ($dbType === $slotType || ($slotType === 'GUARDIAN' && !in_array($dbType, ['FATHER', 'MOTHER']))) {
                $guardian = $g;
                break;
            }
        }

        if (!$guardian) {
            $guardian = new ApplicantBedGuardian();
            $guardian->setApplicant($applicant);
            $guardian->setGuardianType($slotType); 
            $applicant->addGuardian($guardian);
        }

        $guardian->setParentName($fullname);
        $guardian->setRelationship($relationship);
        $guardian->setOccupation($occupation);
        $guardian->setContactNo($request->request->get($slot . '_contact', ''));
        $guardian->setDeceased($request->request->get($slot . '_deceased') ? true : false);
        $guardian->setOFW($request->request->get($slot . '_ofw') ? true : false);

        $em->persist($guardian);
    }

    #[Route('/apply/success/{studentNumber}', name: 'app_enrollment_success', methods: ['GET'])]
    public function success(string $studentNumber, EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(ApplicantBed::class)->findOneBy(['studentNumber' => $studentNumber]);
        if (!$applicant) throw $this->createNotFoundException('Application not found.');

        return $this->render('enrollment-onsite/success.html.twig', [
            'student_number' => $applicant->getStudentNumber(),
            'student_name' => $applicant->getFirstName() . ' ' . $applicant->getLastName()
        ]);
    }

    #[Route('/api/check-lrn', name: 'app_enrollment_check_lrn', methods: ['GET'])]
    public function checkLrn(Request $request, EntityManagerInterface $em): Response
    {
        $lrn = $request->query->get('lrn');
        if (!$lrn) return $this->json(['exists' => false]);
        $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrn]);
        return $this->json(['exists' => $existing !== null]);
    }
}