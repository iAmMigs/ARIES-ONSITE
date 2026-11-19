<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\CampusDatabaseSwitcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase1DatabaseSeparationAndAuditTest extends KernelTestCase
{
    private CampusDatabaseSwitcher $switcher;
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
        $this->connection = $container->get('doctrine')->getConnection();
    }

    public function testCampusDatabaseSwitcherMapping(): void
    {
        $this->assertSame(CampusDatabaseSwitcher::DB_DILIMAN, $this->switcher->getDatabaseForCampus('feu_diliman'));
        $this->assertSame(CampusDatabaseSwitcher::DB_DILIMAN, $this->switcher->getDatabaseForCampus('FDIL'));
        $this->assertSame(CampusDatabaseSwitcher::DB_DILIMAN, $this->switcher->getDatabaseForCampus('diliman'));

        $this->assertSame(CampusDatabaseSwitcher::DB_ALABANG, $this->switcher->getDatabaseForCampus('feu_alabang'));
        $this->assertSame(CampusDatabaseSwitcher::DB_ALABANG, $this->switcher->getDatabaseForCampus('FALAB'));
        $this->assertSame(CampusDatabaseSwitcher::DB_ALABANG, $this->switcher->getDatabaseForCampus('alabang'));
    }

    public function testDynamicDatabaseSwitching(): void
    {
        $this->switcher->switchToCampus('feu_diliman');
        $dbDiliman = $this->connection->fetchOne('SELECT DATABASE()');
        $this->assertSame('aries_diliman_db', $dbDiliman);

        $this->switcher->switchToCampus('feu_alabang');
        $dbAlabang = $this->connection->fetchOne('SELECT DATABASE()');
        $this->assertSame('aries_alabang_db', $dbAlabang);
    }

    public function testExtendedAuditTablesExistInBothDatabases(): void
    {
        foreach (['aries_diliman_db', 'aries_alabang_db'] as $dbName) {
            $this->switcher->switchToDatabase($dbName);

            $tables = $this->connection->fetchFirstColumn(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = :db",
                ['db' => $dbName]
            );

            $this->assertContains('audit_admin_users', $tables, "audit_admin_users missing in $dbName");
            $this->assertContains('audit_school_year', $tables, "audit_school_year missing in $dbName");
            $this->assertContains('audit_document_setup', $tables, "audit_document_setup missing in $dbName");
        }
    }

    public function testAdminUsersAuditTriggerRecordsModuleChanges(): void
    {
        $this->switcher->switchToDatabase('aries_diliman_db');

        // Set simulated acting admin
        $this->connection->executeStatement("SET @app_user_emp_num = '999999'");

        // Insert temporary admin
        $testEmpNum = 888888;
        $this->connection->executeStatement(
            "DELETE FROM admin_users WHERE emp_num = :emp",
            ['emp' => $testEmpNum]
        );
        $this->connection->executeStatement(
            "DELETE FROM audit_admin_users WHERE target_emp_num = :emp",
            ['emp' => $testEmpNum]
        );

        $this->connection->executeStatement(
            "INSERT INTO admin_users (emp_num, email, roles, password, first_name, last_name, campus, tier, modules, is_active)
             VALUES (:emp, 'test_audit@feu.edu.ph', '[\"ROLE_ADMIN\"]', 'dummy_hash', 'AUDIT', 'TESTER', 'feu_diliman', 'staff_admin', '[\"dashboard\"]', 1)",
            ['emp' => $testEmpNum]
        );

        // Check insert audit row
        $insertAudit = $this->connection->fetchAssociative(
            "SELECT * FROM audit_admin_users WHERE target_emp_num = :emp AND audit_action = 'INSERT' ORDER BY audit_id DESC LIMIT 1",
            ['emp' => $testEmpNum]
        );

        $this->assertNotEmpty($insertAudit);
        $this->assertSame('999999', $insertAudit['emp_num']);
        $this->assertSame('["dashboard"]', $insertAudit['new_modules']);

        // Update modules
        $this->connection->executeStatement(
            "UPDATE admin_users SET modules = '[\"dashboard\",\"documents\"]' WHERE emp_num = :emp",
            ['emp' => $testEmpNum]
        );

        $updateAudit = $this->connection->fetchAssociative(
            "SELECT * FROM audit_admin_users WHERE target_emp_num = :emp AND audit_action = 'UPDATE' ORDER BY audit_id DESC LIMIT 1",
            ['emp' => $testEmpNum]
        );

        $this->assertNotEmpty($updateAudit);
        $this->assertSame('999999', $updateAudit['emp_num']);
        $this->assertSame('["dashboard"]', $updateAudit['old_modules']);
        $this->assertSame('["dashboard","documents"]', $updateAudit['new_modules']);

        // Cleanup
        $this->connection->executeStatement("DELETE FROM admin_users WHERE emp_num = :emp", ['emp' => $testEmpNum]);
        $this->connection->executeStatement("DELETE FROM audit_admin_users WHERE target_emp_num = :emp", ['emp' => $testEmpNum]);
        $this->connection->executeStatement("SET @app_user_emp_num = NULL");
    }

    public function testSchoolYearAuditTrigger(): void
    {
        $this->switcher->switchToDatabase('aries_diliman_db');
        $this->connection->executeStatement("SET @app_user_emp_num = '999999'");

        $this->connection->executeStatement(
            "INSERT INTO school_year (label, year_start, year_end, campus, is_active, enrollment_open, created_at)
             VALUES ('SY9999', 2098, 2099, 'FDIL', 0, 0, NOW())"
        );
        $newId = (int) $this->connection->lastInsertId();

        $auditRow = $this->connection->fetchAssociative(
            "SELECT * FROM audit_school_year WHERE school_year_id = :id AND audit_action = 'INSERT'",
            ['id' => $newId]
        );

        $this->assertNotEmpty($auditRow);
        $this->assertSame('999999', $auditRow['emp_num']);
        $this->assertSame('SY9999', $auditRow['label']);

        // Cleanup
        $this->connection->executeStatement("DELETE FROM school_year WHERE id = :id", ['id' => $newId]);
        $this->connection->executeStatement("DELETE FROM audit_school_year WHERE school_year_id = :id", ['id' => $newId]);
        $this->connection->executeStatement("SET @app_user_emp_num = NULL");
    }

    public function testDocumentSetupAuditTrigger(): void
    {
        $this->switcher->switchToDatabase('aries_diliman_db');
        $this->connection->executeStatement("SET @app_user_emp_num = '999999'");

        $this->connection->executeStatement(
            "INSERT INTO document_setup (document_name, slug, folder_name, campus, student_type, nationality_type, grade_levels)
             VALUES ('Test Requirement', 'test_req', 'test_req', 'FDIL', 'New Student', 'Filipino', '[\"all\"]')"
        );
        $newId = (int) $this->connection->lastInsertId();

        $auditRow = $this->connection->fetchAssociative(
            "SELECT * FROM audit_document_setup WHERE document_setup_id = :id AND audit_action = 'INSERT'",
            ['id' => $newId]
        );

        $this->assertNotEmpty($auditRow);
        $this->assertSame('999999', $auditRow['emp_num']);
        $this->assertSame('Test Requirement', $auditRow['document_name']);

        // Cleanup
        $this->connection->executeStatement("DELETE FROM document_setup WHERE id = :id", ['id' => $newId]);
        $this->connection->executeStatement("DELETE FROM audit_document_setup WHERE document_setup_id = :id", ['id' => $newId]);
        $this->connection->executeStatement("SET @app_user_emp_num = NULL");
    }
}

