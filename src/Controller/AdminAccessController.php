<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Service\AssocLoginService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Manages administrator accounts, hierarchy roles, module access permissions,
 * and high-security role transfer/deletion operations with multi-stage verification.
 *
 * Scope: Strict campus isolation between FEU Alabang and FEU Diliman.
 * Route prefix: /admin/access
 */
#[Route('/admin/access')]
#[IsGranted('ROLE_ADMIN')]
class AdminAccessController extends AbstractController
{
    /**
     * Lists administrators for the given campus and displays management dashboard.
     */
    #[Route('/{campus}', name: 'app_admin_access_index', methods: ['GET'])]
    public function index(string $campus, AdminUserRepository $adminUserRepo): Response
    {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);
        $this->assertCanAccessModule($currentUser);

        $admins = $adminUserRepo->findBy(['campus' => $campusCode], ['empNum' => 'ASC']);

        $metrics = [
            'total'  => count($admins),
            'master' => count(array_filter($admins, fn(AdminUser $u) => $u->isMasterAdmin())),
            'senior' => count(array_filter($admins, fn(AdminUser $u) => $u->isSeniorAdmin())),
            'staff'  => count(array_filter($admins, fn(AdminUser $u) => $u->isStaffAdmin())),
            'active' => count(array_filter($admins, fn(AdminUser $u) => $u->isActive())),
        ];

        return $this->render('admin-onsite/access/index.html.twig', [
            'campus'          => $campus,
            'campusCode'      => $campusCode,
            'campusName'      => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'admins'          => $admins,
            'metrics'         => $metrics,
            'active_menu'     => 'admin_access',
            'currentUser'     => $currentUser,
            'canCreateSenior' => $currentUser->isMasterAdmin(),
            'canCreateStaff'  => $currentUser->canCreateTier(AdminUser::TIER_STAFF),
        ]);
    }

    /**
     * Creates a new administrator account within the authorized campus.
     */
    #[Route('/{campus}/create', name: 'app_admin_access_create', methods: ['POST'])]
    public function create(
        string $campus,
        Request $request,
        AdminUserRepository $adminUserRepo,
        AssocLoginService $assocLoginService,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);
        $this->assertCanAccessModule($currentUser);

        if (!$this->isCsrfTokenValid('admin_access_create', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Please try again.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $empNumRaw = trim((string) $request->request->get('emp_num', ''));
        $tier      = (string) $request->request->get('tier', AdminUser::TIER_STAFF);
        $canManage = $request->request->getBoolean('can_manage_admins');

        if ($empNumRaw === '') {
            $this->addFlash('error', 'Employee Number is required.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!ctype_digit($empNumRaw)) {
            $this->addFlash('error', 'Employee Number must contain positive numbers only.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $empNum = (int) $empNumRaw;
        if ($empNum <= 0 || (float) $empNumRaw > 4294967295) {
            $this->addFlash('error', 'Employee Number must be a valid positive number.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 1. Verify existence in central Associate directory
        $assoc = $assocLoginService->findByEmployeeId($empNumRaw);
        if (!$assoc) {
            $this->addFlash('error', sprintf('Employee #%d was not found in the central Associate directory.', $empNum));
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 2. Check if already an administrator in ARIES
        $existingEmp = $adminUserRepo->find($empNum);
        if ($existingEmp) {
            $this->addFlash('error', sprintf(
                'Employee #%d (@%s) is already registered as an administrator for %s.',
                $empNum,
                $assoc['user_name'],
                $existingEmp->getCampus() === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman'
            ));
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!$currentUser->canCreateTier($tier)) {
            $this->addFlash('error', 'You do not have permission to create an administrator with the selected role tier.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $username = (string) ($assoc['user_name'] ?? ('user' . $empNum));

        $newAdmin = new AdminUser();
        $newAdmin->setEmpNum($empNum);
        $newAdmin->setFirstName(ucfirst($username));
        $newAdmin->setLastName('Staff');
        $newAdmin->setEmail($username . '@feu' . ($campusCode === 'feu_alabang' ? 'alabang' : 'diliman') . '.edu.ph');
        $newAdmin->setCampus($campusCode);
        $newAdmin->setTier($tier);
        $newAdmin->setRoles(['ROLE_ADMIN']);
        $newAdmin->setIsActive(true);
        $newAdmin->setPassword(''); // Delegated to central assoc_login MD5

        // Only Master Admin can grant module management access
        $newAdmin->setCanManageAdmins($currentUser->isMasterAdmin() ? $canManage : false);

        $em->persist($newAdmin);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Associate @%s (Employee #%d) has been successfully granted %s access for %s.',
            $username,
            $empNum,
            $newAdmin->getTierLabel(),
            $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman'
        ));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Toggles module management access for a non-master administrator.
     * Restricted strictly to Master Admin.
     */
    #[Route('/{campus}/toggle-permission/{id}', name: 'app_admin_access_toggle_permission', methods: ['POST'])]
    public function togglePermission(
        string $campus,
        int $id,
        Request $request,
        AdminUserRepository $adminUserRepo,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);

        if (!$currentUser->isMasterAdmin()) {
            throw new AccessDeniedException('Only the Master Admin can modify module access permissions.');
        }

        if (!$this->isCsrfTokenValid('admin_access_toggle_perm_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target = $adminUserRepo->find($id);
        if (!$target || $target->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Administrator not found.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if ($target->isMasterAdmin()) {
            $this->addFlash('error', 'Master Admin permissions cannot be modified.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $newVal = !$target->canManageAdmins();
        $target->setCanManageAdmins($newVal);
        $target->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', sprintf(
            'Admin Access module permission for %s was %s.',
            $target->getFullName(),
            $newVal ? 'granted' : 'revoked'
        ));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Transfers the Master Admin role to another administrator.
     * Requires current Master Admin password verification and exact "CONFIRM TRANSFER" text matching.
     */
    #[Route('/{campus}/transfer-master/{id}', name: 'app_admin_access_transfer_master', methods: ['POST'])]
    public function transferMaster(
        string $campus,
        int $id,
        Request $request,
        AdminUserRepository $adminUserRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);

        if (!$currentUser->isMasterAdmin()) {
            throw new AccessDeniedException('Only the Master Admin can transfer the Master Admin role.');
        }

        if (!$this->isCsrfTokenValid('admin_access_transfer_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Transfer cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target = $adminUserRepo->find($id);
        if (!$target || $target->getCampus() !== $campusCode || $target->getEmpNum() === $currentUser->getEmpNum()) {
            $this->addFlash('error', 'Invalid recipient administrator for role transfer.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!$target->isActive()) {
            $this->addFlash('error', 'Cannot transfer Master Admin role to an inactive administrator.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 1. Password verification gate
        $password = (string) $request->request->get('password', '');
        if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
            $this->addFlash('error', 'Incorrect password. The Master Admin role transfer was cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 2. Case-sensitive confirmation string gate
        $confirmText = trim((string) $request->request->get('confirm_text', ''));
        if ($confirmText !== 'CONFIRM TRANSFER') {
            $this->addFlash('error', 'Confirmation phrase does not match "CONFIRM TRANSFER". The transfer was cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // Execute role swap
        $target->setTier(AdminUser::TIER_MASTER);
        $target->setCanManageAdmins(true);
        $target->setUpdatedAt(new \DateTimeImmutable());

        $currentUser->setTier(AdminUser::TIER_SENIOR);
        $currentUser->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash('success', sprintf(
            'Master Admin role has been successfully transferred to %s. Your account is now a Senior Admin.',
            $target->getFullName()
        ));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Permanently deletes an administrator account.
     * Requires current administrator password verification and exact "CONFIRM DELETE" text matching.
     */
    #[Route('/{campus}/delete/{id}', name: 'app_admin_access_delete', methods: ['POST'])]
    public function delete(
        string $campus,
        int $id,
        Request $request,
        AdminUserRepository $adminUserRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);
        $this->assertCanAccessModule($currentUser);

        if (!$this->isCsrfTokenValid('admin_access_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token. Deletion cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target = $adminUserRepo->find($id);
        if (!$target || $target->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Administrator not found.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!$currentUser->canDeleteAdmin($target)) {
            $this->addFlash('error', 'You do not have permission to delete this administrator.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 1. Password verification gate
        $password = (string) $request->request->get('password', '');
        if (!$passwordHasher->isPasswordValid($currentUser, $password)) {
            $this->addFlash('error', 'Incorrect password. Administrator deletion was cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 2. Case-sensitive confirmation string gate
        $confirmText = trim((string) $request->request->get('confirm_text', ''));
        if ($confirmText !== 'CONFIRM DELETE') {
            $this->addFlash('error', 'Confirmation phrase does not match "CONFIRM DELETE". Deletion was cancelled.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $targetName = $target->getFullName();
        $em->remove($target);
        $em->flush();

        $this->addFlash('success', sprintf('Administrator %s has been permanently deleted.', $targetName));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Toggles an administrator account between active and inactive.
     */
    #[Route('/{campus}/toggle-status/{id}', name: 'app_admin_access_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        string $campus,
        int $id,
        Request $request,
        AdminUserRepository $adminUserRepo,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);
        $this->assertCanAccessModule($currentUser);

        if (!$this->isCsrfTokenValid('admin_access_status_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target = $adminUserRepo->find($id);
        if (!$target || $target->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Administrator not found.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if ($target->isMasterAdmin()) {
            $this->addFlash('error', 'The Master Admin account cannot be deactivated.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if ($target->getEmpNum() === $currentUser->getEmpNum()) {
            $this->addFlash('error', 'You cannot deactivate your own account.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!$currentUser->isMasterAdmin() && !$target->isStaffAdmin()) {
            $this->addFlash('error', 'You can only toggle status for Staff Admins.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $newStatus = !$target->isActive();
        $target->setIsActive($newStatus);
        $target->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', sprintf(
            'Administrator %s has been %s.',
            $target->getFullName(),
            $newStatus ? 'activated' : 'deactivated'
        ));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Ensures the current user is authenticated as an AdminUser.
     */
    private function getAuthenticatedAdmin(): AdminUser
    {
        $user = $this->getUser();
        if (!$user instanceof AdminUser) {
            throw new AccessDeniedException('You must be logged in as an administrator.');
        }
        return $user;
    }

    /**
     * Resolves the campus route parameter and verifies that the current admin is authorized for it.
     */
    private function resolveAndAuthorizeCampus(string $campus, AdminUser $currentUser): string
    {
        $campusCode = match (strtolower(trim($campus))) {
            'alabang', 'feu_alabang' => 'feu_alabang',
            'diliman', 'feu_diliman' => 'feu_diliman',
            default                  => null,
        };

        if ($campusCode === null) {
            throw $this->createNotFoundException(sprintf('Unknown campus: "%s"', $campus));
        }

        if ($currentUser->getCampus() !== $campusCode) {
            throw new AccessDeniedException('You are not authorized to access administrator management for this campus.');
        }

        return $campusCode;
    }

    /**
     * Enforces that only Master Admin or approved admins with can_manage_admins permission can access this module.
     */
    private function assertCanAccessModule(AdminUser $currentUser): void
    {
        if (!$currentUser->canManageAdmins()) {
            throw new AccessDeniedException('You do not have permission to view or manage the Admin Access module.');
        }
    }
}
