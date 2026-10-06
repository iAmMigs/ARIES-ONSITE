<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\Api\AssocEmployeeApiController;
use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Service\AssocLoginService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssocEmployeeApiControllerTest extends TestCase
{
    public function testLookupReturnsErrorWhenEmployeeIdMissing(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $adminRepo = $this->createMock(AdminUserRepository::class);

        $controller = new AssocEmployeeApiController($assocService, $adminRepo);
        $request = new Request(['employee_id' => '', 'campus' => 'diliman']);

        $response = $controller->lookup($request);

        $this->assertSame(400, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('empty', $data['status']);
    }

    public function testLookupReturnsNotFoundWhenNotInAssoc(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByEmployeeId')
            ->with('999999999')
            ->willReturn(null);

        $adminRepo = $this->createMock(AdminUserRepository::class);

        $controller = new AssocEmployeeApiController($assocService, $adminRepo);
        $request = new Request(['employee_id' => '999999999', 'campus' => 'diliman']);

        $response = $controller->lookup($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('not_found', $data['status']);
    }

    public function testLookupReturnsAlreadyAdminWhenAlreadyRegistered(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByEmployeeId')
            ->with('123456789')
            ->willReturn([
                'employee_id' => '123456789',
                'user_name' => 'test',
                'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
            ]);

        $existingAdmin = new AdminUser();
        $existingAdmin->setEmpNum(123456789);
        $existingAdmin->setCampus('feu_diliman');

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('find')
            ->with(123456789)
            ->willReturn($existingAdmin);

        $controller = new AssocEmployeeApiController($assocService, $adminRepo);
        $request = new Request(['employee_id' => '123456789', 'campus' => 'diliman']);

        $response = $controller->lookup($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('already_admin', $data['status']);
    }

    public function testLookupReturnsSuccessWhenAvailable(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByEmployeeId')
            ->with('123456789')
            ->willReturn([
                'employee_id' => '123456789',
                'user_name' => 'test',
                'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
            ]);

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('find')
            ->with(123456789)
            ->willReturn(null);

        $controller = new AssocEmployeeApiController($assocService, $adminRepo);
        $request = new Request(['employee_id' => '123456789', 'campus' => 'diliman']);

        $response = $controller->lookup($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode((string) $response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertSame('available', $data['status']);
        $this->assertSame('123456789', $data['employee_id']);
        $this->assertSame('test', $data['user_name']);
    }
}
