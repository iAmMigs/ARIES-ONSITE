<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\CampusDatabaseSwitcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase3DummyDataTest extends KernelTestCase
{
    private \Doctrine\DBAL\Connection $connection;
    private CampusDatabaseSwitcher $switcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->connection = $container->get('doctrine')->getConnection();
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
    }

    public function testDummyAdminsExistInAssocLoginAndCampusDatabases(): void
    {
        $expectedAdmins = [
            '104829' => ['campus' => 'feu_diliman', 'db' => 'aries_diliman_db', 'first' => 'MARCO', 'last' => 'CASTILLO'],
            '107394' => ['campus' => 'feu_diliman', 'db' => 'aries_diliman_db', 'first' => 'ANGELA', 'last' => 'VILLANUEVA'],
            '205183' => ['campus' => 'feu_alabang', 'db' => 'aries_alabang_db', 'first' => 'RAFAEL', 'last' => 'NAVARRO'],
            '208641' => ['campus' => 'feu_alabang', 'db' => 'aries_alabang_db', 'first' => 'CLARISSE', 'last' => 'SORIANO'],
        ];

        $expectedMd5 = md5('admin123');

        foreach ($expectedAdmins as $empId => $meta) {
            $assocUser = $this->connection->fetchAssociative(
                'SELECT * FROM aries_assoc_login.assoc_login WHERE employee_id = :id',
                ['id' => $empId]
            );

            $this->assertIsArray($assocUser, "Dummy admin $empId must exist in aries_assoc_login.assoc_login");
            $this->assertSame($expectedMd5, $assocUser['pass_word'], "Password hash for $empId must match MD5('admin123')");

            $campusAdmin = $this->connection->fetchAssociative(
                "SELECT * FROM {$meta['db']}.admin_users WHERE emp_num = :id",
                ['id' => $empId]
            );

            $this->assertIsArray($campusAdmin, "Dummy admin $empId must exist in {$meta['db']}.admin_users");
            $this->assertSame($meta['first'], $campusAdmin['first_name']);
            $this->assertSame($meta['last'], $campusAdmin['last_name']);
            $this->assertSame($meta['campus'], $campusAdmin['campus']);
        }
    }

    public function testDilimanDummyApplicantsComplete(): void
    {
        $this->switcher->switchToCampus('feu_diliman');

        $applicants = $this->connection->fetchAllAssociative(
            'SELECT * FROM aries_diliman_db.bed_applicants'
        );

        if (count($applicants) === 0) {
            $this->markTestSkipped('Database has been cleared of applicants for manual user testing.');
        }

        $this->assertGreaterThanOrEqual(2, count($applicants), 'Diliman DB must have at least 2 seeded applicants');

        foreach ($applicants as $app) {
            $this->assertStringNotContainsString('-', $app['student_number'], 'Student number must not contain dashes');
            $this->assertNotEmpty($app['first_name']);
            $this->assertNotEmpty($app['last_name']);
            $this->assertNotEmpty($app['mobile_number']);
            $this->assertNotEmpty($app['personal_email']);
            $this->assertNull($app['photo_slug'], 'Dummy applicant must not have a profile image per requirements');

            // Guardians (required for ALL applicants including replacement applicants)
            $guardians = $this->connection->fetchAllAssociative(
                'SELECT * FROM aries_diliman_db.bed_guardians WHERE student_number = :snum',
                ['snum' => $app['student_number']]
            );
            $this->assertCount(3, $guardians, 'Each applicant must have 3 guardians (Father, Mother, Emergency Contact)');
            foreach ($guardians as $g) {
                $this->assertNotEmpty($g['ParentName']);
                $this->assertNotEmpty($g['Occupation'], "Occupation must be filled for guardian {$g['guardian_type']}");
                $this->assertNotEmpty($g['Email'], "Email must be filled for guardian {$g['guardian_type']}");
                $this->assertNotEmpty($g['ContactNo']);
            }

            // If this is a minimal replacement applicant (SY2627) with optional fields left blank
            if (($app['school_year_of_entry'] ?? '') === 'SY2627' || $app['middle_name'] === null || $app['middle_name'] === '') {
                continue;
            }

            $this->assertNotEmpty($app['middle_name']);
            $this->assertNotEmpty($app['birth_date']);
            $this->assertNotEmpty($app['birth_place']);
            $this->assertNotEmpty($app['gender']);
            $this->assertNotEmpty($app['religion']);
            $this->assertNotEmpty($app['citizenship']);
            $this->assertNotEmpty($app['nationality']);
            $this->assertNotEmpty($app['marketing_source']);
            $this->assertNotEmpty($app['last_grade_completed']);
            $this->assertNotEmpty($app['general_average']);
            $this->assertNotEmpty($app['current_region']);
            $this->assertNotEmpty($app['current_city']);
            $this->assertNotEmpty($app['current_address']);

            // School
            $schools = $this->connection->fetchAllAssociative(
                'SELECT * FROM aries_diliman_db.bed_schools WHERE student_number = :snum',
                ['snum' => $app['student_number']]
            );
            if ($app['grade_level'] !== 'Kinder') {
                $this->assertNotEmpty($schools, "Applicants in {$app['grade_level']} must have previous school history");
                foreach ($schools as $s) {
                    $this->assertNotEmpty($s['School']);
                    $this->assertNotEmpty($s['address_line'], 'School history must have address_line');
                }
            }
        }
    }

    public function testAlabangDummyApplicantsComplete(): void
    {
        $this->switcher->switchToCampus('feu_alabang');

        $applicants = $this->connection->fetchAllAssociative(
            'SELECT * FROM aries_alabang_db.bed_applicants'
        );

        if (count($applicants) === 0) {
            $this->markTestSkipped('Database has been cleared of applicants for manual user testing.');
        }

        $this->assertGreaterThanOrEqual(2, count($applicants), 'Alabang DB must have at least 2 seeded applicants');

        foreach ($applicants as $app) {
            $this->assertStringNotContainsString('-', $app['student_number'], 'Student number must not contain dashes');
            $this->assertNotEmpty($app['first_name']);
            $this->assertNotEmpty($app['last_name']);
            $this->assertNotEmpty($app['mobile_number']);
            $this->assertNotEmpty($app['personal_email']);
            $this->assertNull($app['photo_slug'], 'Dummy applicant must not have a profile image per requirements');

            // If this is a minimal applicant with optional fields left blank
            if ($app['middle_name'] === null || $app['middle_name'] === '') {
                $this->assertEmpty($app['middle_name']);
                $this->assertEmpty($app['birth_place']);
                $this->assertEmpty($app['last_grade_completed']);
                $this->assertEmpty($app['general_average']);
                $this->assertEmpty($app['current_region']);
                continue;
            }

            $this->assertNotEmpty($app['middle_name']);
            $this->assertNotEmpty($app['birth_date']);
            $this->assertNotEmpty($app['birth_place']);
            $this->assertNotEmpty($app['gender']);
            $this->assertNotEmpty($app['religion']);
            $this->assertNotEmpty($app['citizenship']);
            $this->assertNotEmpty($app['nationality']);
            $this->assertNotEmpty($app['marketing_source']);
            $this->assertNotEmpty($app['last_grade_completed']);
            $this->assertNotEmpty($app['general_average']);
            $this->assertNotEmpty($app['current_region']);
            $this->assertNotEmpty($app['current_city']);
            $this->assertNotEmpty($app['current_address']);

            // Guardians
            $guardians = $this->connection->fetchAllAssociative(
                'SELECT * FROM aries_alabang_db.bed_guardians WHERE student_number = :snum',
                ['snum' => $app['student_number']]
            );
            $this->assertCount(3, $guardians, 'Each applicant must have 3 guardians (Father, Mother, Emergency Contact)');
            foreach ($guardians as $g) {
                $this->assertNotEmpty($g['ParentName']);
                $this->assertNotEmpty($g['Occupation'], "Occupation must be filled for guardian {$g['guardian_type']}");
                $this->assertNotEmpty($g['Email'], "Email must be filled for guardian {$g['guardian_type']}");
                $this->assertNotEmpty($g['ContactNo']);
            }

            // School
            $schools = $this->connection->fetchAllAssociative(
                'SELECT * FROM aries_alabang_db.bed_schools WHERE student_number = :snum',
                ['snum' => $app['student_number']]
            );
            if ($app['grade_level'] !== 'Kinder') {
                $this->assertNotEmpty($schools, "Applicants in {$app['grade_level']} must have previous school history");
                foreach ($schools as $s) {
                    $this->assertNotEmpty($s['School']);
                    $this->assertNotEmpty($s['address_line'], 'School history must have address_line');
                }
            }
        }
    }
}
