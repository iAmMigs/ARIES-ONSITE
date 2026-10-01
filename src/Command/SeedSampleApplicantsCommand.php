<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedPassport;
use App\Entity\ApplicantBedRequirement;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-sample-applicants',
    description: 'Generates 2 random, unique, detailed BED applicants for FEU Diliman and 2 for FEU Alabang following student number rules',
)]
class SeedSampleApplicantsCommand extends Command
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
        $syAlabang = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_ALABANG);

        if (!$syDiliman) {
            $io->error('No active school year found for FEU Diliman (FDIL).');
            return Command::FAILURE;
        }

        if (!$syAlabang) {
            $io->error('No active school year found for FEU Alabang (FALAB).');
            return Command::FAILURE;
        }

        $io->title('Generating Detailed Sample Applicants');

        $createdApplicants = [];

        // -------------------------------------------------------------
        // 1. FEU DILIMAN - REGULAR (FILIPINO) APPLICANT
        // -------------------------------------------------------------
        $dilimanReg = new ApplicantBed();
        $studentNoDilReg = $this->idGenerator->generateStudentNumber('feu_diliman', $syDiliman, false);
        $dilimanReg->setStudentNumber($studentNoDilReg);
        $this->em->persist($dilimanReg);

        $dilimanReg->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $dilimanReg->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $dilimanReg->setAdmissionType('New Student');
        $dilimanReg->setAdmissionDate(new \DateTime());
        $dilimanReg->setSchoolYearOfEntry($syDiliman->getLabel());
        $dilimanReg->setEducationType('Junior High School');
        $dilimanReg->setGradeLevel('Grade 7');
        $dilimanReg->setTrackStrand(null);
        $dilimanReg->setLrn('109283746512');
        $dilimanReg->setLastName('DELA CRUZ');
        $dilimanReg->setFirstName('MATEO');
        $dilimanReg->setMiddleName('SANTOS');
        $dilimanReg->setPreferredName('TEO');
        $dilimanReg->setBirthDate(new \DateTime('2013-04-15'));
        $dilimanReg->setBirthPlace('QUEZON CITY');
        $dilimanReg->setGender(ApplicantBed::GENDER_MALE);
        $dilimanReg->setReligion('ROMAN CATHOLIC');
        $dilimanReg->setCitizenship('FILIPINO');
        $dilimanReg->setNationality('FILIPINO');
        $dilimanReg->setCountryOfBirth('Philippines');
        $dilimanReg->setCountryOfResidence('Philippines');
        $dilimanReg->setMobileNumber('+63 9171234567');
        $dilimanReg->setLandLineNumber('0289201234');
        $dilimanReg->setPersonalEmail('mateo.delacruz@example.com');
        $dilimanReg->setCurrentRegion('NCR');
        $dilimanReg->setCurrentProvince('METRO MANILA');
        $dilimanReg->setCurrentCity('QUEZON CITY');
        $dilimanReg->setCurrentBarangay('BATASAN HILLS');
        $dilimanReg->setCurrentAddress('BLOCK 12 LOT 4 DIAMOND STREET, FILINVEST HOMES');
        $dilimanReg->setCurrentZip('1126');
        $dilimanReg->setPermanentRegion('NCR');
        $dilimanReg->setPermanentProvince('METRO MANILA');
        $dilimanReg->setPermanentCity('QUEZON CITY');
        $dilimanReg->setPermanentBarangay('BATASAN HILLS');
        $dilimanReg->setPermanentAddress('BLOCK 12 LOT 4 DIAMOND STREET, FILINVEST HOMES');
        $dilimanReg->setPermanentZip('1126');
        $dilimanReg->setLastGradeCompleted('Grade 6');
        $dilimanReg->setGeneralAverage('92.50');
        $dilimanReg->setMarketingSource('Social Media (Facebook / Instagram)');
        $dilimanReg->setDocumentsAgreed(true);
        $dilimanReg->setDocumentsAgreedDate(new \DateTime('+30 days'));

        // Guardians for Diliman Regular
        $dilRegFather = new ApplicantBedGuardian();
        $dilRegFather->setApplicant($dilimanReg);
        $dilRegFather->setGuardianType('father');
        $dilRegFather->setRelationship('Father');
        $dilRegFather->setParentName('ROBERTO SANTOS DELA CRUZ');
        $dilRegFather->setOccupation('Civil Engineer');
        $dilRegFather->setContactNo('+63 9178881234');
        $dilRegFather->setEmail('roberto.delacruz@example.com');
        $dilRegFather->setNationality('FILIPINO');
        $dilRegFather->setSameAsApplicant(true);
        $dilRegFather->setCurrentRegion('NCR');
        $dilRegFather->setCurrentProvince('METRO MANILA');
        $dilRegFather->setCurrentCity('QUEZON CITY');
        $dilRegFather->setCurrentBarangay('BATASAN HILLS');
        $dilRegFather->setCurrentAddress('BLOCK 12 LOT 4 DIAMOND STREET, FILINVEST HOMES');
        $dilRegFather->setCurrentZip('1126');
        $dilimanReg->addGuardian($dilRegFather);
        $this->em->persist($dilRegFather);

        $dilRegMother = new ApplicantBedGuardian();
        $dilRegMother->setApplicant($dilimanReg);
        $dilRegMother->setGuardianType('mother');
        $dilRegMother->setRelationship('Mother');
        $dilRegMother->setParentName('ELENA REYES DELA CRUZ');
        $dilRegMother->setOccupation('Certified Public Accountant');
        $dilRegMother->setContactNo('+63 9187779876');
        $dilRegMother->setEmail('elena.delacruz@example.com');
        $dilRegMother->setNationality('FILIPINO');
        $dilRegMother->setSameAsApplicant(true);
        $dilRegMother->setCurrentRegion('NCR');
        $dilRegMother->setCurrentProvince('METRO MANILA');
        $dilRegMother->setCurrentCity('QUEZON CITY');
        $dilRegMother->setCurrentBarangay('BATASAN HILLS');
        $dilRegMother->setCurrentAddress('BLOCK 12 LOT 4 DIAMOND STREET, FILINVEST HOMES');
        $dilRegMother->setCurrentZip('1126');
        $dilimanReg->addGuardian($dilRegMother);
        $this->em->persist($dilRegMother);

        $dilRegGuardian = new ApplicantBedGuardian();
        $dilRegGuardian->setApplicant($dilimanReg);
        $dilRegGuardian->setGuardianType('guardian');
        $dilRegGuardian->setRelationship('Father');
        $dilRegGuardian->setParentName('ROBERTO SANTOS DELA CRUZ');
        $dilRegGuardian->setOccupation('Civil Engineer');
        $dilRegGuardian->setContactNo('+63 9178881234');
        $dilRegGuardian->setEmail('roberto.delacruz@example.com');
        $dilRegGuardian->setNationality('FILIPINO');
        $dilRegGuardian->setSameAsApplicant(true);
        $dilRegGuardian->setCurrentRegion('NCR');
        $dilRegGuardian->setCurrentProvince('METRO MANILA');
        $dilRegGuardian->setCurrentCity('QUEZON CITY');
        $dilRegGuardian->setCurrentBarangay('BATASAN HILLS');
        $dilRegGuardian->setCurrentAddress('BLOCK 12 LOT 4 DIAMOND STREET, FILINVEST HOMES');
        $dilRegGuardian->setCurrentZip('1126');
        $dilimanReg->addGuardian($dilRegGuardian);
        $this->em->persist($dilRegGuardian);

        // Sibling for Diliman Regular
        $dilRegSibling = new ApplicantBedSibling();
        $dilRegSibling->setApplicant($dilimanReg);
        $dilRegSibling->setSiblingName('BEATRICE DELA CRUZ');
        $dilRegSibling->setSchool('FEU Diliman');
        $dilRegSibling->setIsFeuStudent(true);
        $dilRegSibling->setFeuStudentNo('202350123');
        $dilimanReg->addSibling($dilRegSibling);
        $this->em->persist($dilRegSibling);

        // Previous School for Diliman Regular
        $dilRegSchool = new ApplicantBedSchool();
        $dilRegSchool->setApplicant($dilimanReg);
        $dilRegSchool->setLevel('Elementary');
        $dilRegSchool->setSchool('ATENEO GRADE SCHOOL');
        $dilRegSchool->setSchoolYear('2024-2025');
        $dilRegSchool->setSchoolType('Private');
        $dilRegSchool->setIsInternational(false);
        $dilRegSchool->setRegion('NCR');
        $dilRegSchool->setProvince('METRO MANILA');
        $dilRegSchool->setCity('QUEZON CITY');
        $dilimanReg->addSchool($dilRegSchool);
        $this->em->persist($dilRegSchool);

        // Requirements for Diliman Regular
        $req1 = new ApplicantBedRequirement();
        $req1->setApplicant($dilimanReg);
        $req1->setSlug('psa_birth_certificate');
        $req1->setRequirement('PSA Birth Certificate');
        $req1->setStatus('P');
        $req1->setDateSubmitted(new \DateTime());
        $dilimanReg->addRequirement($req1);
        $this->em->persist($req1);

        $req2 = new ApplicantBedRequirement();
        $req2->setApplicant($dilimanReg);
        $req2->setSlug('report_card');
        $req2->setRequirement('Report Card (Form 138)');
        $req2->setStatus('P');
        $req2->setDateSubmitted(new \DateTime());
        $dilimanReg->addRequirement($req2);
        $this->em->persist($req2);

        $createdApplicants[] = $dilimanReg;
        $this->em->flush();

        // -------------------------------------------------------------
        // 2. FEU DILIMAN - INTERNATIONAL APPLICANT
        // -------------------------------------------------------------
        $dilimanInt = new ApplicantBed();
        $studentNoDilInt = $this->idGenerator->generateStudentNumber('feu_diliman', $syDiliman, true);
        $dilimanInt->setStudentNumber($studentNoDilInt);
        $this->em->persist($dilimanInt);

        $dilimanInt->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $dilimanInt->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $dilimanInt->setAdmissionType('New Student');
        $dilimanInt->setAdmissionDate(new \DateTime());
        $dilimanInt->setSchoolYearOfEntry($syDiliman->getLabel());
        $dilimanInt->setEducationType('Senior High School');
        $dilimanInt->setGradeLevel('Grade 11');
        $dilimanInt->setTrackStrand('STEM');
        $dilimanInt->setLrn(null);
        $dilimanInt->setLastName('KIM');
        $dilimanInt->setFirstName('ALEXANDER');
        $dilimanInt->setMiddleName('JIN');
        $dilimanInt->setPreferredName('ALEX');
        $dilimanInt->setBirthDate(new \DateTime('2009-08-22'));
        $dilimanInt->setBirthPlace('SEOUL, SOUTH KOREA');
        $dilimanInt->setGender(ApplicantBed::GENDER_MALE);
        $dilimanInt->setReligion('CHRISTIAN');
        $dilimanInt->setCitizenship('INTERNATIONAL');
        $dilimanInt->setNationality('SOUTH KOREAN');
        $dilimanInt->setPassportNumber('M19827364');
        $dilimanInt->setVisaType('Special Study Permit (SSP)');
        $dilimanInt->setVisaStatus('Valid');
        $dilimanInt->setCountryOfBirth('South Korea');
        $dilimanInt->setCountryOfResidence('Philippines');
        $dilimanInt->setMobileNumber('+63 9209876543');
        $dilimanInt->setLandLineNumber('0286315544');
        $dilimanInt->setPersonalEmail('alexander.kim@example.com');
        $dilimanInt->setCurrentRegion('NCR');
        $dilimanInt->setCurrentProvince('METRO MANILA');
        $dilimanInt->setCurrentCity('QUEZON CITY');
        $dilimanInt->setCurrentBarangay('LOYOLA HEIGHTS');
        $dilimanInt->setCurrentAddress('UNIT 2405 BLUE RESIDENCES, KATIPUNAN AVENUE');
        $dilimanInt->setCurrentZip('1108');
        $dilimanInt->setPermanentRegion(null);
        $dilimanInt->setPermanentProvince(null);
        $dilimanInt->setPermanentCity('SEOUL');
        $dilimanInt->setPermanentBarangay('GANGNAM-GU');
        $dilimanInt->setPermanentAddress('123 TEHERAN-RO, GANGNAM-GU');
        $dilimanInt->setPermanentZip('06132');
        $dilimanInt->setPermanentCountry('South Korea');
        $dilimanInt->setLastGradeCompleted('Grade 10');
        $dilimanInt->setGeneralAverage('94.00');
        $dilimanInt->setMarketingSource('School Fair / Educational Expo');
        $dilimanInt->setDocumentsAgreed(true);
        $dilimanInt->setDocumentsAgreedDate(new \DateTime('+30 days'));

        // Passport Entity for Diliman International
        $passport = new ApplicantBedPassport();
        $passport->setApplicant($dilimanInt);
        $passport->setPassportNumber('M19827364');
        $passport->setCountryOfIssue('South Korea');
        $passport->setDateIssued(new \DateTime('2023-01-10'));
        $passport->setExpirationDate(new \DateTime('2033-01-09'));
        $dilimanInt->setPassport($passport);
        $this->em->persist($passport);

        // Guardians for Diliman International
        $dilIntFather = new ApplicantBedGuardian();
        $dilIntFather->setApplicant($dilimanInt);
        $dilIntFather->setGuardianType('father');
        $dilIntFather->setRelationship('Father');
        $dilIntFather->setParentName('DONG-HYUN KIM');
        $dilIntFather->setOccupation('Managing Director');
        $dilIntFather->setContactNo('+82 10 1234 5678');
        $dilIntFather->setEmail('dhkim@example.com');
        $dilIntFather->setNationality('SOUTH KOREAN');
        $dilIntFather->setSameAsApplicant(false);
        $dilIntFather->setCurrentCity('SEOUL');
        $dilIntFather->setCurrentAddress('123 TEHERAN-RO, GANGNAM-GU, SEOUL');
        $dilIntFather->setCurrentZip('06132');
        $dilimanInt->addGuardian($dilIntFather);
        $this->em->persist($dilIntFather);

        $dilIntMother = new ApplicantBedGuardian();
        $dilIntMother->setApplicant($dilimanInt);
        $dilIntMother->setGuardianType('mother');
        $dilIntMother->setRelationship('Mother');
        $dilIntMother->setParentName('MIN-JI PARK');
        $dilIntMother->setOccupation('Architect');
        $dilIntMother->setContactNo('+82 10 8765 4321');
        $dilIntMother->setEmail('mjpark@example.com');
        $dilIntMother->setNationality('SOUTH KOREAN');
        $dilIntMother->setSameAsApplicant(false);
        $dilIntMother->setCurrentCity('SEOUL');
        $dilIntMother->setCurrentAddress('123 TEHERAN-RO, GANGNAM-GU, SEOUL');
        $dilIntMother->setCurrentZip('06132');
        $dilimanInt->addGuardian($dilIntMother);
        $this->em->persist($dilIntMother);

        $dilIntGuardian = new ApplicantBedGuardian();
        $dilIntGuardian->setApplicant($dilimanInt);
        $dilIntGuardian->setGuardianType('guardian');
        $dilIntGuardian->setRelationship('Local Guardian');
        $dilIntGuardian->setParentName('GRACE TAN CASTILLO');
        $dilIntGuardian->setOccupation('Legal Counsel');
        $dilIntGuardian->setContactNo('+63 9176543210');
        $dilIntGuardian->setEmail('grace.castillo@example.com');
        $dilIntGuardian->setNationality('FILIPINO');
        $dilIntGuardian->setSameAsApplicant(true);
        $dilIntGuardian->setCurrentRegion('NCR');
        $dilIntGuardian->setCurrentProvince('METRO MANILA');
        $dilIntGuardian->setCurrentCity('QUEZON CITY');
        $dilIntGuardian->setCurrentBarangay('LOYOLA HEIGHTS');
        $dilIntGuardian->setCurrentAddress('UNIT 2405 BLUE RESIDENCES, KATIPUNAN AVENUE');
        $dilIntGuardian->setCurrentZip('1108');
        $dilimanInt->addGuardian($dilIntGuardian);
        $this->em->persist($dilIntGuardian);

        // Sibling for Diliman International
        $dilIntSibling = new ApplicantBedSibling();
        $dilIntSibling->setApplicant($dilimanInt);
        $dilIntSibling->setSiblingName('SO-EUN KIM');
        $dilIntSibling->setSchool('Seoul Foreign School');
        $dilIntSibling->setIsFeuStudent(false);
        $dilimanInt->addSibling($dilIntSibling);
        $this->em->persist($dilIntSibling);

        // Previous School for Diliman International
        $dilIntSchool = new ApplicantBedSchool();
        $dilIntSchool->setApplicant($dilimanInt);
        $dilIntSchool->setLevel('Junior High School');
        $dilIntSchool->setSchool('SEOUL INTERNATIONAL ACADEMY');
        $dilIntSchool->setSchoolYear('2024-2025');
        $dilIntSchool->setSchoolType('Private');
        $dilIntSchool->setIsInternational(true);
        $dilIntSchool->setCountry('South Korea');
        $dilIntSchool->setCity('Seoul');
        $dilimanInt->addSchool($dilIntSchool);
        $this->em->persist($dilIntSchool);

        // Requirements for Diliman International
        $reqInt1 = new ApplicantBedRequirement();
        $reqInt1->setApplicant($dilimanInt);
        $reqInt1->setSlug('passport_bio_page');
        $reqInt1->setRequirement('Passport Bio Page');
        $reqInt1->setStatus('P');
        $reqInt1->setDateSubmitted(new \DateTime());
        $dilimanInt->addRequirement($reqInt1);
        $this->em->persist($reqInt1);

        $reqInt2 = new ApplicantBedRequirement();
        $reqInt2->setApplicant($dilimanInt);
        $reqInt2->setSlug('transcript_of_records');
        $reqInt2->setRequirement('Official Transcript of Records');
        $reqInt2->setStatus('P');
        $reqInt2->setDateSubmitted(new \DateTime());
        $dilimanInt->addRequirement($reqInt2);
        $this->em->persist($reqInt2);

        $createdApplicants[] = $dilimanInt;
        $this->em->flush();

        // -------------------------------------------------------------
        // 3. FEU ALABANG - REGULAR ELEMENTARY APPLICANT
        // -------------------------------------------------------------
        $alabang1 = new ApplicantBed();
        $studentNoAlab1 = $this->idGenerator->generateStudentNumber('feu_alabang', $syAlabang, false);
        $alabang1->setStudentNumber($studentNoAlab1);
        $this->em->persist($alabang1);

        $alabang1->setCampus(ApplicantBed::CAMPUS_ALABANG);
        $alabang1->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $alabang1->setAdmissionType('New Student');
        $alabang1->setAdmissionDate(new \DateTime());
        $alabang1->setSchoolYearOfEntry($syAlabang->getLabel());
        $alabang1->setEducationType('Grade School');
        $alabang1->setGradeLevel('Grade 1');
        $alabang1->setTrackStrand(null);
        $alabang1->setLrn('108927364519');
        $alabang1->setLastName('MENDOZA');
        $alabang1->setFirstName('SOPHIA LORRAINE');
        $alabang1->setMiddleName('VILLANUEVA');
        $alabang1->setPreferredName('SOPHIE');
        $alabang1->setBirthDate(new \DateTime('2019-11-05'));
        $alabang1->setBirthPlace('MUNTINLUPA CITY');
        $alabang1->setGender(ApplicantBed::GENDER_FEMALE);
        $alabang1->setReligion('ROMAN CATHOLIC');
        $alabang1->setCitizenship('FILIPINO');
        $alabang1->setNationality('FILIPINO');
        $alabang1->setCountryOfBirth('Philippines');
        $alabang1->setCountryOfResidence('Philippines');
        $alabang1->setMobileNumber('+63 9285551234');
        $alabang1->setLandLineNumber('0288091122');
        $alabang1->setPersonalEmail('mendoza.family@example.com');
        $alabang1->setCurrentRegion('NCR');
        $alabang1->setCurrentProvince('METRO MANILA');
        $alabang1->setCurrentCity('MUNTINLUPA CITY');
        $alabang1->setCurrentBarangay('AYALA ALABANG');
        $alabang1->setCurrentAddress('742 ACACIA AVENUE, AYALA ALABANG VILLAGE');
        $alabang1->setCurrentZip('1780');
        $alabang1->setPermanentRegion('NCR');
        $alabang1->setPermanentProvince('METRO MANILA');
        $alabang1->setPermanentCity('MUNTINLUPA CITY');
        $alabang1->setPermanentBarangay('AYALA ALABANG');
        $alabang1->setPermanentAddress('742 ACACIA AVENUE, AYALA ALABANG VILLAGE');
        $alabang1->setPermanentZip('1780');
        $alabang1->setLastGradeCompleted('Kindergarten');
        $alabang1->setGeneralAverage('95.00');
        $alabang1->setMarketingSource('Alumni Referral');
        $alabang1->setDocumentsAgreed(true);
        $alabang1->setDocumentsAgreedDate(new \DateTime('+30 days'));

        // Guardians for Alabang 1
        $alab1Father = new ApplicantBedGuardian();
        $alab1Father->setApplicant($alabang1);
        $alab1Father->setGuardianType('father');
        $alab1Father->setRelationship('Father');
        $alab1Father->setParentName('GABRIEL VILLANUEVA MENDOZA');
        $alab1Father->setOccupation('Physician / Orthopedic Surgeon');
        $alab1Father->setContactNo('+63 9175002233');
        $alab1Father->setEmail('dr.gmendoza@example.com');
        $alab1Father->setNationality('FILIPINO');
        $alab1Father->setSameAsApplicant(true);
        $alab1Father->setCurrentRegion('NCR');
        $alab1Father->setCurrentProvince('METRO MANILA');
        $alab1Father->setCurrentCity('MUNTINLUPA CITY');
        $alab1Father->setCurrentBarangay('AYALA ALABANG');
        $alab1Father->setCurrentAddress('742 ACACIA AVENUE, AYALA ALABANG VILLAGE');
        $alab1Father->setCurrentZip('1780');
        $alabang1->addGuardian($alab1Father);
        $this->em->persist($alab1Father);

        $alab1Mother = new ApplicantBedGuardian();
        $alab1Mother->setApplicant($alabang1);
        $alab1Mother->setGuardianType('mother');
        $alab1Mother->setRelationship('Mother');
        $alab1Mother->setParentName('CARMEN TAN MENDOZA');
        $alab1Mother->setOccupation('Pharmacist');
        $alab1Mother->setContactNo('+63 9186003344');
        $alab1Mother->setEmail('carmen.mendoza@example.com');
        $alab1Mother->setNationality('FILIPINO');
        $alab1Mother->setSameAsApplicant(true);
        $alab1Mother->setCurrentRegion('NCR');
        $alab1Mother->setCurrentProvince('METRO MANILA');
        $alab1Mother->setCurrentCity('MUNTINLUPA CITY');
        $alab1Mother->setCurrentBarangay('AYALA ALABANG');
        $alab1Mother->setCurrentAddress('742 ACACIA AVENUE, AYALA ALABANG VILLAGE');
        $alab1Mother->setCurrentZip('1780');
        $alabang1->addGuardian($alab1Mother);
        $this->em->persist($alab1Mother);

        $alab1Guardian = new ApplicantBedGuardian();
        $alab1Guardian->setApplicant($alabang1);
        $alab1Guardian->setGuardianType('guardian');
        $alab1Guardian->setRelationship('Mother');
        $alab1Guardian->setParentName('CARMEN TAN MENDOZA');
        $alab1Guardian->setOccupation('Pharmacist');
        $alab1Guardian->setContactNo('+63 9186003344');
        $alab1Guardian->setEmail('carmen.mendoza@example.com');
        $alab1Guardian->setNationality('FILIPINO');
        $alab1Guardian->setSameAsApplicant(true);
        $alab1Guardian->setCurrentRegion('NCR');
        $alab1Guardian->setCurrentProvince('METRO MANILA');
        $alab1Guardian->setCurrentCity('MUNTINLUPA CITY');
        $alab1Guardian->setCurrentBarangay('AYALA ALABANG');
        $alab1Guardian->setCurrentAddress('742 ACACIA AVENUE, AYALA ALABANG VILLAGE');
        $alab1Guardian->setCurrentZip('1780');
        $alabang1->addGuardian($alab1Guardian);
        $this->em->persist($alab1Guardian);

        // Sibling for Alabang 1
        $alab1Sibling = new ApplicantBedSibling();
        $alab1Sibling->setApplicant($alabang1);
        $alab1Sibling->setSiblingName('LUCAS MENDOZA');
        $alab1Sibling->setSchool('FEU Alabang');
        $alab1Sibling->setIsFeuStudent(true);
        $alab1Sibling->setFeuStudentNo('20245000123');
        $alabang1->addSibling($alab1Sibling);
        $this->em->persist($alab1Sibling);

        // Previous School for Alabang 1
        $alab1School = new ApplicantBedSchool();
        $alab1School->setApplicant($alabang1);
        $alab1School->setLevel('Kindergarten');
        $alab1School->setSchool('DE LA SALLE ZOBEL - PRE-SCHOOL');
        $alab1School->setSchoolYear('2024-2025');
        $alab1School->setSchoolType('Private');
        $alab1School->setIsInternational(false);
        $alab1School->setRegion('NCR');
        $alab1School->setProvince('METRO MANILA');
        $alab1School->setCity('MUNTINLUPA CITY');
        $alabang1->addSchool($alab1School);
        $this->em->persist($alab1School);

        // Requirements for Alabang 1
        $reqAlab1_1 = new ApplicantBedRequirement();
        $reqAlab1_1->setApplicant($alabang1);
        $reqAlab1_1->setSlug('psa_birth_certificate');
        $reqAlab1_1->setRequirement('PSA Birth Certificate');
        $reqAlab1_1->setStatus('P');
        $reqAlab1_1->setDateSubmitted(new \DateTime());
        $alabang1->addRequirement($reqAlab1_1);
        $this->em->persist($reqAlab1_1);

        $reqAlab1_2 = new ApplicantBedRequirement();
        $reqAlab1_2->setApplicant($alabang1);
        $reqAlab1_2->setSlug('kinder_certificate');
        $reqAlab1_2->setRequirement('Kindergarten Completion Certificate');
        $reqAlab1_2->setStatus('P');
        $reqAlab1_2->setDateSubmitted(new \DateTime());
        $alabang1->addRequirement($reqAlab1_2);
        $this->em->persist($reqAlab1_2);

        $createdApplicants[] = $alabang1;
        $this->em->flush();

        // -------------------------------------------------------------
        // 4. FEU ALABANG - REGULAR SENIOR HIGH APPLICANT
        // -------------------------------------------------------------
        $alabang2 = new ApplicantBed();
        $studentNoAlab2 = $this->idGenerator->generateStudentNumber('feu_alabang', $syAlabang, false);
        $alabang2->setStudentNumber($studentNoAlab2);
        $this->em->persist($alabang2);

        $alabang2->setCampus(ApplicantBed::CAMPUS_ALABANG);
        $alabang2->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $alabang2->setAdmissionType('New Student');
        $alabang2->setAdmissionDate(new \DateTime());
        $alabang2->setSchoolYearOfEntry($syAlabang->getLabel());
        $alabang2->setEducationType('Senior High School');
        $alabang2->setGradeLevel('Grade 11');
        $alabang2->setTrackStrand('ABM');
        $alabang2->setLrn('136502849102');
        $alabang2->setLastName('BAUTISTA');
        $alabang2->setFirstName('CHRISTIAN JUDE');
        $alabang2->setMiddleName('GUTIERREZ');
        $alabang2->setPreferredName('CJ');
        $alabang2->setBirthDate(new \DateTime('2009-06-18'));
        $alabang2->setBirthPlace('SAN PEDRO, LAGUNA');
        $alabang2->setGender(ApplicantBed::GENDER_MALE);
        $alabang2->setReligion('ROMAN CATHOLIC');
        $alabang2->setCitizenship('FILIPINO');
        $alabang2->setNationality('FILIPINO');
        $alabang2->setCountryOfBirth('Philippines');
        $alabang2->setCountryOfResidence('Philippines');
        $alabang2->setMobileNumber('+63 9954321098');
        $alabang2->setLandLineNumber('0495308899');
        $alabang2->setPersonalEmail('christian.bautista@example.com');
        $alabang2->setCurrentRegion('REGION IV-A');
        $alabang2->setCurrentProvince('LAGUNA');
        $alabang2->setCurrentCity('SAN PEDRO');
        $alabang2->setCurrentBarangay('SAN VICENTE');
        $alabang2->setCurrentAddress('PHASE 3 BLOCK 8 LOT 19, VILLA OLYMPIA');
        $alabang2->setCurrentZip('4023');
        $alabang2->setPermanentRegion('REGION IV-A');
        $alabang2->setPermanentProvince('LAGUNA');
        $alabang2->setPermanentCity('SAN PEDRO');
        $alabang2->setPermanentBarangay('SAN VICENTE');
        $alabang2->setPermanentAddress('PHASE 3 BLOCK 8 LOT 19, VILLA OLYMPIA');
        $alabang2->setPermanentZip('4023');
        $alabang2->setLastGradeCompleted('Grade 10');
        $alabang2->setGeneralAverage('91.75');
        $alabang2->setMarketingSource('School Visit / Caravan');
        $alabang2->setDocumentsAgreed(true);
        $alabang2->setDocumentsAgreedDate(new \DateTime('+30 days'));

        // Guardians for Alabang 2 (Father is OFW)
        $alab2Father = new ApplicantBedGuardian();
        $alab2Father->setApplicant($alabang2);
        $alab2Father->setGuardianType('father');
        $alab2Father->setRelationship('Father');
        $alab2Father->setParentName('EDUARDO GUTIERREZ BAUTISTA');
        $alab2Father->setOccupation('Merchant Marine Chief Mate');
        $alab2Father->setContactNo('+65 8123 4567');
        $alab2Father->setEmail('capt.edbautista@example.com');
        $alab2Father->setNationality('FILIPINO');
        $alab2Father->setOFW(true);
        $alab2Father->setOfwCountry('Singapore');
        $alab2Father->setSameAsApplicant(false);
        $alab2Father->setCurrentAddress('SINGAPORE SEAFARERS RESIDENCE, JURONG EAST');
        $alab2Father->setCurrentZip('609602');
        $alabang2->addGuardian($alab2Father);
        $this->em->persist($alab2Father);

        $alab2Mother = new ApplicantBedGuardian();
        $alab2Mother->setApplicant($alabang2);
        $alab2Mother->setGuardianType('mother');
        $alab2Mother->setRelationship('Mother');
        $alab2Mother->setParentName('TERESITA FLORES BAUTISTA');
        $alab2Mother->setOccupation('Secondary School Teacher');
        $alab2Mother->setContactNo('+63 9954321000');
        $alab2Mother->setEmail('teresita.bautista@example.com');
        $alab2Mother->setNationality('FILIPINO');
        $alab2Mother->setSameAsApplicant(true);
        $alab2Mother->setCurrentRegion('REGION IV-A');
        $alab2Mother->setCurrentProvince('LAGUNA');
        $alab2Mother->setCurrentCity('SAN PEDRO');
        $alab2Mother->setCurrentBarangay('SAN VICENTE');
        $alab2Mother->setCurrentAddress('PHASE 3 BLOCK 8 LOT 19, VILLA OLYMPIA');
        $alab2Mother->setCurrentZip('4023');
        $alabang2->addGuardian($alab2Mother);
        $this->em->persist($alab2Mother);

        $alab2Guardian = new ApplicantBedGuardian();
        $alab2Guardian->setApplicant($alabang2);
        $alab2Guardian->setGuardianType('guardian');
        $alab2Guardian->setRelationship('Mother');
        $alab2Guardian->setParentName('TERESITA FLORES BAUTISTA');
        $alab2Guardian->setOccupation('Secondary School Teacher');
        $alab2Guardian->setContactNo('+63 9954321000');
        $alab2Guardian->setEmail('teresita.bautista@example.com');
        $alab2Guardian->setNationality('FILIPINO');
        $alab2Guardian->setSameAsApplicant(true);
        $alab2Guardian->setCurrentRegion('REGION IV-A');
        $alab2Guardian->setCurrentProvince('LAGUNA');
        $alab2Guardian->setCurrentCity('SAN PEDRO');
        $alab2Guardian->setCurrentBarangay('SAN VICENTE');
        $alab2Guardian->setCurrentAddress('PHASE 3 BLOCK 8 LOT 19, VILLA OLYMPIA');
        $alab2Guardian->setCurrentZip('4023');
        $alabang2->addGuardian($alab2Guardian);
        $this->em->persist($alab2Guardian);

        // Sibling for Alabang 2
        $alab2Sibling = new ApplicantBedSibling();
        $alab2Sibling->setApplicant($alabang2);
        $alab2Sibling->setSiblingName('HANNAH BAUTISTA');
        $alab2Sibling->setSchool('University of the Philippines Los Baños');
        $alab2Sibling->setIsFeuStudent(false);
        $alabang2->addSibling($alab2Sibling);
        $this->em->persist($alab2Sibling);

        // Previous School for Alabang 2
        $alab2School = new ApplicantBedSchool();
        $alab2School->setApplicant($alabang2);
        $alab2School->setLevel('Junior High School');
        $alab2School->setSchool('SAN PEDRO RELOCATION CENTER NATIONAL HIGH SCHOOL');
        $alab2School->setSchoolYear('2024-2025');
        $alab2School->setSchoolType('Public');
        $alab2School->setIsInternational(false);
        $alab2School->setRegion('REGION IV-A');
        $alab2School->setProvince('LAGUNA');
        $alab2School->setCity('SAN PEDRO');
        $alabang2->addSchool($alab2School);
        $this->em->persist($alab2School);

        // Requirements for Alabang 2
        $reqAlab2_1 = new ApplicantBedRequirement();
        $reqAlab2_1->setApplicant($alabang2);
        $reqAlab2_1->setSlug('psa_birth_certificate');
        $reqAlab2_1->setRequirement('PSA Birth Certificate');
        $reqAlab2_1->setStatus('P');
        $reqAlab2_1->setDateSubmitted(new \DateTime());
        $alabang2->addRequirement($reqAlab2_1);
        $this->em->persist($reqAlab2_1);

        $reqAlab2_2 = new ApplicantBedRequirement();
        $reqAlab2_2->setApplicant($alabang2);
        $reqAlab2_2->setSlug('form_137_jhs');
        $reqAlab2_2->setRequirement('JHS Form 137 / SF10');
        $reqAlab2_2->setStatus('P');
        $reqAlab2_2->setDateSubmitted(new \DateTime());
        $alabang2->addRequirement($reqAlab2_2);
        $this->em->persist($reqAlab2_2);

        $createdApplicants[] = $alabang2;

        // Flush all records to DB
        $this->em->flush();

        $rows = [];
        foreach ($createdApplicants as $app) {
            $rows[] = [
                $app->getCampus(),
                $app->getStudentNumber(),
                $app->getLastName() . ', ' . $app->getFirstName() . ' ' . ($app->getMiddleName() ?? ''),
                $app->getCitizenship(),
                $app->getEducationType(),
                $app->getGradeLevel() . ($app->getTrackStrand() ? ' (' . $app->getTrackStrand() . ')' : ''),
                $app->getPersonalEmail(),
            ];
        }

        $io->table(
            ['Campus', 'Student Number', 'Full Name', 'Citizenship', 'Education Type', 'Grade / Strand', 'Email'],
            $rows
        );

        $io->success('Successfully created 4 random unique detailed applicants (2 for FEU Diliman, 2 for FEU Alabang).');

        return Command::SUCCESS;
    }
}
