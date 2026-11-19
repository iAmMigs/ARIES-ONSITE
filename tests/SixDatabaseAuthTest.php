<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\AssocLoginService;
use App\Service\CampusDatabaseSwitcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SixDatabaseAuthTest extends KernelTestCase
{
    private AssocLoginService $assocLoginService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->assocLoginService = $container->get(AssocLoginService::class);
    }

    public function testDilimanAssociateLookupAndAuthentication(): void
    {
        // Admin Diliman emp_id 1
        $assoc = $this->assocLoginService->findByEmployeeIdForCampus('1', CampusDatabaseSwitcher::CAMPUS_DILIMAN);
        $this->assertNotNull($assoc);
        $this->assertSame(1, $assoc['emp_id']);
        $this->assertSame('admindiliman', $assoc['user_name']);
        $this->assertTrue($this->assocLoginService->verifyPassword('admin123', $assoc['password']));

        // Verify that Diliman associate does not resolve in Alabang
        $inAlabang = $this->assocLoginService->findByEmployeeIdForCampus('1', CampusDatabaseSwitcher::CAMPUS_ALABANG);
        $this->assertNull($inAlabang);
        $this->assertTrue($this->assocLoginService->existsInOtherCampus('1', CampusDatabaseSwitcher::CAMPUS_ALABANG));
    }

    public function testAlabangAssociateLookupAndAuthentication(): void
    {
        // Admin Alabang emp_id 2
        $assoc = $this->assocLoginService->findByEmployeeIdForCampus('2', CampusDatabaseSwitcher::CAMPUS_ALABANG);
        $this->assertNotNull($assoc);
        $this->assertSame(2, $assoc['emp_id']);
        $this->assertSame('adminalabang', $assoc['user_name']);
        $this->assertTrue($this->assocLoginService->verifyPassword('admin123', $assoc['password']));

        // Verify that Alabang associate does not resolve in Diliman
        $inDiliman = $this->assocLoginService->findByEmployeeIdForCampus('2', CampusDatabaseSwitcher::CAMPUS_DILIMAN);
        $this->assertNull($inDiliman);
        $this->assertTrue($this->assocLoginService->existsInOtherCampus('2', CampusDatabaseSwitcher::CAMPUS_DILIMAN));
    }
}

