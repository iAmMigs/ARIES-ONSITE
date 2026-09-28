<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\AdminUser;
use PHPUnit\Framework\TestCase;

class AdminUserTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new AdminUser();
        $this->assertSame(AdminUser::TIER_STAFF, $user->getTier());
        $this->assertFalse($user->canManageAdmins());
        $this->assertTrue($user->isActive());
    }

    public function testMasterAdminCapabilities(): void
    {
        $master = new AdminUser();
        $master->setTier(AdminUser::TIER_MASTER);
        $master->setCampus('feu_alabang');

        $this->assertTrue($master->isMasterAdmin());
        $this->assertFalse($master->isSeniorAdmin());
        $this->assertFalse($master->isStaffAdmin());
        $this->assertSame(1, $master->getTierLevel());
        $this->assertSame('Master Admin', $master->getTierLabel());
        $this->assertTrue($master->canManageAdmins()); // Master admin always can manage

        // Master can create Senior and Staff
        $this->assertTrue($master->canCreateTier(AdminUser::TIER_SENIOR));
        $this->assertTrue($master->canCreateTier(AdminUser::TIER_STAFF));
        $this->assertFalse($master->canCreateTier(AdminUser::TIER_MASTER));
    }

    public function testSeniorAdminCapabilities(): void
    {
        $senior = new AdminUser();
        $senior->setTier(AdminUser::TIER_SENIOR);
        $senior->setCampus('feu_alabang');

        $this->assertFalse($senior->isMasterAdmin());
        $this->assertTrue($senior->isSeniorAdmin());
        $this->assertFalse($senior->isStaffAdmin());
        $this->assertSame(2, $senior->getTierLevel());
        $this->assertSame('Senior Admin', $senior->getTierLabel());

        // Without canManageAdmins permission
        $senior->setCanManageAdmins(false);
        $this->assertFalse($senior->canManageAdmins());
        $this->assertFalse($senior->canCreateTier(AdminUser::TIER_STAFF));

        // With canManageAdmins permission
        $senior->setCanManageAdmins(true);
        $this->assertTrue($senior->canManageAdmins());
        $this->assertTrue($senior->canCreateTier(AdminUser::TIER_STAFF));
        $this->assertFalse($senior->canCreateTier(AdminUser::TIER_SENIOR));
        $this->assertFalse($senior->canCreateTier(AdminUser::TIER_MASTER));
    }

    public function testDeletionHierarchyAndGuards(): void
    {
        $master = new AdminUser();
        $master->setTier(AdminUser::TIER_MASTER);
        $master->setCampus('feu_alabang');

        $senior = new AdminUser();
        $senior->setTier(AdminUser::TIER_SENIOR);
        $senior->setCampus('feu_alabang');
        $senior->setCanManageAdmins(true);

        $staff = new AdminUser();
        $staff->setTier(AdminUser::TIER_STAFF);
        $staff->setCampus('feu_alabang');

        $otherCampusStaff = new AdminUser();
        $otherCampusStaff->setTier(AdminUser::TIER_STAFF);
        $otherCampusStaff->setCampus('feu_diliman');

        // Master cannot delete self or other master
        $this->assertFalse($master->canDeleteAdmin($master));

        // Master can delete Senior and Staff within same campus
        $this->assertTrue($master->canDeleteAdmin($senior));
        $this->assertTrue($master->canDeleteAdmin($staff));

        // Master cannot delete staff from another campus
        $this->assertFalse($master->canDeleteAdmin($otherCampusStaff));

        // Senior can delete Staff within same campus
        $this->assertTrue($senior->canDeleteAdmin($staff));

        // Senior cannot delete Master or another Senior
        $this->assertFalse($senior->canDeleteAdmin($master));
        $this->assertFalse($senior->canDeleteAdmin($senior));

        // Staff cannot delete anyone
        $this->assertFalse($staff->canDeleteAdmin($staff));
    }

    public function testEmpNumGetterAndSetter(): void
    {
        $user = new AdminUser();
        $this->assertNull($user->getEmpNum());

        $user->setEmpNum(202110444);
        $this->assertSame(202110444, $user->getEmpNum());
    }
}
