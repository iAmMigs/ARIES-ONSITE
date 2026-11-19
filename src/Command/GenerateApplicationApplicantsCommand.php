<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\ApplicantBedRequirement;
use App\Entity\ApplicantBedSchool;
use App\Entity\ApplicantBedSibling;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\CampusDatabaseSwitcher;
use App\Service\DocumentComplianceService;
use App\Service\StudentIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Generates realistic applicants (5 for FEU Diliman and 5 for FEU Alabang)
 * populating all form fields (including optional and lookup-based fields)
 * as if filled out directly via the admissions application form.
 * Creates actual sample PDF files on disk for all submitted documents.
 */
#[AsCommand(
    name: 'app:generate-application-applicants',
    description: 'Generates 5 realistic applicants for FEU Diliman and 5 for FEU Alabang with full application form data, correct names, and sample PDF files.',
)]
class GenerateApplicationApplicantsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampusDatabaseSwitcher $dbSwitcher,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly StudentIdGenerator $idGenerator,
        private readonly DocumentComplianceService $complianceService,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generating Realistic Application Form Applicants (5 Diliman + 5 Alabang)');

        $createdSummary = [];

        // =============================================================
        // 1. FEU DILIMAN (5 Applicants with random grade levels)
        // =============================================================
        $this->dbSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_DILIMAN);
        $this->purgeCampusApplicants();

        $syDiliman = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_DILIMAN)
            ?? $this->schoolYearRepository->findOneBy(['campus' => SchoolYear::CAMPUS_DILIMAN, 'isEnrollmentOpen' => true]);

        if (!$syDiliman) {
            $io->error('No active or open school year found for FEU Diliman.');
            return Command::FAILURE;
        }

        $io->section(sprintf('Seeding 5 Applicants for FEU Diliman (%s)', $syDiliman->getLabel()));

        $dilimanData = [
            [
                'grade' => 'Kinder',
                'edu' => 'Kinder',
                'strand' => null,
                'birth_year' => 2021,
                'first' => 'CARLO MIGUEL',
                'middle' => 'BAUTISTA',
                'last' => 'DELA CRUZ',
                'preferred' => 'CARLO',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => null,
                'religion' => 'CATHOLIC',
                'lrn' => null,
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => null,
                'address' => [
                    'street' => '21 PISCES STREET, VILLA CECILIA SUBDIVISION',
                    'brgy' => 'Concepcion',
                    'city' => 'CITY OF BALIWAG',
                    'prov' => 'BULACAN',
                    'reg' => 'REGION III (CENTRAL LUZON)',
                    'zip' => '2312',
                ],
                'father' => [
                    'last_name' => 'DELA CRUZ',
                    'first_name' => 'ROBERTO',
                    'middle_name' => 'BAUTISTA',
                    'job' => 'CIVIL ENGINEER',
                    'contact' => '+63 917 354 7257',
                    'email' => 'roberto.delacruz@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'DELA CRUZ',
                    'first_name' => 'MARIA',
                    'middle_name' => 'ELENA',
                    'job' => 'CERTIFIED PUBLIC ACCOUNTANT',
                    'contact' => '+63 918 640 7976',
                    'email' => 'mariaelena.delacruz@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'REYES',
                    'first_name' => 'TERESA',
                    'middle_name' => 'DELA CRUZ',
                    'relation' => 'AUNT',
                    'job' => 'HUMAN RESOURCES MANAGER',
                    'contact' => '+63 920 847 1928',
                    'email' => 'teresa.reyes@gmail.com',
                ],
                'sibling' => [
                    'name' => 'MATEO BAUTISTA DELA CRUZ',
                    'school' => 'FEU High School',
                    'is_feu' => true,
                    'student_no' => '202301984',
                ],
                'marketing' => 'Social Media, Friends and Family, Website',
            ],
            [
                'grade' => 'Grade 3',
                'edu' => 'Grade School',
                'strand' => null,
                'birth_year' => 2017,
                'first' => 'SOFIA BEATRIZ',
                'middle' => 'MENDOZA',
                'last' => 'SANTOS',
                'preferred' => 'SOFIA',
                'gender' => ApplicantBed::GENDER_FEMALE,
                'suffix' => null,
                'religion' => 'CHRISTIAN',
                'lrn' => '109283746192',
                'adm_type' => ApplicantBed::TYPE_TRANSFEREE,
                'prev_school' => [
                    'name' => 'St. Joseph College of Quezon City',
                    'level' => 'Grade School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'E. Rodriguez Sr. Avenue',
                    'city' => 'QUEZON CITY',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '15 DAHLIA STREET, FAIRVIEW',
                    'brgy' => 'Batasan Hills',
                    'city' => 'QUEZON CITY',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1126',
                ],
                'father' => [
                    'last_name' => 'SANTOS',
                    'first_name' => 'ANTONIO',
                    'middle_name' => 'MENDOZA',
                    'job' => 'ARCHITECT',
                    'contact' => '+63 917 283 9102',
                    'email' => 'antonio.santos@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'SANTOS',
                    'first_name' => 'CAROLINA',
                    'middle_name' => 'MENDOZA',
                    'job' => 'MARKETING DIRECTOR',
                    'contact' => '+63 918 394 8201',
                    'email' => 'carolina.santos@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'MENDOZA',
                    'first_name' => 'BEATRIZ',
                    'middle_name' => null,
                    'relation' => 'GRANDMOTHER',
                    'job' => 'RETIRED PROFESSOR',
                    'contact' => '+63 920 482 9103',
                    'email' => 'beatriz.mendoza@gmail.com',
                ],
                'sibling' => [
                    'name' => 'LUCAS MENDOZA SANTOS',
                    'school' => 'FEU Roosevelt Marikina',
                    'is_feu' => true,
                    'student_no' => '202401827',
                ],
                'marketing' => 'School Fair, Website, Campus Tour',
            ],
            [
                'grade' => 'Grade 7',
                'edu' => 'Junior High School',
                'strand' => null,
                'birth_year' => 2013,
                'first' => 'ETHAN GABRIEL',
                'middle' => 'NAVARRO',
                'last' => 'GARCIA',
                'preferred' => 'ETHAN',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => null,
                'religion' => 'CATHOLIC',
                'lrn' => '108374625184',
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => [
                    'name' => 'Miriam College Grade School',
                    'level' => 'Grade School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Katipunan Avenue, Loyola Heights',
                    'city' => 'QUEZON CITY',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '42 MALAKAS STREET, DILIMAN',
                    'brgy' => 'Diliman',
                    'city' => 'QUEZON CITY',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1101',
                ],
                'father' => [
                    'last_name' => 'GARCIA',
                    'first_name' => 'GABRIEL',
                    'middle_name' => 'NAVARRO',
                    'job' => 'SOFTWARE ARCHITECT',
                    'contact' => '+63 917 583 0192',
                    'email' => 'gabriel.garcia@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'GARCIA',
                    'first_name' => 'ELENA',
                    'middle_name' => 'NAVARRO',
                    'job' => 'DENTIST',
                    'contact' => '+63 918 472 9182',
                    'email' => 'elena.garcia@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'GARCIA',
                    'first_name' => 'RICARDO',
                    'middle_name' => null,
                    'relation' => 'UNCLE',
                    'job' => 'FINANCIAL ANALYST',
                    'contact' => '+63 920 739 1827',
                    'email' => 'ricardo.garcia@gmail.com',
                ],
                'sibling' => [
                    'name' => 'JULIANA NAVARRO GARCIA',
                    'school' => 'Far Eastern University',
                    'is_feu' => true,
                    'student_no' => '202409182',
                ],
                'marketing' => 'Campus Tour, Social Media, School Visit',
            ],
            [
                'grade' => 'Grade 10',
                'edu' => 'Junior High School',
                'strand' => null,
                'birth_year' => 2010,
                'first' => 'CHLOE MAE',
                'middle' => 'VILLANUEVA',
                'last' => 'RAMOS',
                'preferred' => 'CHLOE',
                'gender' => ApplicantBed::GENDER_FEMALE,
                'suffix' => null,
                'religion' => 'BORN AGAIN',
                'lrn' => '107482910394',
                'adm_type' => ApplicantBed::TYPE_TRANSFEREE,
                'prev_school' => [
                    'name' => 'Marikina Catholic School',
                    'level' => 'Junior High School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Concepcion Uno',
                    'city' => 'CITY OF MARIKINA',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '88 MARIKINA HEIGHTS DRIVE',
                    'brgy' => 'Concepcion Uno',
                    'city' => 'CITY OF MARIKINA',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1807',
                ],
                'father' => [
                    'last_name' => 'RAMOS',
                    'first_name' => 'FERNANDO',
                    'middle_name' => 'VILLANUEVA',
                    'job' => 'BANK MANAGER',
                    'contact' => '+63 917 628 3910',
                    'email' => 'fernando.ramos@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'RAMOS',
                    'first_name' => 'MARICEL',
                    'middle_name' => 'VILLANUEVA',
                    'job' => 'PHARMACIST',
                    'contact' => '+63 918 839 2019',
                    'email' => 'maricel.ramos@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'VILLANUEVA',
                    'first_name' => 'LOURDES',
                    'middle_name' => null,
                    'relation' => 'AUNT',
                    'job' => 'REGISTERED NURSE',
                    'contact' => '+63 920 938 4710',
                    'email' => 'lourdes.villanueva@gmail.com',
                ],
                'sibling' => [
                    'name' => 'DANIEL VILLANUEVA RAMOS',
                    'school' => 'FEU Roosevelt Marikina',
                    'is_feu' => true,
                    'student_no' => '202104829',
                ],
                'marketing' => 'Friends and Family, Social Media',
            ],
            [
                'grade' => 'Grade 11',
                'edu' => 'Senior High School',
                'strand' => 'STEM',
                'birth_year' => 2009,
                'first' => 'LUCAS DANIEL',
                'middle' => 'DELA ROSA',
                'last' => 'AQUINO',
                'preferred' => 'LUCAS',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => 'JR',
                'religion' => 'CATHOLIC',
                'lrn' => '106392817450',
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => [
                    'name' => 'Antipolo National High School',
                    'level' => 'Junior High School',
                    'year' => '2024-2025',
                    'type' => 'Public',
                    'address' => 'Olalia Street, Mayamot',
                    'city' => 'CITY OF ANTIPOLO',
                    'prov' => 'RIZAL',
                    'reg' => 'REGION IV-A (CALABARZON)',
                ],
                'address' => [
                    'street' => '12 SUMULONG HIGHWAY, BRGY MAYAMOT',
                    'brgy' => 'Mayamot',
                    'city' => 'CITY OF ANTIPOLO',
                    'prov' => 'RIZAL',
                    'reg' => 'REGION IV-A (CALABARZON)',
                    'zip' => '1870',
                ],
                'father' => [
                    'last_name' => 'AQUINO',
                    'first_name' => 'LUCAS',
                    'middle_name' => 'DELA ROSA',
                    'job' => 'MECHANICAL ENGINEER',
                    'contact' => '+63 917 739 1029',
                    'email' => 'lucas.aquino.sr@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'AQUINO',
                    'first_name' => 'PATRICIA',
                    'middle_name' => 'DELA ROSA',
                    'job' => 'HIGH SCHOOL PRINCIPAL',
                    'contact' => '+63 918 948 1028',
                    'email' => 'patricia.aquino@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'DELA ROSA',
                    'first_name' => 'EDUARDO',
                    'middle_name' => null,
                    'relation' => 'UNCLE',
                    'job' => 'COMMERCIAL PILOT',
                    'contact' => '+63 920 182 9384',
                    'email' => 'eduardo.delarosa@gmail.com',
                ],
                'sibling' => [
                    'name' => 'BEATRICE DELA ROSA AQUINO',
                    'school' => 'Far Eastern University Diliman',
                    'is_feu' => true,
                    'student_no' => '202203918',
                ],
                'marketing' => 'Facebook, Website, Campus Open House',
            ],
        ];

        foreach ($dilimanData as $data) {
            $app = $this->createFullApplicant(
                CampusDatabaseSwitcher::CAMPUS_DILIMAN,
                ApplicantBed::CAMPUS_DILIMAN,
                $syDiliman,
                $data
            );

            $createdSummary[] = [
                'campus' => 'FEU Diliman',
                'student_no' => $app->getStudentNumber(),
                'name' => $app->getFullName(),
                'grade' => $app->getGradeLevel(),
                'edu' => $app->getEducationType(),
                'strand' => $app->getTrackStrand() ?? 'N/A',
                'city' => $app->getCurrentCity(),
            ];

            $io->writeln(sprintf('  [FEU Diliman] Created %s: %s - %s (%s)', $app->getGradeLevel(), $app->getStudentNumber(), $app->getFullName(), $app->getCurrentCity()));
        }

        // =============================================================
        // 2. FEU ALABANG (5 Applicants with random grade levels)
        // =============================================================
        $this->dbSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_ALABANG);
        $this->purgeCampusApplicants();

        $syAlabang = $this->schoolYearRepository->findActiveByCampus(SchoolYear::CAMPUS_ALABANG)
            ?? $this->schoolYearRepository->findOneBy(['campus' => SchoolYear::CAMPUS_ALABANG, 'isEnrollmentOpen' => true]);

        if (!$syAlabang) {
            $io->error('No active or open school year found for FEU Alabang.');
            return Command::FAILURE;
        }

        $io->section(sprintf('Seeding 5 Applicants for FEU Alabang (%s)', $syAlabang->getLabel()));

        $alabangData = [
            [
                'grade' => 'Grade 1',
                'edu' => 'Grade School',
                'strand' => null,
                'birth_year' => 2019,
                'first' => 'SEBASTIAN MATEO',
                'middle' => 'ROXAS',
                'last' => 'ALCANTARA',
                'preferred' => 'SEBASTIAN',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => null,
                'religion' => 'CATHOLIC',
                'lrn' => '119283746102',
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => [
                    'name' => 'De La Salle Santiago Zobel Pre-School',
                    'level' => 'Kindergarten',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Ayala Alabang Village',
                    'city' => 'CITY OF MUNTINLUPA',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '54 ACACIA AVENUE, AYALA ALABANG VILLAGE',
                    'brgy' => 'Alabang',
                    'city' => 'CITY OF MUNTINLUPA',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1780',
                ],
                'father' => [
                    'last_name' => 'ALCANTARA',
                    'first_name' => 'RAFAEL',
                    'middle_name' => 'ROXAS',
                    'job' => 'MANAGING DIRECTOR',
                    'contact' => '+63 917 839 2018',
                    'email' => 'rafael.alcantara@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'ALCANTARA',
                    'first_name' => 'ISABELLE',
                    'middle_name' => 'ROXAS',
                    'job' => 'DERMATOLOGIST',
                    'contact' => '+63 918 920 1827',
                    'email' => 'isabelle.alcantara@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'ROXAS',
                    'first_name' => 'VICTOR',
                    'middle_name' => null,
                    'relation' => 'UNCLE',
                    'job' => 'CORPORATE LAWYER',
                    'contact' => '+63 920 384 7192',
                    'email' => 'victor.roxas@gmail.com',
                ],
                'sibling' => [
                    'name' => 'CAMILLA ROXAS ALCANTARA',
                    'school' => 'FEU Alabang',
                    'is_feu' => true,
                    'student_no' => '202305829',
                ],
                'marketing' => 'Friends and Family, Social Media, School Website',
            ],
            [
                'grade' => 'Grade 5',
                'edu' => 'Grade School',
                'strand' => null,
                'birth_year' => 2015,
                'first' => 'ALTHEA BEATRICE',
                'middle' => 'PINEDA',
                'last' => 'MERCADO',
                'preferred' => 'ALTHEA',
                'gender' => ApplicantBed::GENDER_FEMALE,
                'suffix' => null,
                'religion' => 'CHRISTIAN',
                'lrn' => '115293847561',
                'adm_type' => ApplicantBed::TYPE_TRANSFEREE,
                'prev_school' => [
                    'name' => 'Southville International School',
                    'level' => 'Grade School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Luxembourg Street, BF Homes International',
                    'city' => 'CITY OF LAS PIÑAS',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '102 PILAR EXECUTIVE VILLAGE, BRGY PILAR',
                    'brgy' => 'Pilar',
                    'city' => 'CITY OF LAS PIÑAS',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1740',
                ],
                'father' => [
                    'last_name' => 'MERCADO',
                    'first_name' => 'BERNARDO',
                    'middle_name' => 'PINEDA',
                    'job' => 'HOTEL GENERAL MANAGER',
                    'contact' => '+63 917 928 3019',
                    'email' => 'bernardo.mercado@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'MERCADO',
                    'first_name' => 'CORAZON',
                    'middle_name' => 'PINEDA',
                    'job' => 'EXECUTIVE CHEF',
                    'contact' => '+63 918 830 1928',
                    'email' => 'corazon.mercado@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'PINEDA',
                    'first_name' => 'MARIA CRISTINA',
                    'middle_name' => null,
                    'relation' => 'AUNT',
                    'job' => 'EVENT PLANNER',
                    'contact' => '+63 920 491 8273',
                    'email' => 'cristina.pineda@gmail.com',
                ],
                'sibling' => [
                    'name' => 'JOAQUIN PINEDA MERCADO',
                    'school' => 'FEU Alabang',
                    'is_feu' => true,
                    'student_no' => '202208194',
                ],
                'marketing' => 'Website, School Fair, Word of Mouth',
            ],
            [
                'grade' => 'Grade 8',
                'edu' => 'Junior High School',
                'strand' => null,
                'birth_year' => 2012,
                'first' => 'CALEB JOSHUA',
                'middle' => 'CORTEZ',
                'last' => 'RIVERA',
                'preferred' => 'CALEB',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => null,
                'religion' => 'CATHOLIC',
                'lrn' => '112839401928',
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => [
                    'name' => 'San Beda College Alabang',
                    'level' => 'Junior High School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Don Manolo Boulevard, Alabang Hills',
                    'city' => 'CITY OF MUNTINLUPA',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                ],
                'address' => [
                    'street' => '77 AGUIRRE AVENUE, BF HOMES',
                    'brgy' => 'BF Homes',
                    'city' => 'CITY OF PARAÑAQUE',
                    'prov' => 'METRO MANILA',
                    'reg' => 'NATIONAL CAPITAL REGION (NCR)',
                    'zip' => '1700',
                ],
                'father' => [
                    'last_name' => 'RIVERA',
                    'first_name' => 'JOSHUA',
                    'middle_name' => 'CORTEZ',
                    'job' => 'FINANCE VICE PRESIDENT',
                    'contact' => '+63 917 102 9384',
                    'email' => 'joshua.rivera@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'RIVERA',
                    'first_name' => 'MONICA',
                    'middle_name' => 'CORTEZ',
                    'job' => 'PEDIATRICIAN',
                    'contact' => '+63 918 293 8471',
                    'email' => 'monica.rivera@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'CORTEZ',
                    'first_name' => 'ALEXANDER',
                    'middle_name' => null,
                    'relation' => 'UNCLE',
                    'job' => 'DATA SCIENTIST',
                    'contact' => '+63 920 582 9102',
                    'email' => 'alexander.cortez@gmail.com',
                ],
                'sibling' => [
                    'name' => 'BIANCA CORTEZ RIVERA',
                    'school' => 'FEU Alabang',
                    'is_feu' => true,
                    'student_no' => '202206918',
                ],
                'marketing' => 'Campus Tour, Friends and Family, Facebook',
            ],
            [
                'grade' => 'Grade 11',
                'edu' => 'Senior High School',
                'strand' => 'ABM',
                'birth_year' => 2009,
                'first' => 'HANNAH DOMINIQUE',
                'middle' => 'DE LEON',
                'last' => 'SILVA',
                'preferred' => 'HANNAH',
                'gender' => ApplicantBed::GENDER_FEMALE,
                'suffix' => null,
                'religion' => 'CATHOLIC',
                'lrn' => '109384726150',
                'adm_type' => ApplicantBed::TYPE_NEW_STUDENT,
                'prev_school' => [
                    'name' => 'Brent International School Manila',
                    'level' => 'Junior High School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'Brentville Industrial Park, Mamplasan',
                    'city' => 'CITY OF BIÑAN',
                    'prov' => 'LAGUNA',
                    'reg' => 'REGION IV-A (CALABARZON)',
                ],
                'address' => [
                    'street' => '24 SANTA ROSA ESTATE, BRGY BALIBAGO',
                    'brgy' => 'Balibago',
                    'city' => 'CITY OF SANTA ROSA',
                    'prov' => 'LAGUNA',
                    'reg' => 'REGION IV-A (CALABARZON)',
                    'zip' => '4026',
                ],
                'father' => [
                    'last_name' => 'SILVA',
                    'first_name' => 'DOMINIC',
                    'middle_name' => 'DE LEON',
                    'job' => 'SUPPLY CHAIN DIRECTOR',
                    'contact' => '+63 917 394 8201',
                    'email' => 'dominic.silva@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'SILVA',
                    'first_name' => 'VALERIE',
                    'middle_name' => 'DE LEON',
                    'job' => 'COMMERCIAL REAL ESTATE BROKER',
                    'contact' => '+63 918 482 9102',
                    'email' => 'valerie.silva@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'DE LEON',
                    'first_name' => 'ANGELA',
                    'middle_name' => null,
                    'relation' => 'AUNT',
                    'job' => 'HUMAN RESOURCES ADVISOR',
                    'contact' => '+63 920 673 8192',
                    'email' => 'angela.deleon@gmail.com',
                ],
                'sibling' => [
                    'name' => 'CHRISTIAN DE LEON SILVA',
                    'school' => 'FEU Cavite',
                    'is_feu' => true,
                    'student_no' => '202309182',
                ],
                'marketing' => 'Website, Social Media, School Presentation',
            ],
            [
                'grade' => 'Grade 12',
                'edu' => 'Senior High School',
                'strand' => 'HUMSS',
                'birth_year' => 2008,
                'first' => 'GABRIEL DOMINIC',
                'middle' => 'TOLENTINO',
                'last' => 'VEGA',
                'preferred' => 'GABRIEL',
                'gender' => ApplicantBed::GENDER_MALE,
                'suffix' => null,
                'religion' => 'PROTESTANT',
                'lrn' => '108293847192',
                'adm_type' => ApplicantBed::TYPE_TRANSFEREE,
                'prev_school' => [
                    'name' => 'De La Salle University - Dasmariñas',
                    'level' => 'Senior High School',
                    'year' => '2024-2025',
                    'type' => 'Private',
                    'address' => 'DBB-B, City of Dasmariñas',
                    'city' => 'CITY OF DASMARIÑAS',
                    'prov' => 'CAVITE',
                    'reg' => 'REGION IV-A (CALABARZON)',
                ],
                'address' => [
                    'street' => '18 MOLINO BOULEVARD, MEADOWOOD VILLAGE',
                    'brgy' => 'Molino III',
                    'city' => 'CITY OF BACOOR',
                    'prov' => 'CAVITE',
                    'reg' => 'REGION IV-A (CALABARZON)',
                    'zip' => '4102',
                ],
                'father' => [
                    'last_name' => 'VEGA',
                    'first_name' => 'DOMINIC',
                    'middle_name' => 'TOLENTINO',
                    'job' => 'INFORMATION SECURITY SPECIALIST',
                    'contact' => '+63 917 482 9182',
                    'email' => 'dominic.vega@gmail.com',
                ],
                'mother' => [
                    'last_name' => 'VEGA',
                    'first_name' => 'MARIA TERESA',
                    'middle_name' => 'TOLENTINO',
                    'job' => 'COLLEGE DEAN',
                    'contact' => '+63 918 573 9182',
                    'email' => 'teresa.vega@gmail.com',
                ],
                'guardian' => [
                    'last_name' => 'TOLENTINO',
                    'first_name' => 'RAMON',
                    'middle_name' => null,
                    'relation' => 'UNCLE',
                    'job' => 'FINANCIAL AUDITOR',
                    'contact' => '+63 920 782 9103',
                    'email' => 'ramon.tolentino@gmail.com',
                ],
                'sibling' => [
                    'name' => 'SOPHIA TOLENTINO VEGA',
                    'school' => 'FEU Alabang',
                    'is_feu' => true,
                    'student_no' => '202308192',
                ],
                'marketing' => 'Campus Tour, Social Media, Friends and Family',
            ],
        ];

        foreach ($alabangData as $data) {
            $app = $this->createFullApplicant(
                CampusDatabaseSwitcher::CAMPUS_ALABANG,
                ApplicantBed::CAMPUS_ALABANG,
                $syAlabang,
                $data
            );

            $createdSummary[] = [
                'campus' => 'FEU Alabang',
                'student_no' => $app->getStudentNumber(),
                'name' => $app->getFullName(),
                'grade' => $app->getGradeLevel(),
                'edu' => $app->getEducationType(),
                'strand' => $app->getTrackStrand() ?? 'N/A',
                'city' => $app->getCurrentCity(),
            ];

            $io->writeln(sprintf('  [FEU Alabang] Created %s: %s - %s (%s)', $app->getGradeLevel(), $app->getStudentNumber(), $app->getFullName(), $app->getCurrentCity()));
        }

        $io->newLine();
        $io->success(sprintf('Successfully generated %d applicants (5 Diliman + 5 Alabang) with zero profile images, properly parsed guardian names, and sample PDF files!', count($createdSummary)));

        $io->table(
            ['Campus', 'Student ID', 'Full Name', 'Grade Level', 'Education Level', 'Strand', 'City/Municipality'],
            $createdSummary
        );

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createFullApplicant(
        string $campusSlug,
        string $campusCode,
        SchoolYear $schoolYear,
        array $data
    ): ApplicantBed {
        $applicant = new ApplicantBed();

        // 1. Generate institutional student number
        $studentNo = $this->idGenerator->generateStudentNumber($campusSlug, $schoolYear);
        $applicant->setStudentNumber($studentNo);
        $this->em->persist($applicant);

        // 2. Academic selections
        $applicant->setCampus($campusCode);
        $applicant->setAdmissionStatus(ApplicantBed::STATUS_PENDING);
        $applicant->setAdmissionDate(new \DateTime('-' . rand(2, 14) . ' days'));
        $applicant->setSchoolYearOfEntry($schoolYear->getLabel());
        $applicant->setEducationType((string)$data['edu']);
        $applicant->setGradeLevel((string)$data['grade']);
        $applicant->setTrackStrand($data['strand'] !== null ? (string)$data['strand'] : null);
        $applicant->setAdmissionType((string)$data['adm_type']);
        $applicant->setLrn($data['lrn'] !== null ? (string)$data['lrn'] : null);

        // Academic performance
        $applicant->setGeneralAverage(number_format((float)rand(89, 96) + (rand(0, 99) / 100), 2));
        $prevGradeNum = (int)filter_var((string)$data['grade'], FILTER_SANITIZE_NUMBER_INT);
        $applicant->setLastGradeCompleted($prevGradeNum > 1 ? ('Grade ' . ($prevGradeNum - 1)) : ($data['grade'] === 'Grade 1' ? 'Kinder' : 'N/A'));
        $applicant->setExaminationScore((float)rand(86, 98));
        $applicant->setExaminationDate(new \DateTime('-' . rand(3, 10) . ' days'));

        // 3. Personal information
        $applicant->setFirstName(strtoupper((string)$data['first']));
        $applicant->setMiddleName(strtoupper((string)$data['middle']));
        $applicant->setLastName(strtoupper((string)$data['last']));
        $applicant->setPreferredName(strtoupper((string)$data['preferred']));
        $applicant->setExtensionName($data['suffix'] !== null ? strtoupper((string)$data['suffix']) : null);
        $applicant->setSuffix($data['suffix'] !== null ? strtoupper((string)$data['suffix']) : null);

        $birthMonth = str_pad((string)rand(1, 12), 2, '0', STR_PAD_LEFT);
        $birthDay = str_pad((string)rand(1, 28), 2, '0', STR_PAD_LEFT);
        $applicant->setBirthDate(new \DateTime("{$data['birth_year']}-{$birthMonth}-{$birthDay}"));
        $applicant->setBirthPlace(strtoupper((string)$data['address']['city']));
        $applicant->setGender((string)$data['gender']);

        // Lookup-based fields
        $applicant->setReligion(strtoupper((string)$data['religion']));
        $applicant->setCitizenship('FILIPINO');
        $applicant->setNationality('FILIPINO');
        $applicant->setCountryOfBirth('Philippines');
        $applicant->setCountryOfResidence('Philippines');
        $applicant->setPermanentCountry('Philippines');
        $applicant->setIndigenousGroup('None');

        // Contact info
        $applicant->setMobileNumber('+63 9' . rand(15, 99) . ' ' . rand(100, 999) . ' ' . rand(1000, 9999));
        $applicant->setLandLineNumber('028 ' . rand(100, 999) . ' ' . rand(1000, 9999));
        $cleanFirst = str_replace(' ', '', strtolower((string)$data['first']));
        $cleanLast = str_replace(' ', '', strtolower((string)$data['last']));
        $applicant->setPersonalEmail("{$cleanFirst}.{$cleanLast}@gmail.com");

        // Profile picture strictly NULL
        $applicant->setPhotoSlug(null);

        // 4. Current & Permanent Addresses (Both complete and populated from lookups)
        $addr = $data['address'];
        $applicant->setCurrentRegion((string)$addr['reg']);
        $applicant->setCurrentProvince((string)$addr['prov']);
        $applicant->setCurrentCity((string)$addr['city']);
        $applicant->setCurrentBarangay((string)$addr['brgy']);
        $applicant->setCurrentAddress(strtoupper((string)$addr['street']));
        $applicant->setCurrentZip((string)$addr['zip']);

        $applicant->setPermanentRegion((string)$addr['reg']);
        $applicant->setPermanentProvince((string)$addr['prov']);
        $applicant->setPermanentCity((string)$addr['city']);
        $applicant->setPermanentBarangay((string)$addr['brgy']);
        $applicant->setPermanentAddress(strtoupper((string)$addr['street']));
        $applicant->setPermanentZip((string)$addr['zip']);

        // Survey and Policy
        $applicant->setMarketingSource((string)$data['marketing']);
        $applicant->setDocumentsAgreed(true);
        $deadline = $schoolYear->getPromissoryDeadline() ?? new \DateTime('+60 days');
        $applicant->setDocumentsAgreedDate($deadline);

        // 5. Guardians (Father, Mother, Emergency Contact)
        // Store parentName in standardized LASTNAME, FIRSTNAME MIDDLENAME format for Father and Mother
        $fLname = strtoupper((string)$data['father']['last_name']);
        $fFname = strtoupper((string)$data['father']['first_name']);
        $fMname = strtoupper((string)($data['father']['middle_name'] ?? ''));
        $fatherFullName = $fMname !== '' ? "{$fLname}, {$fFname} {$fMname}" : "{$fLname}, {$fFname}";

        $mLname = strtoupper((string)$data['mother']['last_name']);
        $mFname = strtoupper((string)$data['mother']['first_name']);
        $mMname = strtoupper((string)($data['mother']['middle_name'] ?? ''));
        $motherFullName = $mMname !== '' ? "{$mLname}, {$mFname} {$mMname}" : "{$mLname}, {$mFname}";

        $gLname = strtoupper((string)$data['guardian']['last_name']);
        $gFname = strtoupper((string)$data['guardian']['first_name']);
        $gMname = strtoupper((string)($data['guardian']['middle_name'] ?? ''));
        $guardianFullName = $gMname !== '' ? "{$gFname} {$gMname} {$gLname}" : "{$gFname} {$gLname}";

        $guardiansConfig = [
            'FATHER' => [
                'type' => 'FATHER',
                'rel' => 'FATHER',
                'name' => $fatherFullName,
                'job' => strtoupper((string)$data['father']['job']),
                'contact' => (string)$data['father']['contact'],
                'email' => strtolower((string)$data['father']['email']),
            ],
            'MOTHER' => [
                'type' => 'MOTHER',
                'rel' => 'MOTHER',
                'name' => $motherFullName,
                'job' => strtoupper((string)$data['mother']['job']),
                'contact' => (string)$data['mother']['contact'],
                'email' => strtolower((string)$data['mother']['email']),
            ],
            'GUARDIAN' => [
                'type' => 'GUARDIAN',
                'rel' => strtoupper((string)$data['guardian']['relation']),
                'name' => $guardianFullName,
                'job' => strtoupper((string)$data['guardian']['job']),
                'contact' => (string)$data['guardian']['contact'],
                'email' => strtolower((string)$data['guardian']['email']),
            ],
        ];

        foreach ($guardiansConfig as $gConf) {
            $guardian = new ApplicantBedGuardian();
            $guardian->setApplicant($applicant);
            $guardian->setGuardianType($gConf['type']);
            $guardian->setRelationship($gConf['rel']);
            $guardian->setParentName($gConf['name']);
            $guardian->setOccupation($gConf['job']);
            $guardian->setContactNo($gConf['contact']);
            $guardian->setEmail($gConf['email']);
            $guardian->setNationality('FILIPINO');
            $guardian->setDeceased(false);
            $guardian->setOFW(false);
            $guardian->setSameAsApplicant(true);

            // Populate complete address directly on guardian
            $guardian->setCurrentRegion((string)$addr['reg']);
            $guardian->setCurrentProvince((string)$addr['prov']);
            $guardian->setCurrentCity((string)$addr['city']);
            $guardian->setCurrentBarangay((string)$addr['brgy']);
            $guardian->setCurrentAddress(strtoupper((string)$addr['street']));
            $guardian->setCurrentZip((string)$addr['zip']);

            $guardian->setPermanentRegion((string)$addr['reg']);
            $guardian->setPermanentProvince((string)$addr['prov']);
            $guardian->setPermanentCity((string)$addr['city']);
            $guardian->setPermanentBarangay((string)$addr['brgy']);
            $guardian->setPermanentAddress(strtoupper((string)$addr['street']));
            $guardian->setPermanentZip((string)$addr['zip']);

            $applicant->addGuardian($guardian);
            $this->em->persist($guardian);
        }

        // 6. Sibling
        if (!empty($data['sibling'])) {
            $sib = new ApplicantBedSibling();
            $sib->setApplicant($applicant);
            $sib->setSiblingName(strtoupper((string)$data['sibling']['name']));
            $sib->setSchool((string)$data['sibling']['school']);
            $sib->setIsFeuStudent((bool)$data['sibling']['is_feu']);
            $sib->setFeuStudentNo($data['sibling']['student_no'] !== null ? (string)$data['sibling']['student_no'] : null);
            $applicant->addSibling($sib);
            $this->em->persist($sib);
        }

        // 7. Educational History
        if (!empty($data['prev_school'])) {
            $ps = $data['prev_school'];
            $school = new ApplicantBedSchool();
            $school->setApplicant($applicant);
            $school->setSchool(strtoupper((string)$ps['name']));
            $school->setLevel((string)$ps['level']);
            $school->setSchoolYear((string)$ps['year']);
            $school->setSchoolType((string)$ps['type']);
            $school->setAddressLine((string)$ps['address']);
            $school->setCity((string)$ps['city']);
            $school->setProvince((string)$ps['prov']);
            $school->setRegion((string)$ps['reg']);
            $school->setCountry('Philippines');
            $school->setIsInternational(false);
            $applicant->addSchool($school);
            $this->em->persist($school);
        }

        // 8. Documents / Requirements setup
        $applicableSetups = $this->complianceService->getApplicableDocuments($applicant);
        foreach ($applicableSetups as $ds) {
            $slug = (string)$ds->getSlug();

            // Profile photo is strictly missing/promissory
            if (in_array($slug, ['photo_2x2', '2x2_id_picture', 'profile_picture'], true) || str_contains($slug, 'photo') || str_contains($slug, '2x2')) {
                $req = new ApplicantBedRequirement();
                $req->setApplicant($applicant);
                $req->setSlug($slug);
                $req->setRequirement($ds->getDocumentName());
                $req->setStatus(ApplicantBedRequirement::STATUS_PENDING);
                $req->setDateSubmitted(null);
                $req->setStoredFileName(null);
                $req->setIsDeleted(false);
                $applicant->addRequirement($req);
                $this->em->persist($req);
                continue;
            }

            // Simulate PSA and Form 137 as submitted, others as pending
            $isSubmitted = in_array($slug, ['req_psa', 'psa', 'req_form_137_transcript_of_records_'], true);

            $req = new ApplicantBedRequirement();
            $req->setApplicant($applicant);
            $req->setSlug($slug);
            $req->setRequirement($ds->getDocumentName());
            $req->setIsDeleted(false);

            if ($isSubmitted) {
                $req->setStatus(ApplicantBedRequirement::STATUS_SUBMITTED);
                $req->setDateSubmitted(new \DateTime('-' . rand(1, 5) . ' days'));
                $folder = $ds->getFolderName() ?: 'onsite-psa';
                $storedFileName = 'uploads/' . $folder . '/' . $studentNo . '-' . strtoupper($slug) . '.pdf';
                $req->setStoredFileName($storedFileName);

                // Create physical sample PDF file on disk
                $physicalPath = $this->projectDir . '/public/' . $storedFileName;
                $this->createSamplePdf($physicalPath, 'Sample Document: ' . $ds->getDocumentName());
            } else {
                $req->setStatus(ApplicantBedRequirement::STATUS_PENDING);
                $req->setDateSubmitted(null);
                $req->setStoredFileName(null);
            }

            $applicant->addRequirement($req);
            $this->em->persist($req);
        }

        // Synchronize compliance status with promissory deadline
        $this->complianceService->syncComplianceStatus($applicant, $deadline);

        $this->em->flush();

        return $applicant;
    }

    /**
     * Creates a lightweight, valid PDF file containing sample text on disk.
     */
    private function createSamplePdf(string $filePath, string $title = 'Sample Document'): void
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $stream = "BT\n/F1 22 Tf\n72 720 Td\n(" . addcslashes($title, "()\\") . ") Tj\n/F1 12 Tf\n72 680 Td\n(This is a valid sample document for admissions testing.) Tj\nET";
        $streamLen = strlen($stream);

        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $obj4 = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";
        $obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        $offsets[1] = strlen($pdf);
        $pdf .= $obj1;
        $offsets[2] = strlen($pdf);
        $pdf .= $obj2;
        $offsets[3] = strlen($pdf);
        $pdf .= $obj3;
        $offsets[4] = strlen($pdf);
        $pdf .= $obj4;
        $offsets[5] = strlen($pdf);
        $pdf .= $obj5;

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        file_put_contents($filePath, $pdf);
    }

    private function purgeCampusApplicants(): void
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
