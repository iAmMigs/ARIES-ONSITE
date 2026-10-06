<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AdminAccessController;
use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminAccessControllerTest extends TestCase
{
    private AdminAccessController $controller;
    private EntityManagerInterface $emMock;
    private AdminUserRepository $repoMock;
    private UserPasswordHasherInterface $hasherMock;
    private TokenStorageInterface $tokenStorageMock;
    private CsrfTokenManagerInterface $csrfMock;
    private \Symfony\Component\DependencyInjection\Container $container;

    protected function setUp(): void
    {
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->repoMock = $this->createMock(AdminUserRepository::class);
        $this->hasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->csrfMock = $this->createMock(CsrfTokenManagerInterface::class);

        $this->controller = new AdminAccessController();
    }

    private function setAuthenticatedUser(AdminUser $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorageMock->method('getToken')->willReturn($token);

        $this->container = new \Symfony\Component\DependencyInjection\Container();
        $this->container->set('security.token_storage', $this->tokenStorageMock);
        $this->container->set('security.csrf.token_manager', $this->csrfMock);

        $this->controller->setContainer($this->container);
    }

    public function testCampusIsolationBlocksCrossCampusAccess(): void
    {
        $dilimanMaster = new AdminUser();
        $dilimanMaster->setCampus('feu_diliman');
        $dilimanMaster->setTier(AdminUser::TIER_MASTER);
        $dilimanMaster->setCanManageAdmins(true);

        $this->setAuthenticatedUser($dilimanMaster);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You are not authorized to access administrator management for this campus.');

        $this->controller->index('alabang', $this->repoMock);
    }

    public function testUnapprovedAdminCannotAccessModule(): void
    {
        $staff = new AdminUser();
        $staff->setCampus('feu_alabang');
        $staff->setTier(AdminUser::TIER_STAFF);
        $staff->setCanManageAdmins(false);

        $this->setAuthenticatedUser($staff);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('You do not have permission to view or manage the Admin Access module.');

        $this->controller->index('alabang', $this->repoMock);
    }

    public function testNonMasterAdminCannotTransferMasterRole(): void
    {
        $senior = new AdminUser();
        $senior->setCampus('feu_alabang');
        $senior->setTier(AdminUser::TIER_SENIOR);
        $senior->setCanManageAdmins(true);

        $this->setAuthenticatedUser($senior);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Only the Master Admin can transfer the Master Admin role.');

        $request = new Request([], ['_token' => 'dummy', 'password' => 'secret', 'confirm_text' => 'CONFIRM TRANSFER']);
        $this->controller->transferMaster('alabang', 99, $request, $this->repoMock, $this->hasherMock, $this->emMock);
    }

    public function testCreateAdminRejectsInvalidEmpNum(): void
    {
        $master = new AdminUser();
        $master->setCampus('feu_alabang');
        $master->setTier(AdminUser::TIER_MASTER);
        $master->setCanManageAdmins(true);
        $this->setAuthenticatedUser($master);

        $this->csrfMock->method('isTokenValid')->willReturn(true);

        $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $router->method('generate')->willReturn('/admin/access/alabang');
        $session = new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage());
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $baseRequest = new Request();
        $baseRequest->setSession($session);
        $requestStack->push($baseRequest);

        $this->container->set('router', $router);
        $this->container->set('request_stack', $requestStack);

        // Non-numeric employee number
        $request = new Request([], [
            '_token' => 'dummy',
            'emp_num' => 'ABC12345',
            'tier' => AdminUser::TIER_STAFF
        ]);
        $request->setSession($session);

        $assocServiceMock = $this->createMock(\App\Service\AssocLoginService::class);

        $response = $this->controller->create('alabang', $request, $this->repoMock, $assocServiceMock, $this->emMock);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(['Employee Number must contain positive numbers only.'], $session->getFlashBag()->get('error'));
    }

    public function testCreateAdminSavesValidEmpNum(): void
    {
        $master = new AdminUser();
        $master->setCampus('feu_alabang');
        $master->setTier(AdminUser::TIER_MASTER);
        $master->setCanManageAdmins(true);
        $this->setAuthenticatedUser($master);

        $this->csrfMock->method('isTokenValid')->willReturn(true);

        $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $router->method('generate')->willReturn('/admin/access/alabang');
        $session = new \Symfony\Component\HttpFoundation\Session\Session(new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage());
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();
        $baseRequest = new Request();
        $baseRequest->setSession($session);
        $requestStack->push($baseRequest);

        $this->container->set('router', $router);
        $this->container->set('request_stack', $requestStack);

        $this->repoMock->method('find')->with(202110444)->willReturn(null);

        $assocServiceMock = $this->createMock(\App\Service\AssocLoginService::class);
        $assocServiceMock->method('findByEmployeeId')->with('202110444')->willReturn([
            'employee_id' => '202110444',
            'user_name' => 'jdelacruz',
            'pass_word' => '098f6bcd4621d373cade4e832627b4f6'
        ]);

        $savedAdmin = null;
        $this->emMock->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($admin) use (&$savedAdmin) {
                $savedAdmin = $admin;
            });
        $this->emMock->expects($this->once())->method('flush');

        $request = new Request([], [
            '_token' => 'dummy',
            'emp_num' => '202110444',
            'tier' => AdminUser::TIER_STAFF
        ]);
        $request->setSession($session);

        $response = $this->controller->create('alabang', $request, $this->repoMock, $assocServiceMock, $this->emMock);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotNull($savedAdmin);
        $this->assertSame(202110444, $savedAdmin->getEmpNum());
        $this->assertSame('Jdelacruz', $savedAdmin->getFirstName());
    }
}
