<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedPassport;
use App\Entity\ApplicantBedRequirement;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
use App\Entity\DocumentSetup;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates a complete sample applicant with random data across all fields
 * including middle name, siblings, guardians, education history, and documents for FEU Diliman.
 */
#[AsCommand(
    name: 'app:generate-diliman-sample',
    description: 'Generates 1 complete sample applicant with random data for all fields including sibling and middle name for FEU Diliman',
)]
class GenerateDilimanApplicantCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly StudentIdGenerator $idGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $syDiliman = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_DILIMAN);
        if (!$syDiliman) {
            $io->error('No active school year found for FEU Diliman (FDIL).');
            return Command::FAILURE;
        }

        $io->title('Generating 1 Sample Applicant for FEU Diliman with All Fields');

        $maleFirstNames = ['Gabriel', 'Mateo', 'Lucas', 'Joaquin', 'Rafael', 'Christian', 'Daniel', 'Julian'];
        $femaleFirstNames = ['Sofia', 'Isabella', 'Camilla', 'Beatriz', 'Marian', 'Danielle', 'Alyssa', 'Patricia'];
        $middleNames = ['Valenzuela', 'Villanueva', 'Santiago', 'Fernandez', 'Del Rosario', 'Mercado', 'Roxas', 'Castillo'];
        $lastNames = ['Bautista', 'Mendoza', 'Navarro', 'Soriano', 'Aquino', 'Concepcion', 'Villamor', 'Salazar'];

        $isMale = (bool) random_int(0, 1);
        $gender = $isMale ? ApplicantBed::GENDER_MALE : ApplicantBed::GENDER_FEMALE;
        $firstName = $isMale ? $maleFirstNames[array_rand($maleFirstNames)] : $femaleFirstNames[array_rand($femaleFirstNames)];
        $middleName = $middleNames[array_rand($middleNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $nicknames = ['Gabe', 'Matt', 'Luke', 'Sofi', 'Bella', 'Rafa', 'Dani', 'Patti'];
        $preferredName = $nicknames[array_rand($nicknames)];

        $streetNames = ['Commonwealth Avenue', 'Katipunan Avenue', 'Tandang Sora Avenue', 'Kalayaan Avenue', 'Don Antonio Street'];
        $currentAddress = 'Block ' . random_int(1, 40) . ' Lot ' . random_int(1, 30) . ' ' . $streetNames[array_rand($streetNames)];

        $applicant = new ApplicantBed();
        $studentNo = $this->idGenerator->generateStudentNumber('feu_diliman', $syDiliman, false);
        $applicant->setStudentNumber($studentNo);
        $this->em->persist($applicant);

        // Core applicant identity & campus
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setAdmissionType('New Student');
        $applicant->setAdmissionDate(new \DateTime());
        $applicant->setSchoolYearOfEntry($syDiliman->getLabel());

        // Academic details
        $applicant->setEducationType('Senior High School');
        $applicant->setGradeLevel('Grade 11');
        $applicant->setTrackStrand('STEM');
        $applicant->setLrn('109' . random_int(100000000, 999999999));

        // Name fields
        $applicant->setLastName($lastName);
        $applicant->setFirstName($firstName);
        $applicant->setMiddleName($middleName);
        $applicant->setPreferredName($preferredName);
        $applicant->setSuffix(random_int(0, 1) === 1 ? 'Jr.' : null);
        $applicant->setExtensionName($applicant->getSuffix());

        // Personal demographics
        $applicant->setBirthDate(new \DateTime('-' . random_int(15, 17) . ' years'));
        $applicant->setBirthPlace('Quezon City');
        $applicant->setGender($gender);
        $applicant->setReligion('Roman Catholic');
        $applicant->setCitizenship('LOCAL');
        $applicant->setNationality('Filipino');
        $applicant->setCountryOfBirth('Philippines');
        $applicant->setCountryOfResidence('Philippines');
        $applicant->setPermanentCountry('Philippines');
        $applicant->setIndigenousGroup(null);

        // Contact information
        $applicant->setMobileNumber('+63 9' . random_int(10000000, 99999999));
        $applicant->setLandLineNumber('028' . random_int(1000000, 9999999));
        $applicant->setPersonalEmail(strtolower($firstName . '.' . $lastName . random_int(10, 99) . '@example.com'));

        // Current & Permanent Addresses
        $applicant->setCurrentRegion('National Capital Region (NCR)');
        $applicant->setCurrentProvince('Metro Manila');
        $applicant->setCurrentCity('Quezon City');
        $applicant->setCurrentBarangay('Diliman');
        $applicant->setCurrentAddress($currentAddress);
        $applicant->setCurrentZip('1101');

        $applicant->setPermanentRegion($applicant->getCurrentRegion());
        $applicant->setPermanentProvince($applicant->getCurrentProvince());
        $applicant->setPermanentCity($applicant->getCurrentCity());
        $applicant->setPermanentBarangay($applicant->getCurrentBarangay());
        $applicant->setPermanentAddress($applicant->getCurrentAddress());
        $applicant->setPermanentZip($applicant->getCurrentZip());

        // Photo, Academics & Examination
        $applicant->setPhotoSlug('metronic/media/avatars/300-' . random_int(1, 34) . '.png');
        $applicant->setSchoolType('Private');
        $applicant->setLastGradeCompleted('Grade 10');
        $applicant->setGeneralAverage('93.' . random_int(20, 90));
        $applicant->setMarketingSource('Social Media (Facebook / Instagram), School Caravan');
        $applicant->setDocumentsAgreed(true);
        $applicant->setDocumentsAgreedDate(new \DateTime('+30 days'));

        $applicant->setExaminationScore(92.50);
        $applicant->setExaminationDate(new \DateTime('-3 days'));

        // Passport details
        $passportNum = 'P' . random_int(1000000, 9999999);
        $applicant->setPassportNumber($passportNum);
        $applicant->setVisaType('permanent_resident');
        $applicant->setVisaStatus('Active');

        $passport = new ApplicantBedPassport();
        $passport->setApplicant($applicant);
        $passport->setPassportNumber($passportNum);
        $passport->setCountryOfIssue('Philippines');
        $passport->setDateIssued(new \DateTime('-2 years'));
        $passport->setExpirationDate(new \DateTime('+8 years'));
        $applicant->setPassport($passport);
        $this->em->persist($passport);

        // --- Guardians (Father, Mother, Emergency Contact) ---
        // 1. Father
        $father = new ApplicantBedGuardian();
        $father->setApplicant($applicant);
        $father->setGuardianType('FATHER');
        $father->setRelationship('Father');
        $father->setParentName($lastName . ', Ricardo ' . $middleName);
        $father->setOccupation('Civil Engineer');
        $father->setContactNo('+63 917' . random_int(1000000, 9999999));
        $father->setEmail('ricardo.' . strtolower($lastName) . '@example.com');
        $father->setNationality('Filipino');
        $father->setDeceased(false);
        $father->setOFW(false);
        $father->setSameAsApplicant(true);
        $father->setCurrentRegion($applicant->getCurrentRegion());
        $father->setCurrentProvince($applicant->getCurrentProvince());
        $father->setCurrentCity($applicant->getCurrentCity());
        $father->setCurrentBarangay($applicant->getCurrentBarangay());
        $father->setCurrentAddress($applicant->getCurrentAddress());
        $father->setCurrentZip($applicant->getCurrentZip());
        $father->setPermanentRegion($applicant->getPermanentRegion());
        $father->setPermanentProvince($applicant->getPermanentProvince());
        $father->setPermanentCity($applicant->getPermanentCity());
        $father->setPermanentBarangay($applicant->getPermanentBarangay());
        $father->setPermanentAddress($applicant->getPermanentAddress());
        $father->setPermanentZip($applicant->getPermanentZip());
        $father->setPermanentCountry('Philippines');
        $applicant->addGuardian($father);
        $this->em->persist($father);

        // 2. Mother
        $motherMaidenLastName = $middleNames[array_rand($middleNames)];
        $mother = new ApplicantBedGuardian();
        $mother->setApplicant($applicant);
        $mother->setGuardianType('MOTHER');
        $mother->setRelationship('Mother');
        $mother->setParentName($motherMaidenLastName . ', Maria Clara');
        $mother->setOccupation('Senior Accountant');
        $mother->setContactNo('+63 918' . random_int(1000000, 9999999));
        $mother->setEmail('mariaclara.' . strtolower($motherMaidenLastName) . '@example.com');
        $mother->setNationality('Filipino');
        $mother->setDeceased(false);
        $mother->setOFW(false);
        $mother->setSameAsApplicant(true);
        $mother->setCurrentRegion($applicant->getCurrentRegion());
        $mother->setCurrentProvince($applicant->getCurrentProvince());
        $mother->setCurrentCity($applicant->getCurrentCity());
        $mother->setCurrentBarangay($applicant->getCurrentBarangay());
        $mother->setCurrentAddress($applicant->getCurrentAddress());
        $mother->setCurrentZip($applicant->getCurrentZip());
        $mother->setPermanentRegion($applicant->getPermanentRegion());
        $mother->setPermanentProvince($applicant->getPermanentProvince());
        $mother->setPermanentCity($applicant->getPermanentCity());
        $mother->setPermanentBarangay($applicant->getPermanentBarangay());
        $mother->setPermanentAddress($applicant->getPermanentAddress());
        $mother->setPermanentZip($applicant->getPermanentZip());
        $mother->setPermanentCountry('Philippines');
        $applicant->addGuardian($mother);
        $this->em->persist($mother);

        // 3. Guardian / Emergency Contact
        $guardian = new ApplicantBedGuardian();
        $guardian->setApplicant($applicant);
        $guardian->setGuardianType('GUARDIAN');
        $guardian->setRelationship('Uncle');
        $guardian->setParentName($lastName . ', Antonio ' . $middleName);
        $guardian->setOccupation('Operations Manager');
        $guardian->setContactNo('+63 919' . random_int(1000000, 9999999));
        $guardian->setEmail('antonio.' . strtolower($lastName) . '@example.com');
        $guardian->setNationality('Filipino');
        $guardian->setAddress('Quezon City, Metro Manila');
        $applicant->addGuardian($guardian);
        $this->em->persist($guardian);

        // --- Siblings ---
        // Sibling 1 (FEU Student)
        $sibling1 = new ApplicantBedSibling();
        $sibling1->setApplicant($applicant);
        $sibling1->setSiblingName($lastName . ', Marco ' . $middleName);
        $sibling1->setSchool('FEU Diliman');
        $sibling1->setIsFeuStudent(true);
        $sibling1->setFeuStudentNo('202350088');
        $applicant->addSibling($sibling1);
        $this->em->persist($sibling1);

        // Sibling 2 (Non-FEU Student)
        $sibling2 = new ApplicantBedSibling();
        $sibling2->setApplicant($applicant);
        $sibling2->setSiblingName($lastName . ', Beatrice ' . $middleName);
        $sibling2->setSchool('University of the Philippines Diliman');
        $sibling2->setIsFeuStudent(false);
        $sibling2->setFeuStudentNo(null);
        $applicant->addSibling($sibling2);
        $this->em->persist($sibling2);

        // --- Education History ---
        // Junior High School
        $schoolJhs = new ApplicantBedSchool();
        $schoolJhs->setApplicant($applicant);
        $schoolJhs->setLevel('Junior High School');
        $schoolJhs->setSchool('Ateneo de Manila Junior High School');
        $schoolJhs->setSchoolYear('2023-2024');
        $schoolJhs->setSchoolType('Private');
        $schoolJhs->setIsInternational(false);
        $schoolJhs->setRegion('National Capital Region (NCR)');
        $schoolJhs->setProvince('Metro Manila');
        $schoolJhs->setCity('Quezon City');
        $applicant->addSchool($schoolJhs);
        $this->em->persist($schoolJhs);

        // Elementary
        $schoolElem = new ApplicantBedSchool();
        $schoolElem->setApplicant($applicant);
        $schoolElem->setLevel('Elementary');
        $schoolElem->setSchool('Miriam College Lower School');
        $schoolElem->setSchoolYear('2019-2020');
        $schoolElem->setSchoolType('Private');
        $schoolElem->setIsInternational(false);
        $schoolElem->setRegion('National Capital Region (NCR)');
        $schoolElem->setProvince('Metro Manila');
        $schoolElem->setCity('Quezon City');
        $applicant->addSchool($schoolElem);
        $this->em->persist($schoolElem);

        // --- Document Requirements ---
        $docSetups = $this->em->getRepository(DocumentSetup::class)->findBy(['campus' => 'FDIL']);
        if (count($docSetups) > 0) {
            foreach ($docSetups as $setup) {
                $req = new ApplicantBedRequirement();
                $req->setApplicant($applicant);
                $req->setSlug($setup->getSlug());
                $req->setRequirement($setup->getDocumentName());
                $req->setStatus('S');
                $req->setDateSubmitted(new \DateTime());
                $req->setStoredFileName('uploads/documents/' . $setup->getSlug() . '_sample.pdf');
                $applicant->addRequirement($req);
                $this->em->persist($req);
            }
        } else {
            // Default essential requirements if no setups loaded
            $defaultReqs = [
                ['slug' => 'req_psa', 'name' => 'PSA Birth Certificate'],
                ['slug' => 'req_form_137_transcript_of_records_', 'name' => 'Form 137 (Transcript of Records)'],
                ['slug' => 'req_feu_docs', 'name' => 'SHS Only Docs'],
            ];
            foreach ($defaultReqs as $dr) {
                $req = new ApplicantBedRequirement();
                $req->setApplicant($applicant);
                $req->setSlug($dr['slug']);
                $req->setRequirement($dr['name']);
                $req->setStatus('S');
                $req->setDateSubmitted(new \DateTime());
                $req->setStoredFileName('uploads/documents/' . $dr['slug'] . '_sample.pdf');
                $applicant->addRequirement($req);
                $this->em->persist($req);
            }
        }

        $this->em->flush();

        $io->success(sprintf(
            'Successfully generated sample applicant for FEU Diliman!' . PHP_EOL .
            'Student Number: %s' . PHP_EOL .
            'Name: %s, %s %s' . PHP_EOL .
            'Middle Name: %s' . PHP_EOL .
            'Siblings: %s, %s' . PHP_EOL .
            'Guardians: Father (%s), Mother (%s), Emergency Contact (%s)' . PHP_EOL .
            'School Year: %s' . PHP_EOL .
            'Grade Level & Strand: %s - %s',
            $applicant->getStudentNumber(),
            $applicant->getLastName(),
            $applicant->getFirstName(),
            $applicant->getMiddleName(),
            $applicant->getMiddleName(),
            $sibling1->getSiblingName(),
            $sibling2->getSiblingName(),
            $father->getParentName(),
            $mother->getParentName(),
            $guardian->getParentName(),
            $applicant->getSchoolYearOfEntry(),
            $applicant->getGradeLevel(),
            $applicant->getTrackStrand()
        ));

        return Command::SUCCESS;
    }
}
