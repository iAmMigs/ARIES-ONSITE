<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedRequirement;
use App\Entity\DocumentSetup;
use App\Entity\SchoolYear;
use App\Repository\ApplicantBedRepository;
use App\Repository\SchoolYearRepository;
use App\Service\ApplicantDeletionService;
use App\Service\CampusDatabaseSwitcher;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:replace-diliman-applicants',
    description: 'Deletes 4 sample applicants in FEU Diliman and recreates them in the next active school year (SY2627) with optional fields left blank.'
)]
class ReplaceDilimanApplicantsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampusDatabaseSwitcher $dbSwitcher,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly ApplicantBedRepository $applicantRepository,
        private readonly StudentIdGenerator $idGenerator,
        private readonly ApplicantDeletionService $deletionService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Replace 4 FEU Diliman Applicants with SY2627 Minimal Applicants');

        // Switch to FEU Diliman database
        $this->dbSwitcher->switchToCampus('feu_diliman');

        // Find the next active/open school year (SY2627)
        $sy2627 = $this->schoolYearRepository->findOneBy([
            'campus' => SchoolYear::CAMPUS_DILIMAN,
            'label' => 'SY2627',
        ]);

        if (!$sy2627) {
            $io->error('School Year SY2627 not found for FEU Diliman.');
            return Command::FAILURE;
        }

        // Target 4 applicants to delete (Grades 9, 10, 11, 12 from SY2526 or existing SY2627)
        $targetIds = ['202550010', '202550011', '202550012', '202550013', '202650001', '202650002', '202650003', '202650004'];
        $io->section('1. Deleting Existing Applicants from FEU Diliman');

        foreach ($targetIds as $targetId) {
            $existing = $this->applicantRepository->find($targetId);
            if ($existing) {
                $io->writeln(sprintf('  Deleting applicant %s (%s, %s)...', $existing->getStudentNumber(), $existing->getGradeLevel(), $existing->getFullName()));
                $this->deletionService->deleteApplicant($existing);
            } else {
                $io->writeln(sprintf('  Applicant %s already absent, continuing...', $targetId));
            }
        }

        $this->em->clear();

        // 2. Recreate 4 replacement applicants in the next school year (SY2627) with optional fields left blank
        $io->section('2. Creating 4 Replacement Applicants in SY2627 with Optional Fields Blank');

        $replacementDefinitions = [
            [
                'grade' => 'Grade 9',
                'edu' => 'Junior High School',
                'strand' => null,
                'first' => 'MATEO',
                'last' => 'DELA CRUZ',
                'gender' => 'Male',
                'email' => 'mateo.delacruz.sy2627@gmail.com',
                'mobile' => '+63 917 111 2233',
            ],
            [
                'grade' => 'Grade 10',
                'edu' => 'Junior High School',
                'strand' => null,
                'first' => 'ISLA YSABELLE',
                'last' => 'REYES',
                'gender' => 'Female',
                'email' => 'isla.reyes.sy2627@gmail.com',
                'mobile' => '+63 918 222 3344',
            ],
            [
                'grade' => 'Grade 11',
                'edu' => 'Senior High School',
                'strand' => 'STEM',
                'first' => 'LUCAS',
                'last' => 'SANTOS',
                'gender' => 'Male',
                'email' => 'lucas.santos.sy2627@gmail.com',
                'mobile' => '+63 919 333 4455',
            ],
            [
                'grade' => 'Grade 12',
                'edu' => 'Senior High School',
                'strand' => 'ABM',
                'first' => 'SOFIA',
                'last' => 'GARCIA',
                'gender' => 'Female',
                'email' => 'sofia.garcia.sy2627@gmail.com',
                'mobile' => '+63 920 444 5566',
            ],
        ];

        $created = [];

        foreach ($replacementDefinitions as $def) {
            // Generate dashless student ID using SY2627 (prefix 20265)
            $studentNo = $this->idGenerator->generateStudentNumber('feu_diliman', $sy2627, false);

            $applicant = new ApplicantBed();
            $applicant->setStudentNumber($studentNo);
            $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
            $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
            $applicant->setAdmissionDate(new \DateTime());
            $applicant->setAdmissionType(ApplicantBed::TYPE_NEW_STUDENT);
            $applicant->setSchoolYearOfEntry($sy2627->getLabel());
            $applicant->setEducationType($def['edu']);
            $applicant->setGradeLevel($def['grade']);
            $applicant->setTrackStrand($def['strand']);

            // Required core applicant identity
            $applicant->setFirstName($def['first']);
            $applicant->setLastName($def['last']);
            $applicant->setGender($def['gender'] === 'Male' ? ApplicantBed::GENDER_MALE : ApplicantBed::GENDER_FEMALE);
            $applicant->setCitizenship('Filipino');
            $applicant->setNationality('Filipino');
            $applicant->setPersonalEmail($def['email']);
            $applicant->setMobileNumber($def['mobile']);
            $applicant->setBirthDate(new \DateTime('2009-06-15'));
            $applicant->setDocumentsAgreed(true);
            $applicant->setDocumentsAgreedDate($sy2627->getPromissoryDeadline() ?? new \DateTime('+60 days'));

            // EXPLICITLY LEAVE ALL OPTIONAL FIELDS BLANK (NULL)
            $applicant->setMiddleName(null);
            $applicant->setExtensionName(null);
            $applicant->setSuffix(null);
            $applicant->setPreferredName(null);
            $applicant->setLrn(null);
            $applicant->setBirthPlace(null);
            $applicant->setReligion(null);
            $applicant->setIndigenousGroup(null);
            $applicant->setLandLineNumber(null);
            $applicant->setPhotoSlug(null);
            $applicant->setCurrentRegion(null);
            $applicant->setCurrentProvince(null);
            $applicant->setCurrentCity(null);
            $applicant->setCurrentBarangay(null);
            $applicant->setCurrentAddress(null);
            $applicant->setCurrentZip(null);
            $applicant->setPermanentRegion(null);
            $applicant->setPermanentProvince(null);
            $applicant->setPermanentCity(null);
            $applicant->setPermanentBarangay(null);
            $applicant->setPermanentAddress(null);
            $applicant->setPermanentZip(null);
            $applicant->setPermanentCountry(null);
            $applicant->setCountryOfBirth(null);
            $applicant->setCountryOfResidence(null);
            $applicant->setLastGradeCompleted(null);
            $applicant->setGeneralAverage(null);
            $applicant->setMarketingSource(null);
            $applicant->setExaminationScore(null);
            $applicant->setExaminationDate(null);
            $applicant->setPassport(null);

            $this->em->persist($applicant);

            // --- FAMILY BACKGROUND (Father, Mother, Emergency Contact) ---
            // 1. Father
            $father = new ApplicantBedGuardian();
            $father->setApplicant($applicant);
            $father->setGuardianType('FATHER');
            $father->setRelationship('FATHER');
            $father->setParentName('ROBERTO ' . $def['last']);
            $father->setContactNo('+63 917 ' . rand(100, 999) . ' ' . rand(1000, 9999));
            $father->setEmail('roberto.' . strtolower(str_replace(' ', '', $def['last'])) . '@gmail.com');
            $father->setNationality('FILIPINO');
            $father->setOccupation('ENGINEER');
            $father->setDeceased(false);
            $father->setOFW(false);
            $father->setSameAsApplicant(true);
            $applicant->addGuardian($father);
            $this->em->persist($father);

            // 2. Mother
            $mother = new ApplicantBedGuardian();
            $mother->setApplicant($applicant);
            $mother->setGuardianType('MOTHER');
            $mother->setRelationship('MOTHER');
            $mother->setParentName('MARIA ELENA ' . $def['last']);
            $mother->setContactNo('+63 918 ' . rand(100, 999) . ' ' . rand(1000, 9999));
            $mother->setEmail('elena.' . strtolower(str_replace(' ', '', $def['last'])) . '@gmail.com');
            $mother->setNationality('FILIPINO');
            $mother->setOccupation('ACCOUNTANT');
            $mother->setDeceased(false);
            $mother->setOFW(false);
            $mother->setSameAsApplicant(true);
            $applicant->addGuardian($mother);
            $this->em->persist($mother);

            // 3. Emergency Contact (Guardian)
            $guardian = new ApplicantBedGuardian();
            $guardian->setApplicant($applicant);
            $guardian->setGuardianType('GUARDIAN');
            $guardian->setRelationship('GUARDIAN');
            $guardian->setParentName('TERESA ' . $def['last']);
            $guardian->setContactNo($def['mobile']);
            $guardian->setEmail($def['email']);
            $guardian->setNationality('FILIPINO');
            $guardian->setOccupation('HUMAN RESOURCES MANAGER');
            $guardian->setDeceased(false);
            $guardian->setOFW(false);
            $guardian->setSameAsApplicant(true);
            $applicant->addGuardian($guardian);
            $this->em->persist($guardian);

            // Requirements: Seed compliant requirement rows matching active document setups
            $setups = $this->em->getRepository(DocumentSetup::class)->findBy([
                'campus' => [ApplicantBed::CAMPUS_DILIMAN, null],
            ]);

            foreach ($setups as $ds) {
                $grades = $ds->getGradeLevels() ?? [];
                if (!empty($grades)) {
                    $normG = array_map(fn($x) => strtolower(str_replace([' ', '_', '-'], '', (string)$x)), $grades);
                    $currG = strtolower(str_replace([' ', '_', '-'], '', $def['grade']));
                    if (!in_array('all', $normG, true) && !in_array($currG, $normG, true)) {
                        continue;
                    }
                }

                if (in_array($ds->getSlug(), ['passport_bio', 'valid_visa', 'acr_icard', 'special_study_permit', 'ph_birth_certificate', 'english_translations', 'parent_passport_id', 'proof_of_residency', 'medical_record'], true)) {
                    continue;
                }

                $req = new ApplicantBedRequirement();
                $req->setApplicant($applicant);
                $req->setSlug($ds->getSlug());
                $req->setRequirement($ds->getDocumentName());
                $req->setStatus(ApplicantBedRequirement::STATUS_PENDING);
                $req->setDateSubmitted(null);
                $req->setStoredFileName(null);
                $applicant->addRequirement($req);
                $this->em->persist($req);
            }

            $this->em->flush();

            $created[] = [
                'campus' => 'FEU Diliman',
                'student_no' => $applicant->getStudentNumber(),
                'name' => $applicant->getFullName(),
                'sy' => $applicant->getSchoolYearOfEntry(),
                'grade' => $applicant->getGradeLevel(),
                'strand' => $applicant->getTrackStrand() ?? 'N/A',
                'optional_fields' => 'All Blank (NULL)',
            ];

            $io->writeln(sprintf('  Created %s: %s - %s (SY: %s, Strand: %s) [Optional Fields: Blank]',
                $applicant->getGradeLevel(),
                $applicant->getStudentNumber(),
                $applicant->getFullName(),
                $applicant->getSchoolYearOfEntry(),
                $applicant->getTrackStrand() ?? 'N/A'
            ));
        }

        $io->newLine();
        $io->success(sprintf('Successfully deleted 4 applicants and recreated 4 replacement applicants in %s with blank optional fields and dashless IDs!', $sy2627->getLabel()));

        $io->table(
            ['Campus', 'Student ID', 'Full Name', 'School Year', 'Grade Level', 'Track/Strand', 'Optional Fields Status'],
            $created
        );

        return Command::SUCCESS;
    }
}

