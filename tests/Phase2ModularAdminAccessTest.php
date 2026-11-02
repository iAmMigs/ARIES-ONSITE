<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\AdminUser;
use App\Service\CampusDatabaseSwitcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase2ModularAdminAccessTest extends KernelTestCase
{
    private \Doctrine\ORM\EntityManagerInterface $em;
    private CampusDatabaseSwitcher $switcher;
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
        $this->connection = $container->get('doctrine')->getConnection();
    }

    public function testMasterAdminHasAllModules(): void
    {
        $master = new AdminUser();
        $master->setTier(AdminUser::TIER_MASTER);

        $this->assertTrue($master->hasModule(AdminUser::MODULE_DASHBOARD));
        $this->assertTrue($master->hasModule(AdminUser::MODULE_REGISTRATIONS));
        $this->assertTrue($master->hasModule(AdminUser::MODULE_DOCUMENTS));
        $this->assertTrue($master->hasModule(AdminUser::MODULE_SCHOOL_YEAR));
        $this->assertTrue($master->hasModule(AdminUser::MODULE_ADMIN_ACCESS));

        $this->assertCount(5, $master->getModules());
        $this->assertTrue($master->canManageAdmins());
    }

    public function testModularAdminPermissions(): void
    {
        $admin = new AdminUser();
        $admin->setTier(AdminUser::TIER_STAFF);
        $admin->setModules(['documents', 'admin_access']);

        $this->assertTrue($admin->hasModule(AdminUser::MODULE_DOCUMENTS));
        $this->assertTrue($admin->hasModule(AdminUser::MODULE_ADMIN_ACCESS));
        $this->assertFalse($admin->hasModule(AdminUser::MODULE_DASHBOARD));
        $this->assertFalse($admin->hasModule(AdminUser::MODULE_REGISTRATIONS));
        $this->assertFalse($admin->hasModule(AdminUser::MODULE_SCHOOL_YEAR));

        $this->assertTrue($admin->canManageAdmins());

        // Update modules to remove admin_access
        $admin->setModules(['dashboard']);
        $this->assertTrue($admin->hasModule(AdminUser::MODULE_DASHBOARD));
        $this->assertFalse($admin->hasModule(AdminUser::MODULE_ADMIN_ACCESS));
        $this->assertFalse($admin->canManageAdmins());
    }

    public function testDatabasePersistenceOfModularAdminAndAuditTrail(): void
    {
        $this->switcher->switchToDatabase('aries_diliman_db');
        $this->connection->executeStatement("SET @app_user_emp_num = '1'"); // Master admin acting

        $testEmpNum = 777777;
        $this->connection->executeStatement("DELETE FROM admin_users WHERE emp_num = :emp", ['emp' => $testEmpNum]);
        $this->connection->executeStatement("DELETE FROM audit_admin_users WHERE target_emp_num = :emp", ['emp' => $testEmpNum]);

        $admin = new AdminUser();
        $admin->setEmpNum($testEmpNum);
        $admin->setEmail('modulartest@feudiliman.edu.ph');
        $admin->setFirstName('MODULAR');
        $admin->setLastName('ADMIN');
        $admin->setCampus('feu_diliman');
        $admin->setTier(AdminUser::TIER_STAFF);
        $admin->setModules(['documents', 'admin_access']);
        $admin->setPassword('dummy');
        $admin->setIsActive(true);

        $this->em->persist($admin);
        $this->em->flush();

        // 1. Verify admin_users table row has modules json
        $storedAdmin = $this->em->find(AdminUser::class, $testEmpNum);
        $this->assertNotNull($storedAdmin);
        $this->assertTrue($storedAdmin->hasModule('documents'));
        $this->assertTrue($storedAdmin->hasModule('admin_access'));
        $this->assertFalse($storedAdmin->hasModule('dashboard'));

        // 2. Verify audit_admin_users received INSERT audit with new_modules
        $auditInsert = $this->connection->fetchAssociative(
            "SELECT * FROM audit_admin_users WHERE target_emp_num = :emp AND audit_action = 'INSERT'",
            ['emp' => $testEmpNum]
        );
        $this->assertNotEmpty($auditInsert);
        $this->assertSame('1', $auditInsert['emp_num']);
        $this->assertStringContainsString('documents', $auditInsert['new_modules']);
        $this->assertStringContainsString('admin_access', $auditInsert['new_modules']);

        // 3. Update modules
        $storedAdmin->setModules(['dashboard', 'registrations']);
        $this->em->flush();

        // 4. Verify audit_admin_users received UPDATE audit with old_modules and new_modules
        $auditUpdate = $this->connection->fetchAssociative(
            "SELECT * FROM audit_admin_users WHERE target_emp_num = :emp AND audit_action = 'UPDATE' ORDER BY audit_id DESC LIMIT 1",
            ['emp' => $testEmpNum]
        );
        $this->assertNotEmpty($auditUpdate);
        $this->assertStringContainsString('documents', $auditUpdate['old_modules']);
        $this->assertStringContainsString('dashboard', $auditUpdate['new_modules']);
        $this->assertStringNotContainsString('documents', $auditUpdate['new_modules']);

        // Cleanup
        $this->connection->executeStatement("DELETE FROM admin_users WHERE emp_num = :emp", ['emp' => $testEmpNum]);
        $this->connection->executeStatement("DELETE FROM audit_admin_users WHERE target_emp_num = :emp", ['emp' => $testEmpNum]);
        $this->connection->executeStatement("SET @app_user_emp_num = NULL");
    }
}

