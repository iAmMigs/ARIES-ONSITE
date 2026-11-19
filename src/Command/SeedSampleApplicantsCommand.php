<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedRequirement;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
use App\Entity\DocumentSetup;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\CampusDatabaseSwitcher;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-sample-applicants',
    description: 'Generates 1 random, realistic applicant for each grade (Kinder to Grade 12) for FEU Diliman and FEU Alabang without profile images.',
)]
class SeedSampleApplicantsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly StudentIdGenerator $idGenerator,
        private readonly CampusDatabaseSwitcher $dbSwitcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating 1 Applicant per Grade for FEU Diliman and FEU Alabang');

        $createdApplicants = [];

        // 13 standard Basic Education grades
        $gradeDefinitions = [
            ['grade' => 'Kinder',   'edu' => 'Kinder',              'strand' => null,   'birth_year' => 2020],
            ['grade' => 'Grade 1',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2019],
            ['grade' => 'Grade 2',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2018],
            ['grade' => 'Grade 3',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2017],
            ['grade' => 'Grade 4',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2016],
            ['grade' => 'Grade 5',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2015],
            ['grade' => 'Grade 6',  'edu' => 'Grade School',        'strand' => null,   'birth_year' => 2014],
            ['grade' => 'Grade 7',  'edu' => 'Junior High School',  'strand' => null,   'birth_year' => 2013],
            ['grade' => 'Grade 8',  'edu' => 'Junior High School',  'strand' => null,   'birth_year' => 2012],
            ['grade' => 'Grade 9',  'edu' => 'Junior High School',  'strand' => null,   'birth_year' => 2011],
            ['grade' => 'Grade 10', 'edu' => 'Junior High School',  'strand' => null,   'birth_year' => 2010],
            ['grade' => 'Grade 11', 'edu' => 'Senior High School',  'strand' => 'STEM', 'birth_year' => 2009],
            ['grade' => 'Grade 12', 'edu' => 'Senior High School',  'strand' => 'ABM',  'birth_year' => 2008],
        ];

        // Realistic profiles for FEU Diliman
        $dilimanProfiles = [
            ['first' => 'NOAH GABRIEL',     'middle' => 'TAN',          'last' => 'SANTOS',      'gender' => 'Male',   'city' => 'QUEZON CITY',   'brgy' => 'BATASAN HILLS',     'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1126', 'email' => 'noah.santos@gmail.com'],
            ['first' => 'SAMANTHA NICOLE', 'middle' => 'REYES',        'last' => 'CRUZ',        'gender' => 'Female', 'city' => 'QUEZON CITY',   'brgy' => 'COMMONWEALTH',      'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1121', 'email' => 'sam.cruz@gmail.com'],
            ['first' => 'LIAM ETHAN',      'middle' => 'BAUTISTA',     'last' => 'GARCIA',      'gender' => 'Male',   'city' => 'QUEZON CITY',   'brgy' => 'DILIMAN',           'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1101', 'email' => 'liam.garcia@gmail.com'],
            ['first' => 'CHLOE SOPHIA',    'middle' => 'MENDOZA',      'last' => 'RAMOS',       'gender' => 'Female', 'city' => 'MARIKINA CITY',  'brgy' => 'CONCEPCION UNO',    'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1807', 'email' => 'chloe.ramos@gmail.com'],
            ['first' => 'LUCAS BENJAMIN',  'middle' => 'DELA ROSA',    'last' => 'LOPEZ',       'gender' => 'Male',   'city' => 'PASIG CITY',      'brgy' => 'SAN ANTONIO',       'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1600', 'email' => 'lucas.lopez@gmail.com'],
            ['first' => 'ISABELLA MARIE',  'middle' => 'NAVARRO',      'last' => 'CASTRO',      'gender' => 'Female', 'city' => 'QUEZON CITY',   'brgy' => 'HOLY SPIRIT',       'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1127', 'email' => 'isabella.castro@gmail.com'],
            ['first' => 'JULIAN MARCUS',   'middle' => 'VILLANUEVA',   'last' => 'TORRES',      'gender' => 'Male',   'city' => 'CALOOCAN CITY',  'brgy' => 'BAGONG SILANG',     'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1428', 'email' => 'julian.torres@gmail.com'],
            ['first' => 'MIA FRANCESCA',   'middle' => 'SORIANO',      'last' => 'AQUINO',      'gender' => 'Female', 'city' => 'SAN MATEO',      'brgy' => 'AMPID I',           'prov' => 'RIZAL',        'reg' => 'REGION IV-A', 'zip' => '1850', 'email' => 'mia.aquino@gmail.com'],
            ['first' => 'DANIEL ALEXANDER','middle' => 'PASCUAL',      'last' => 'LIM',         'gender' => 'Male',   'city' => 'QUEZON CITY',   'brgy' => 'LOYOLA HEIGHTS',    'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1108', 'email' => 'daniel.lim@gmail.com'],
            ['first' => 'ANDREA CAMILLE',  'middle' => 'MORALES',      'last' => 'DIZON',       'gender' => 'Female', 'city' => 'QUEZON CITY',   'brgy' => 'TANDANG SORA',      'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1116', 'email' => 'andrea.dizon@gmail.com'],
            ['first' => 'JOAQUIN RAFAEL',  'middle' => 'SALAZAR',      'last' => 'FLORES',      'gender' => 'Male',   'city' => 'ANTIPOLO CITY',  'brgy' => 'MAYAMOT',           'prov' => 'RIZAL',        'reg' => 'REGION IV-A', 'zip' => '1870', 'email' => 'joaquin.flores@gmail.com'],
            ['first' => 'SOPHIA BEATRIZ',  'middle' => 'FERNANDEZ',    'last' => 'OCAMPO',      'gender' => 'Female', 'city' => 'QUEZON CITY',   'brgy' => 'UP CAMPUS',         'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1101', 'email' => 'sophia.ocampo@gmail.com'],
            ['first' => 'ELIJAH MIGUEL',   'middle' => 'GUTIERREZ',    'last' => 'VALENZUELA',  'gender' => 'Male',   'city' => 'RODRIGUEZ',      'brgy' => 'SAN JOSE',          'prov' => 'RIZAL',        'reg' => 'REGION IV-A', 'zip' => '1860', 'email' => 'elijah.valenzuela@gmail.com'],
        ];

        // Realistic profiles for FEU Alabang
        $alabangProfiles = [
            ['first' => 'SEBASTIAN MATEO', 'middle' => 'ROXAS',        'last' => 'ALCANTARA',   'gender' => 'Male',   'city' => 'MUNTINLUPA CITY','brgy' => 'ALABANG',          'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1781', 'email' => 'sebastian.alcantara@gmail.com'],
            ['first' => 'ALTHEA BEATRICE', 'middle' => 'PINEDA',       'last' => 'MERCADO',     'gender' => 'Female', 'city' => 'MUNTINLUPA CITY','brgy' => 'AYALA ALABANG',    'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1780', 'email' => 'althea.mercado@gmail.com'],
            ['first' => 'CALEB JOSHUA',    'middle' => 'CORTEZ',       'last' => 'RIVERA',      'gender' => 'Male',   'city' => 'LAS PIÑAS CITY', 'brgy' => 'PILAR',             'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1740', 'email' => 'caleb.rivera@gmail.com'],
            ['first' => 'HANNAH DOMINIQUE','middle' => 'DE LEON',      'last' => 'SILVA',       'gender' => 'Female', 'city' => 'PARAÑAQUE CITY', 'brgy' => 'BF HOMES',          'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1700', 'email' => 'hannah.silva@gmail.com'],
            ['first' => 'GABRIEL DOMINIC', 'middle' => 'TOLENTINO',    'last' => 'VEGA',        'gender' => 'Male',   'city' => 'MUNTINLUPA CITY','brgy' => 'POBLACION',        'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1776', 'email' => 'gabriel.vega@gmail.com'],
            ['first' => 'BEATRICE YSABELLE','middle' => 'MIRANDA',     'last' => 'SALAZAR',     'gender' => 'Female', 'city' => 'SAN PEDRO',      'brgy' => 'SAN VICENTE',       'prov' => 'LAGUNA',       'reg' => 'REGION IV-A', 'zip' => '4023', 'email' => 'beatrice.salazar@gmail.com'],
            ['first' => 'TRISTAN RAFAEL',  'middle' => 'DOMINGO',      'last' => 'CHAVEZ',      'gender' => 'Male',   'city' => 'BIÑAN CITY',     'brgy' => 'SAN ANTONIO',       'prov' => 'LAGUNA',       'reg' => 'REGION IV-A', 'zip' => '4024', 'email' => 'tristan.chavez@gmail.com'],
            ['first' => 'ALYSSA JANINE',   'middle' => 'CONCEPCION',   'last' => 'EVANGELISTA', 'gender' => 'Female', 'city' => 'SANTA ROSA CITY','brgy' => 'BALIBAGO',         'prov' => 'LAGUNA',       'reg' => 'REGION IV-A', 'zip' => '4026', 'email' => 'alyssa.evangelista@gmail.com'],
            ['first' => 'ZACHARY PAUL',    'middle' => 'DAVID',        'last' => 'SERRANO',     'gender' => 'Male',   'city' => 'BACOOR CITY',    'brgy' => 'MOLINO III',        'prov' => 'CAVITE',       'reg' => 'REGION IV-A', 'zip' => '4102', 'email' => 'zachary.serrano@gmail.com'],
            ['first' => 'KIARA DANIELLE',  'middle' => 'GUINTO',       'last' => 'MACARAEG',    'gender' => 'Female', 'city' => 'IMUS CITY',      'brgy' => 'MALAGASANG II-A',   'prov' => 'CAVITE',       'reg' => 'REGION IV-A', 'zip' => '4103', 'email' => 'kiara.macaraeg@gmail.com'],
            ['first' => 'NATHAN ANDREW',   'middle' => 'SAMONTE',      'last' => 'BELTRAN',     'gender' => 'Male',   'city' => 'DASMARIÑAS CITY','brgy' => 'SALITRAN II',       'prov' => 'CAVITE',       'reg' => 'REGION IV-A', 'zip' => '4114', 'email' => 'nathan.beltran@gmail.com'],
            ['first' => 'PATRICIA ELENA',  'middle' => 'CARANDANG',    'last' => 'LAUREL',      'gender' => 'Female', 'city' => 'MUNTINLUPA CITY','brgy' => 'CUPANG',            'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1771', 'email' => 'patricia.laurel@gmail.com'],
            ['first' => 'GIAN CARLO',      'middle' => 'VILLAFUERTE',  'last' => 'ENRILE',      'gender' => 'Male',   'city' => 'MUNTINLUPA CITY','brgy' => 'PUTATAN',           'prov' => 'METRO MANILA', 'reg' => 'NCR', 'zip' => '1772', 'email' => 'gian.enrile@gmail.com'],
        ];

        // =============================================================
        // 1. SEED FEU DILIMAN
        // =============================================================
        $this->dbSwitcher->switchToCampus('feu_diliman');
        $this->purgeApplicants();
        $syDiliman = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_DILIMAN);
        if (!$syDiliman) {
            $io->error('No active school year found for FEU Diliman.');
            return Command::FAILURE;
        }

        $io->section('Seeding FEU Diliman Applicants (1 per grade: Kinder to Grade 12)');
        foreach ($gradeDefinitions as $idx => $gDef) {
            $prof = $dilimanProfiles[$idx];
            $app = $this->generateSingleApplicant(
                'feu_diliman',
                ApplicantBed::CAMPUS_DILIMAN,
                $syDiliman,
                $gDef,
                $prof
            );
            $createdApplicants[] = [
                'campus' => 'FEU Diliman',
                'student_no' => $app->getStudentNumber(),
                'name' => $app->getFullName(),
                'grade' => $app->getGradeLevel(),
                'edu' => $app->getEducationType(),
                'strand' => $app->getTrackStrand() ?? 'N/A',
            ];
            $io->writeln(sprintf('  [FEU Diliman] Created %s: %s - %s', $app->getGradeLevel(), $app->getStudentNumber(), $app->getFullName()));
        }

        // =============================================================
        // 2. SEED FEU ALABANG
        // =============================================================
        $this->dbSwitcher->switchToCampus('feu_alabang');
        $this->purgeApplicants();
        $syAlabang = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_ALABANG);
        if (!$syAlabang) {
            $io->error('No active school year found for FEU Alabang.');
            return Command::FAILURE;
        }

        $io->section('Seeding FEU Alabang Applicants (1 per grade: Kinder to Grade 12)');
        foreach ($gradeDefinitions as $idx => $gDef) {
            $prof = $alabangProfiles[$idx];
            $app = $this->generateSingleApplicant(
                'feu_alabang',
                ApplicantBed::CAMPUS_ALABANG,
                $syAlabang,
                $gDef,
                $prof
            );
            $createdApplicants[] = [
                'campus' => 'FEU Alabang',
                'student_no' => $app->getStudentNumber(),
                'name' => $app->getFullName(),
                'grade' => $app->getGradeLevel(),
                'edu' => $app->getEducationType(),
                'strand' => $app->getTrackStrand() ?? 'N/A',
            ];
            $io->writeln(sprintf('  [FEU Alabang] Created %s: %s - %s', $app->getGradeLevel(), $app->getStudentNumber(), $app->getFullName()));
        }

        $io->newLine();
        $io->success(sprintf('Successfully generated %d applicants (13 Diliman + 13 Alabang) with zero profile images and strict dashless IDs!', count($createdApplicants)));

        $io->table(
            ['Campus', 'Student ID', 'Full Name', 'Grade Level', 'Education Level', 'Strand'],
            $createdApplicants
        );

        return Command::SUCCESS;
    }

    /**
     * @param array{grade: string, edu: string, strand: ?string, birth_year: int} $gDef
     * @param array{first: string, middle: string, last: string, gender: string, city: string, brgy: string, prov: string, reg: string, zip: string, email: string} $prof
     */
    private function generateSingleApplicant(
        string $campus,
        string $campusCode,
        SchoolYear $schoolYear,
        array $gDef,
        array $prof
    ): ApplicantBed {
        $applicant = new ApplicantBed();
        
        // Strict dashless student number generated via StudentIdGenerator
        $studentNo = $this->idGenerator->generateStudentNumber($campus, $schoolYear);
        $applicant->setStudentNumber($studentNo);
        $this->em->persist($applicant);

        $applicant->setCampus($campusCode);
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setAdmissionType($gDef['grade'] === 'Kinder' || $gDef['grade'] === 'Grade 1' || $gDef['grade'] === 'Grade 7' || $gDef['grade'] === 'Grade 11' ? 'New Student' : 'Transferee');
        $applicant->setAdmissionDate(new \DateTime('-' . rand(1, 30) . ' days'));
        $applicant->setSchoolYearOfEntry($schoolYear->getLabel());
        $applicant->setEducationType($gDef['edu']);
        $applicant->setGradeLevel($gDef['grade']);
        $applicant->setTrackStrand($gDef['strand']);

        // Generate realistic 12-digit LRN (Kinder can be null or valid)
        $lrn = ($gDef['grade'] === 'Kinder') ? null : ('1' . str_pad((string)rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT));
        $applicant->setLrn($lrn);

        $applicant->setExaminationScore((float)rand(85, 98));
        $applicant->setExaminationDate(new \DateTime('-' . rand(5, 20) . ' days'));

        $applicant->setLastName($prof['last']);
        $applicant->setFirstName($prof['first']);
        $applicant->setMiddleName($prof['middle']);
        $applicant->setSuffix(null);
        $applicant->setExtensionName(null);
        $applicant->setPreferredName(explode(' ', $prof['first'])[0]);

        // Birthdate matching grade level age
        $birthMonth = str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT);
        $birthDay = str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);
        $applicant->setBirthDate(new \DateTime("{$gDef['birth_year']}-{$birthMonth}-{$birthDay}"));
        $applicant->setBirthPlace($prof['city']);
        $applicant->setGender($prof['gender']);
        $applicant->setReligion('ROMAN CATHOLIC');
        $applicant->setCitizenship('FILIPINO');
        $applicant->setNationality('FILIPINO');
        $applicant->setCountryOfBirth('Philippines');
        $applicant->setCountryOfResidence('Philippines');
        $applicant->setPermanentCountry('Philippines');
        $applicant->setIndigenousGroup('None');

        $applicant->setMobileNumber('+63 9' . rand(15, 99) . rand(1000000, 9999999));
        $applicant->setLandLineNumber('028' . rand(1000000, 9999999));
        $applicant->setPersonalEmail($prof['email']);

        // Profile image explicitly NULL as instructed
        $applicant->setPhotoSlug(null);

        // Address
        $applicant->setCurrentRegion($prof['reg']);
        $applicant->setCurrentProvince($prof['prov']);
        $applicant->setCurrentCity($prof['city']);
        $applicant->setCurrentBarangay($prof['brgy']);
        $applicant->setCurrentAddress('Block ' . rand(1, 50) . ' Lot ' . rand(1, 40) . ' ' . $prof['last'] . ' St.');
        $applicant->setCurrentZip($prof['zip']);

        $applicant->setPermanentRegion($prof['reg']);
        $applicant->setPermanentProvince($prof['prov']);
        $applicant->setPermanentCity($prof['city']);
        $applicant->setPermanentBarangay($prof['brgy']);
        $applicant->setPermanentAddress($applicant->getCurrentAddress());
        $applicant->setPermanentZip($prof['zip']);

        $applicant->setGeneralAverage(number_format((float)rand(88, 96), 2));
        $prevGradeNum = (int)filter_var($gDef['grade'], FILTER_SANITIZE_NUMBER_INT);
        $applicant->setLastGradeCompleted($prevGradeNum > 1 ? ('Grade ' . ($prevGradeNum - 1)) : ($gDef['grade'] === 'Grade 1' ? 'Kinder' : 'N/A'));

        $sources = ['Facebook', 'Campus Tour', 'Friends', 'Website', 'Events'];
        $applicant->setMarketingSource($sources[array_rand($sources)]);
        $applicant->setDocumentsAgreed(true);
        $applicant->setDocumentsAgreedDate($schoolYear->getPromissoryDeadline() ?? new \DateTime('+60 days'));

        // 1. Father Guardian
        $father = new ApplicantBedGuardian();
        $father->setApplicant($applicant);
        $father->setGuardianType('FATHER');
        $father->setRelationship('FATHER');
        $father->setParentName('ROBERTO ' . $prof['middle'] . ' ' . $prof['last']);
        $fatherFatherJobs = ['ENGINEER', 'ACCOUNTANT', 'BUSINESSMAN', 'TEACHER', 'ARCHITECT', 'IT CONSULTANT'];
        $father->setOccupation($fatherFatherJobs[array_rand($fatherFatherJobs)]);
        $father->setContactNo('+63 9' . rand(15, 99) . rand(1000000, 9999999));
        $father->setEmail('roberto.' . strtolower($prof['last']) . '@gmail.com');
        $father->setDeceased(false);
        $father->setOFW(false);
        $father->setSameAsApplicant(true);
        $father->setCurrentAddress($applicant->getCurrentAddress());
        $father->setCurrentCity($applicant->getCurrentCity());
        $father->setCurrentProvince($applicant->getCurrentProvince());
        $father->setCurrentRegion($applicant->getCurrentRegion());
        $father->setCurrentZip($applicant->getCurrentZip());
        $applicant->addGuardian($father);
        $this->em->persist($father);

        // 2. Mother Guardian
        $mother = new ApplicantBedGuardian();
        $mother->setApplicant($applicant);
        $mother->setGuardianType('MOTHER');
        $mother->setRelationship('MOTHER');
        $mother->setParentName('MARIA ELENA ' . $prof['last']);
        $motherJobs = ['DOCTOR', 'NURSE', 'PHARMACIST', 'BANK MANAGER', 'HOMEMAKER', 'PROFESSOR'];
        $mother->setOccupation($motherJobs[array_rand($motherJobs)]);
        $mother->setContactNo('+63 9' . rand(15, 99) . rand(1000000, 9999999));
        $mother->setEmail('elena.' . strtolower($prof['last']) . '@gmail.com');
        $mother->setDeceased(false);
        $mother->setOFW(false);
        $mother->setSameAsApplicant(true);
        $mother->setCurrentAddress($applicant->getCurrentAddress());
        $mother->setCurrentCity($applicant->getCurrentCity());
        $mother->setCurrentProvince($applicant->getCurrentProvince());
        $mother->setCurrentRegion($applicant->getCurrentRegion());
        $mother->setCurrentZip($applicant->getCurrentZip());
        $applicant->addGuardian($mother);
        $this->em->persist($mother);

        // 3. Emergency Contact Guardian
        $guardian = new ApplicantBedGuardian();
        $guardian->setApplicant($applicant);
        $guardian->setGuardianType('GUARDIAN');
        $guardian->setRelationship('AUNT');
        $guardian->setParentName('TERESA ' . $prof['last']);
        $guardian->setOccupation('HUMAN RESOURCES MANAGER');
        $guardian->setContactNo('+63 9' . rand(15, 99) . rand(1000000, 9999999));
        $guardian->setEmail('teresa.' . strtolower($prof['last']) . '@gmail.com');
        $guardian->setDeceased(false);
        $guardian->setOFW(false);
        $guardian->setSameAsApplicant(true);
        $guardian->setCurrentAddress($applicant->getCurrentAddress());
        $guardian->setCurrentCity($applicant->getCurrentCity());
        $guardian->setCurrentProvince($applicant->getCurrentProvince());
        $guardian->setCurrentRegion($applicant->getCurrentRegion());
        $guardian->setCurrentZip($applicant->getCurrentZip());
        $applicant->addGuardian($guardian);
        $this->em->persist($guardian);

        // School History
        if ($gDef['grade'] !== 'Kinder') {
            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool($campus === 'feu_diliman' ? 'St. Joseph Academy Quezon City' : 'De La Salle Santiago Zobel School');
            $school->setSchoolType('Private');
            $school->setLevel($gDef['edu']);
            $school->setSchoolYear('2024-2025');
            $school->setAddressLine('123 Education Boulevard');
            $school->setCountry('Philippines');
            $school->setRegion($prof['reg']);
            $school->setProvince($prof['prov']);
            $school->setCity($prof['city']);
            $applicant->addSchool($school);
            $this->em->persist($school);
        }

        // Sibling
        $sibling = new ApplicantBedSibling();
        $sibling->setApplicant($applicant);
        $sibling->setSiblingName('MATEO ' . $prof['middle'] . ' ' . $prof['last']);
        $sibling->setIsFeuStudent(false);
        $sibling->setSchool('FEU High School');
        $applicant->addSibling($sibling);
        $this->em->persist($sibling);

        // Documents: seed compliant requirements matching active DocumentSetup for this grade
        $setups = $this->em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [$campusCode, null],
        ]);

        foreach ($setups as $ds) {
            $grades = $ds->getGradeLevels() ?? [];
            if (!empty($grades)) {
                $normG = array_map(fn($x) => strtolower(str_replace([' ', '_', '-'], '', (string)$x)), $grades);
                $currG = strtolower(str_replace([' ', '_', '-'], '', $gDef['grade']));
                if (!in_array('all', $normG, true) && !in_array($currG, $normG, true)) {
                    continue;
                }
            }

            // Skip foreign-only docs for local applicants
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

        return $applicant;
    }

    private function purgeApplicants(): void
    {
        $this->em->clear();
        $conn = $this->em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $conn->executeStatement('TRUNCATE TABLE bed_requirements');
        $conn->executeStatement('TRUNCATE TABLE bed_guardians');
        $conn->executeStatement('TRUNCATE TABLE bed_schools');
        $conn->executeStatement('TRUNCATE TABLE bed_siblings');
        $conn->executeStatement('TRUNCATE TABLE bed_passports');
        $conn->executeStatement('TRUNCATE TABLE bed_applicants');
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $this->em->clear();
    }
}
