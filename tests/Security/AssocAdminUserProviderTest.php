<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Security\AssocAdminUserProvider;
use App\Service\AssocLoginService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class AssocAdminUserProviderTest extends TestCase
{
    public function testLoadUserByIdentifierNotFoundInAssocLogin(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByIdentifier')
            ->with('nonexistent')
            ->willReturn(null);

        $adminRepo = $this->createMock(AdminUserRepository::class);

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);

        $this->expectException(UserNotFoundException::class);
        $provider->loadUserByIdentifier('nonexistent');
    }

    public function testLoadUserByIdentifierNotRegisteredInAries(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByIdentifier')
            ->with('test')
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

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('This associate account is not registered as an ARIES Administrator for this campus. Please contact your system administrator.');
        $provider->loadUserByIdentifier('test');
    }

    public function testLoadUserByIdentifierInactiveAdmin(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByIdentifier')
            ->with('test')
            ->willReturn([
                'employee_id' => '123456789',
                'user_name' => 'test',
                'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
            ]);

        $inactiveAdmin = new AdminUser();
        $inactiveAdmin->setEmpNum(123456789);
        $inactiveAdmin->setIsActive(false);

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('find')
            ->with(123456789)
            ->willReturn($inactiveAdmin);

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('This administrator account has been deactivated.');
        $provider->loadUserByIdentifier('test');
    }

    public function testLoadUserByIdentifierSuccessSetsMd5PasswordOnAdminUser(): void
    {
        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByIdentifier')
            ->with('123456789')
            ->willReturn([
                'employee_id' => '123456789',
                'user_name' => 'test',
                'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
            ]);

        $adminUser = new AdminUser();
        $adminUser->setEmpNum(123456789);
        $adminUser->setEmail('test@feudiliman.edu.ph');
        $adminUser->setCampus('feu_diliman');
        $adminUser->setIsActive(true);

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('find')
            ->with(123456789)
            ->willReturn($adminUser);

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);
        $user = $provider->loadUserByIdentifier('123456789');

        $this->assertInstanceOf(AdminUser::class, $user);
        $this->assertSame('098f6bcd4621d373cade4e832627b4f6', $user->getPassword());
    }

    public function testRefreshUserMaintainsPasswordParity(): void
    {
        $existingAdmin = new AdminUser();
        $existingAdmin->setEmpNum(123456789);
        $existingAdmin->setPassword('098f6bcd4621d373cade4e832627b4f6');

        $refreshedAdmin = new AdminUser();
        $refreshedAdmin->setEmpNum(123456789);
        $refreshedAdmin->setPassword('');

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('find')
            ->with(123456789)
            ->willReturn($refreshedAdmin);

        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByEmployeeId')
            ->with('123456789')
            ->willReturn([
                'employee_id' => '123456789',
                'user_name' => 'test',
                'pass_word' => '098f6bcd4621d373cade4e832627b4f6',
            ]);

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);
        $result = $provider->refreshUser($existingAdmin);

        $this->assertSame('098f6bcd4621d373cade4e832627b4f6', $result->getPassword());
    }

    public function testLoadUserByIdentifierSuccessViaEmail(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmpNum(1);
        $adminUser->setEmail('admin@feudiliman.edu.ph');
        $adminUser->setCampus('feu_diliman');
        $adminUser->setIsActive(true);

        $assocService = $this->createMock(AssocLoginService::class);
        $assocService->expects($this->once())
            ->method('findByIdentifier')
            ->with('admin@feudiliman.edu.ph')
            ->willReturn(null); // not found in assoc directly

        $assocService->expects($this->once())
            ->method('findByEmployeeId')
            ->with('1')
            ->willReturn([
                'employee_id' => '1',
                'user_name' => 'admindiliman',
                'pass_word' => '0192023a7bbd73250516f069df18b500',
            ]);

        $adminRepo = $this->createMock(AdminUserRepository::class);
        $adminRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'admin@feudiliman.edu.ph'])
            ->willReturn($adminUser);

        $adminRepo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($adminUser);

        $provider = new AssocAdminUserProvider($assocService, $adminRepo);
        $user = $provider->loadUserByIdentifier('admin@feudiliman.edu.ph');

        $this->assertInstanceOf(AdminUser::class, $user);
        $this->assertSame('0192023a7bbd73250516f069df18b500', $user->getPassword());
        $this->assertSame('admin@feudiliman.edu.ph', $user->getEmail());
    }
}


