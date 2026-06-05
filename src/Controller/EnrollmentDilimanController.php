<?php

declare(strict_types=1);

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
use App\Entity\LookupCountry;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/diliman/apply')]
class EnrollmentDilimanController extends AbstractController
{
    #[Route('', name: 'app_enrollment_diliman_apply', methods: ['GET'])]
    public function apply(Request $request, EntityManagerInterface $em, SchoolYearRepository $syRepo): Response
    {
        $campus = 'feu_diliman';
        $campusCode = SchoolYear::CAMPUS_DILIMAN;

        $activeSY = $syRepo->findActiveByCampus($campusCode);
        if (!$activeSY || !$activeSY->isEnrollmentOpen()) {
            return $this->render('enrollment-onsite/diliman/enrollment_closed.html.twig', [
                'campus' => $campus,
                'activeSY' => $activeSY,
            ]);
        }
        
        $documents    = $em->getRepository(DocumentSetup::class)->findBy(['campus' => [$campusCode, null]]);
        $religions    = $em->getRepository(LookupReligion::class)->findBy([], ['religionName' => 'ASC']);
        $citizenships = $em->getRepository(LookupCitizenship::class)->findBy([], ['citizenshipName' => 'ASC']);
        $nationalities = $em->getRepository(LookupCitizenship::class)->findBy([], ['citizenshipName' => 'ASC']);
        $countries    = $em->getRepository(LookupCountry::class)->findBy([], ['countryName' => 'ASC']);

        return $this->render('enrollment-onsite/diliman/enroll.html.twig', [
            'selected_campus'        => $campus,
            'active_sy'              => $activeSY,
            'documents'              => $documents,
            'religions'              => $religions,
            'citizenships'           => $citizenships,
            'nationalities'          => $nationalities,
            'countries'              => $countries,
        ]);
    }

    #[Route('/submit', name: 'app_enrollment_diliman_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $em,
        StudentIdGenerator $idGenerator,
        SchoolYearRepository $syRepo,
        \Psr\Log\LoggerInterface $logger
    ): Response
    {
        // Log the incoming request data for debugging
        $logger->info('Enrollment Submission POST data (Diliman): ' . json_encode($request->request->all()));
        $logger->info('Enrollment Submission FILES data (Diliman): ' . json_encode(array_keys($request->files->all())));

        $campus = 'feu_diliman';
        $campusCode = SchoolYear::CAMPUS_DILIMAN;
        $activeSY = $syRepo->findActiveByCampus($campusCode);

        if (!$activeSY || !$activeSY->isEnrollmentOpen()) {
            error_log("Enrollment Redirect: Enrollment is closed for FDILI (Open: " . ($activeSY ? ($activeSY->isEnrollmentOpen() ? 'YES' : 'NO') : 'SY NOT FOUND') . ")");
            $this->addFlash('error', 'Enrollment is currently closed.');
            return $this->redirectToRoute('app_enrollment_diliman_apply');
        }

        $lrnInput = $request->request->get('lrn');
        if (!empty($lrnInput)) {
            $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrnInput]);
            if ($existing) {
                error_log("Enrollment Redirect: LRN $lrnInput already exists in the system (Diliman).");
                $this->addFlash('error', 'The provided LRN is already registered.');
                return $this->redirectToRoute('app_enrollment_diliman_apply');
            }
        }

        $em->beginTransaction();
        try {
            $applicant = new ApplicantBed();
            
            // --- PRE-CHECK MISSING DOCS ---
            $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
                'campus' => [$campusCode, null]
            ]);

            $missingDocs = [];
            $tempAdmType = $request->request->get('admission_type', '');
            $tempNat = strtoupper($request->request->get('citizenship', ''));
            $tempGrade = $request->request->get('grade_level', '');

            $processedPrecheckSlugs = [];
            foreach ($documentSetups as $docSetup) {
                $isMatch = true;
                if ($docSetup->getStudentType() && strtoupper($docSetup->getStudentType()) !== strtoupper($tempAdmType)) $isMatch = false;
                if ($docSetup->getNationalityType() && strtoupper($docSetup->getNationalityType()) !== $tempNat) $isMatch = false;
                if ($docSetup->getGradeLevels() && !in_array($tempGrade, $docSetup->getGradeLevels())) $isMatch = false;
                
                if (!$isMatch) continue;

                $slug = $docSetup->getSlug();
                if (in_array($slug, $processedPrecheckSlugs)) continue;
                $processedPrecheckSlugs[] = $slug;

                $file = $request->files->get($slug);
                if (!$file instanceof UploadedFile) {
                    $missingDocs[] = $docSetup->getDocumentName();
                }
            }

            if (count($missingDocs) > 0) {
                // If there are missing docs, we assume the user signed the waiver modal if they got here.
                // We no longer require a tentative date.
                $applicant->setDocumentsAgreed(true);
                $applicant->setDocumentsAgreedDate($activeSY->getPromissoryDeadline());
            }

            // --- ALL CLEAR, GENERATE STUDENT NUMBER ---
            $studentNo = $idGenerator->generateStudentNumber($campus, $activeSY);
            $applicant->setStudentNumber($studentNo);
            
            // Persist the applicant immediately so that child entities (with derived identities) can safely map their ManyToOne primary keys.
            $em->persist($applicant);
            
            $applicant->setCampus($campusCode);
            $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            $applicant->setAdmissionDate(new \DateTime());

            $applicant->setEducationType($request->request->get('education_type'));
            $applicant->setGradeLevel($request->request->get('grade_level'));
            $applicant->setTrackStrand($request->request->get('strand'));
            $applicant->setLrn($lrnInput !== '' ? $lrnInput : null);
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
            $applicant->setNationality($request->request->get('nationality'));
            
            if (strtoupper($citizenship) === 'INTERNATIONAL') {
                $applicant->setPassportNumber($request->request->get('passport_number'));
                $applicant->setVisaType($request->request->get('visa_type'));
                $applicant->setVisaStatus($request->request->get('visa_status'));
            }

            $marketingSource = $request->request->get('marketing_source');
            if ($marketingSource === 'Other') {
                $marketingSource = $request->request->get('marketing_source_other');
            }
            $applicant->setMarketingSource($marketingSource);

            // Waiver check already performed earlier.

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
            
            $processedReqSlugs = [];
            foreach ($documentSetups as $docSetup) {
                $isMatch = true;
                if ($docSetup->getStudentType() && strtoupper($docSetup->getStudentType()) !== strtoupper($applicant->getAdmissionType())) $isMatch = false;
                $studentNationality = strtoupper($applicant->getCitizenship());
                if ($docSetup->getNationalityType() && strtoupper($docSetup->getNationalityType()) !== $studentNationality) $isMatch = false;
                if ($docSetup->getGradeLevels() && !in_array($applicant->getGradeLevel(), $docSetup->getGradeLevels())) $isMatch = false;
                
                if (!$isMatch) continue;

                $slug = $docSetup->getSlug();
                if (in_array($slug, $processedReqSlugs)) continue;
                $processedReqSlugs[] = $slug;
                
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

                    $ext = $file->guessExtension() ?: $file->getClientOriginalExtension();
                    $filename = strtoupper($slug) . '-' . $studentNo . '.' . $ext;
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

            $em->flush();
            $em->commit();

            return $this->redirectToRoute('app_enrollment_diliman_success', ['studentNumber' => $studentNo]);

        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $em->rollback();
            $logger->error('Enrollment DB Constraint Error (Diliman): ' . $e->getMessage());
            $this->addFlash('error', 'Submission failed: A record with this unique information already exists.');
            return $this->redirectToRoute('app_enrollment_diliman_apply');
        } catch (\Exception $e) {
            $em->rollback();
            // Log the error for debugging
            $logger->error('Enrollment Submission Error (Diliman): ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'studentNo' => $studentNo ?? 'unknown'
            ]);

            $this->addFlash('error', 'Submission failed: ' . $e->getMessage());
            return $this->redirectToRoute('app_enrollment_diliman_apply');
        }
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

        // --- Address handling ---
        if ($slot === 'guardian') {
            // Guardian slot: check "Same as Applicant" flag
            $sameAsApplicant = $request->request->get('guardian_same_as_applicant') ? true : false;
            $guardian->setSameAsApplicant($sameAsApplicant);

            if ($sameAsApplicant) {
                // Copy applicant's addresses to guardian
                $guardian->setCurrentRegion($applicant->getCurrentRegion());
                $guardian->setCurrentProvince($applicant->getCurrentProvince());
                $guardian->setCurrentCity($applicant->getCurrentCity());
                $guardian->setCurrentBarangay($applicant->getCurrentBarangay());
                $guardian->setCurrentAddress($applicant->getCurrentAddress());
                $guardian->setCurrentZip($applicant->getCurrentZip());
                $guardian->setPermanentRegion($applicant->getPermanentRegion());
                $guardian->setPermanentProvince($applicant->getPermanentProvince());
                $guardian->setPermanentCity($applicant->getPermanentCity());
                $guardian->setPermanentBarangay($applicant->getPermanentBarangay());
                $guardian->setPermanentAddress($applicant->getPermanentAddress());
                $guardian->setPermanentZip($applicant->getPermanentZip());
            } else {
                // Guardian's own current address
                $guardian->setCurrentRegion(strtoupper($request->request->get('guardian_addr_region', '') ?: ''));
                $guardian->setCurrentProvince(strtoupper($request->request->get('guardian_addr_province', '') ?: ''));
                $guardian->setCurrentCity(strtoupper($request->request->get('guardian_addr_city', '') ?: ''));
                $guardian->setCurrentBarangay($request->request->get('guardian_addr_barangay', ''));
                $guardian->setCurrentAddress(strtoupper($request->request->get('guardian_addr_street', '') ?: ''));
                $guardian->setCurrentZip($request->request->get('guardian_addr_zip', ''));

                // Guardian's permanent address
                $permSame = $request->request->get('guardian_perm_same');
                if ($permSame) {
                    $guardian->setPermanentRegion($guardian->getCurrentRegion());
                    $guardian->setPermanentProvince($guardian->getCurrentProvince());
                    $guardian->setPermanentCity($guardian->getCurrentCity());
                    $guardian->setPermanentBarangay($guardian->getCurrentBarangay());
                    $guardian->setPermanentAddress($guardian->getCurrentAddress());
                    $guardian->setPermanentZip($guardian->getCurrentZip());
                } else {
                    $guardian->setPermanentRegion(strtoupper($request->request->get('guardian_perm_region', '') ?: ''));
                    $guardian->setPermanentProvince(strtoupper($request->request->get('guardian_perm_province', '') ?: ''));
                    $guardian->setPermanentCity(strtoupper($request->request->get('guardian_perm_city', '') ?: ''));
                    $guardian->setPermanentBarangay($request->request->get('guardian_perm_barangay', ''));
                    $guardian->setPermanentAddress(strtoupper($request->request->get('guardian_perm_street', '') ?: ''));
                    $guardian->setPermanentZip($request->request->get('guardian_perm_zip', ''));
                }
            }
        } else {
            // Father/Mother: personal/contact only (no address collection)
            $guardian->setSameAsApplicant(false);
            $guardian->setCurrentRegion(null);
            $guardian->setCurrentProvince(null);
            $guardian->setCurrentCity(null);
            $guardian->setCurrentBarangay(null);
            $guardian->setCurrentAddress(null);
            $guardian->setCurrentZip(null);
            $guardian->setPermanentRegion(null);
            $guardian->setPermanentProvince(null);
            $guardian->setPermanentCity(null);
            $guardian->setPermanentBarangay(null);
            $guardian->setPermanentAddress(null);
            $guardian->setPermanentZip(null);
        }

        $em->persist($guardian);
    }

    #[Route('/success/{studentNumber}', name: 'app_enrollment_diliman_success', methods: ['GET'])]
    public function success(string $studentNumber, EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(ApplicantBed::class)->findOneBy(['studentNumber' => $studentNumber]);
        if (!$applicant) throw $this->createNotFoundException('Application not found.');

        return $this->render('enrollment-onsite/diliman/success.html.twig', [
            'student_number' => $applicant->getStudentNumber(),
            'student_name' => $applicant->getFirstName() . ' ' . $applicant->getLastName(),
            'education_type' => $applicant->getEducationType(),
        ]);
    }

    #[Route('/api/check-lrn', name: 'app_enrollment_diliman_check_lrn', methods: ['GET'])]
    public function checkLrn(Request $request, EntityManagerInterface $em): Response
    {
        $lrn = $request->query->get('lrn');
        if (!$lrn) return $this->json(['exists' => false]);
        $existing = $em->getRepository(ApplicantBed::class)->findOneBy(['lrn' => $lrn]);
        return $this->json(['exists' => $existing !== null]);
    }



    private function hasMissingRequiredDocs(ApplicantBed $applicant, EntityManagerInterface $em): bool
    {
        return count($this->getMissingRequiredDocs($applicant, $em)) > 0;
    }

    private function getMissingRequiredDocs(ApplicantBed $applicant, EntityManagerInterface $em): array
    {
        $documentSetups = $em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [$applicant->getCampus(), null]
        ]);
        
        $submittedSlugs = [];
        foreach ($applicant->getRequirements() as $req) {
            if (!$req->getIsDeleted() && $req->getStoredFileName()) {
                $submittedSlugs[] = $req->getSlug();
            }
        }
        
        $missingDocs = [];
        foreach ($documentSetups as $docSetup) {
            $isMatch = true;
            if ($docSetup->getStudentType() && strtoupper($docSetup->getStudentType()) !== strtoupper($applicant->getAdmissionType())) $isMatch = false;
            $studentNationality = strtoupper($applicant->getCitizenship());
            if ($docSetup->getNationalityType() && strtoupper($docSetup->getNationalityType()) !== $studentNationality) $isMatch = false;
            if ($docSetup->getGradeLevels() && !in_array($applicant->getGradeLevel(), $docSetup->getGradeLevels())) $isMatch = false;
            
            if (!$isMatch) continue;

            if (!in_array($docSetup->getSlug(), $submittedSlugs)) {
                $missingDocs[] = $docSetup->getDocumentName();
            }
        }
        
        return $missingDocs;
    }
}
