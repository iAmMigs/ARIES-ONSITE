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
use App\Entity\LookupCountry;
use App\Entity\LookupCitizenship;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alabang/apply')]
class EnrollmentAlabangController extends AbstractController
{
    #[Route('', name: 'app_enrollment_alabang_apply', methods: ['GET'])]
    public function apply(Request $request, EntityManagerInterface $em, SchoolYearRepository $syRepo): Response
    {
        $campus = 'feu_alabang';
        $campusCode = SchoolYear::CAMPUS_ALABANG;

        $activeSY = $syRepo->findActiveByCampus($campusCode);
        if (!$activeSY || !$activeSY->isEnrollmentOpen()) {
            return $this->render('enrollment-onsite/alabang/enrollment_closed.html.twig', [
                'campus' => $campus,
                'activeSY' => $activeSY,
            ]);
        }
        
        $documents    = $em->getRepository(DocumentSetup::class)->findBy(['campus' => [$campusCode, null]]);
        $religions    = $em->getRepository(LookupReligion::class)->findBy([], ['religionName' => 'ASC']);
        $citizenships = $em->getRepository(LookupCitizenship::class)->findBy([], ['citizenshipName' => 'ASC']);
        $countries    = $em->getRepository(LookupCountry::class)->findBy([], ['countryName' => 'ASC']);

        return $this->render('enrollment-onsite/alabang/enroll.html.twig', [
            'selected_campus'        => $campus,
            'active_sy'              => $activeSY,
            'documents'              => $documents,
            'religions'              => $religions,
            'citizenships'           => $citizenships,
            'countries'              => $countries,
        ]);
    }

    #[Route('/submit', name: 'app_enrollment_alabang_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        StudentIdGenerator $idGenerator,
        SchoolYearRepository $syRepo
    ): Response
    {
        // Log the incoming request data for debugging
        error_log('Enrollment Submission POST data: ' . json_encode($request->request->all()));
        error_log('Enrollment Submission FILES data: ' . json_encode(array_keys($request->files->all())));

        $campus = 'feu_alabang';
        $campusCode = SchoolYear::CAMPUS_ALABANG;
        $activeSY = $syRepo->findActiveByCampus($campusCode);

        if (!$activeSY || !$activeSY->isEnrollmentOpen()) {
            error_log("Enrollment Redirect: Enrollment is closed for FALAB (Open: " . ($activeSY ? ($activeSY->isEnrollmentOpen() ? 'YES' : 'NO') : 'SY NOT FOUND') . ")");
            $this->addFlash('error', 'Enrollment is currently closed.');
        }

        $lrnInput = $request->request->get('lrn');
        
        if (!empty($lrnInput)) {
            $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrnInput]);
            if ($existing) {
                error_log("Enrollment Redirect: LRN $lrnInput already exists in the system.");
                $this->addFlash('error', 'The provided LRN is already registered.');
                return $this->redirectToRoute('app_enrollment_alabang_apply');
            }
        }

        $em->beginTransaction();
        try {
            $applicant = new ApplicantBed();
            
            $studentNo = $idGenerator->generateStudentNumber($campus, $activeSY);
            $applicant->setStudentNumber($studentNo);
            $applicant->setCampus($campusCode);
            $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            $applicant->setAdmissionDate(new \DateTime());

            $applicant->setEducationType($request->request->get('education_type'));
            $applicant->setGradeLevel($request->request->get('grade_level'));
            $applicant->setTrackStrand($request->request->get('strand'));
            $applicant->setLrn($lrnInput);
            $applicant->setSchoolYearOfEntry($activeSY->getLabel());
            $applicant->setAdmissionType($request->request->get('admission_type'));

            $formatName = fn(?string $n) => $n ? strtoupper(trim($n)) : null;

            $applicant->setLastName($formatName($request->request->get('last_name')));
            $applicant->setFirstName($formatName($request->request->get('first_name')));
            $applicant->setMiddleName($formatName($request->request->get('middle_name')));
            $applicant->setExtensionName($formatName($request->request->get('suffix')));
            
            if ($birthday = $request->request->get('birthday')) {
                $applicant->setBirthDate(new \DateTime($birthday));
            }
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
            }

            $agreedDateStr = $request->request->get('documents_agreed_date');
            if (!empty($agreedDateStr)) {
                $applicant->setDocumentsAgreedDate(new \DateTime($agreedDateStr));
                $applicant->setDocumentsAgreed(true);
            }

            $applicant->setMobileNumber($request->request->get('contact_number'));
            $applicant->setPersonalEmail($request->request->get('email'));
            $applicant->setLandLineNumber($request->request->get('landline'));

            error_log("Enrollment Alabang: Hydrating Address for " . $applicant->getLastName());
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

            $levels = ['kinder', 'elem', 'jhs', 'shs'];
            foreach ($levels as $lvl) {
                $schools = $request->request->all()['educ_' . $lvl . '_school'] ?? [];
                $years = $request->request->all()['educ_' . $lvl . '_year'] ?? [];
                $levelLabels = $request->request->all()['educ_' . $lvl . '_level'] ?? [];
                $types = $request->request->all()['educ_' . $lvl . '_type'] ?? [];
                
                $isInternationals = $request->request->all()['educ_' . $lvl . '_is_international'] ?? [];
                $countries = $request->request->all()['educ_' . $lvl . '_country'] ?? [];
                $regions = $request->request->all()['educ_' . $lvl . '_region'] ?? [];
                $provinces = $request->request->all()['educ_' . $lvl . '_province'] ?? [];
                $cities = $request->request->all()['educ_' . $lvl . '_city'] ?? [];

                if (is_array($schools)) {
                    foreach ($schools as $index => $schoolName) {
                        if (!empty($schoolName)) {
                            $school = new ApplicantBedSchool();
                            $school->setApplicant($applicant);
                            $school->setSchool($formatName($schoolName));
                            $school->setSchoolYear($years[$index] ?? null);
                            $school->setSchoolType($types[$index] ?? null);
                            $school->setLevel($levelLabels[$index] ?? match($lvl) {
                                'kinder' => 'Kindergarten',
                                'elem' => 'Elementary',
                                'jhs' => 'Junior High School',
                                'shs' => 'Senior High School',
                                default => strtoupper($lvl)
                            });
                            
                            $isInt = !empty($isInternationals[$index]);
                            $school->setIsInternational($isInt);
                            if ($isInt) {
                                $school->setCountry($countries[$index] ?? null);
                            } else {
                                $school->setRegion($regions[$index] ?? null);
                                $school->setProvince($provinces[$index] ?? null);
                                $school->setCity($cities[$index] ?? null);
                            }
                            
                            $applicant->addSchool($school);
                            $em->persist($school);
                        }
                    }
                }
            }

            $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
                'campus' => [$applicant->getCampus(), null]
            ]);
            
            foreach ($documentSetups as $docSetup) {
                $isMatch = true;
                if ($docSetup->getStudentType() && strtoupper($docSetup->getStudentType()) !== strtoupper($applicant->getAdmissionType())) $isMatch = false;
                $studentNationality = strtoupper($applicant->getCitizenship()) === 'FILIPINO' ? 'LOCAL' : 'INTERNATIONAL';
                if ($docSetup->getNationalityType() && strtoupper($docSetup->getNationalityType()) !== $studentNationality) $isMatch = false;
                if ($docSetup->getGradeLevels() && !in_array($applicant->getGradeLevel(), $docSetup->getGradeLevels())) $isMatch = false;
                
                if (!$isMatch) continue;

                $slug = $docSetup->getSlug();
                $file = $request->files->get($slug);
                
                if ($file instanceof UploadedFile) {
                    if ($file->getSize() > 10485760) throw new \Exception('File ' . $docSetup->getDocumentName() . ' exceeds 10MB limit.');

                    $allowedTypesString = $docSetup->getAllowedFileTypes();
                    if (!empty($allowedTypesString)) {
                        $allowedExtensions = array_map('trim', explode(',', strtolower($allowedTypesString)));
                        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
                            throw new \Exception('Invalid format for ' . $docSetup->getDocumentName());
                        }
                    }

                    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $docSetup->getFolderName();
                    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

                    $filename = strtoupper($slug) . '-' . $studentNo . '.' . $file->guessExtension();
                    $file->move($targetDir, $filename);
                    
                    $req = new ApplicantBedRequirement();
                    $req->setApplicant($applicant);
                    $req->setRequirement($docSetup->getDocumentName());
                    $req->setStoredFileName('uploads/' . $docSetup->getFolderName() . '/' . $filename);
                    $req->setSlug($slug);
                    $req->setStatus('S');
                    $req->setDateSubmitted(new \DateTime());
                    $req->setIsDeleted(false);
                    
                    $applicant->addRequirement($req);
                    $em->persist($req);
                }
            }

            $em->persist($applicant);
            $em->flush();
            $em->commit();

            return $this->redirectToRoute('app_enrollment_alabang_success', ['studentNumber' => $studentNo]);

        } catch (\Throwable $e) {
            $em->rollback();
            // Log the error for debugging
            error_log('Enrollment Submission Error (Alabang): ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $this->addFlash('error', 'Submission failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_enrollment_alabang_apply');
        }
    }

    private function hydrateAddress(ApplicantBed $applicant, Request $request, EntityManagerInterface $em, string $type)
    {
        $prefix = ($type === 'current') ? 'address' : 'perm';
        $fieldPrefix = ($type === 'current') ? 'Current' : 'Permanent';

        // Update to read region based on province if not set, but the UI might send it or handle it.
        $regionId = $request->request->get($type === 'current' ? 'region' : 'perm_region');
        // Actually, the new UI uses a text field for province. But wait, if it auto-selects, it still sends ID or value.
        // Let's assume it sends region value directly. Wait, the old UI used IDs?
        // LookupRegion code was regionCode. Let's just lookup by name if it's not a code, or just keep it as is.
        // I will change the UI to send the code or name. If it's the code, it uses findOneBy.
        $provId = $request->request->get($prefix . '_province');
        $cityId = $request->request->get($prefix . '_city');
        $brgyId = $request->request->get($prefix . '_barangay');

        if($regionId) {
            $r = $em->getRepository(LookupRegion::class)->findOneBy(['regionCode' => $regionId]);
            if($r) $applicant->{'set'.$fieldPrefix.'Region'}($r->getRegionDesc());
            else $applicant->{'set'.$fieldPrefix.'Region'}(strtoupper($regionId));
        }
        if($provId) {
            $p = $em->getRepository(LookupProvince::class)->findOneBy(['provinceCode' => $provId]);
            if($p) $applicant->{'set'.$fieldPrefix.'Province'}($p->getProvinceDesc());
            else $applicant->{'set'.$fieldPrefix.'Province'}(strtoupper($provId));
        }
        if($cityId) {
            $c = $em->getRepository(LookupCity::class)->findOneBy(['cityCode' => $cityId]);
            if($c) $applicant->{'set'.$fieldPrefix.'City'}($c->getCityDesc());
            else $applicant->{'set'.$fieldPrefix.'City'}(strtoupper($cityId));
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
        $guardian->setOfwCountry($request->request->get($slot . '_ofw_country'));
        $guardian->setEmail($request->request->get($slot . '_email'));
        $guardian->setAddress($request->request->get($slot . '_address'));

        $em->persist($guardian);
    }

    #[Route('/success/{studentNumber}', name: 'app_enrollment_alabang_success', methods: ['GET'])]
    public function success(string $studentNumber, EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(ApplicantBed::class)->findOneBy(['studentNumber' => $studentNumber]);
        if (!$applicant) throw $this->createNotFoundException('Application not found.');

        return $this->render('enrollment-onsite/alabang/success.html.twig', [
            'student_number' => $applicant->getStudentNumber(),
            'student_name' => $applicant->getFirstName() . ' ' . $applicant->getLastName()
        ]);
    }

    #[Route('/api/check-lrn', name: 'app_enrollment_alabang_check_lrn', methods: ['GET'])]
    public function checkLrn(Request $request, EntityManagerInterface $em): Response
    {
        $lrn = $request->query->get('lrn');
        if (!$lrn) return $this->json(['exists' => false]);
        $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrn]);
        return $this->json(['exists' => $existing !== null]);
    }
}
