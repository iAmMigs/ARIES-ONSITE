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
 * Manages administrator accounts, module access permissions,
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
            'total'         => count($admins),
            'master'        => count(array_filter($admins, fn(AdminUser $u) => $u->isMasterAdmin())),
            'active'        => count(array_filter($admins, fn(AdminUser $u) => $u->isActive())),
            'registrations' => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_REGISTRATIONS))),
            'finance'       => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_FINANCE))),
            'survey'        => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_SURVEY))),
            'tracks'        => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_TRACKS))),
            'documents'     => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_DOCUMENTS))),
            'school_year'   => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_SCHOOL_YEAR))),
            'admin_access'  => count(array_filter($admins, fn(AdminUser $u) => $u->hasModule(AdminUser::MODULE_ADMIN_ACCESS))),
        ];

        return $this->render('admin-onsite/access/index.html.twig', [
            'campus'           => $campus,
            'campusCode'       => $campusCode,
            'campusName'       => $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            'admins'           => $admins,
            'metrics'          => $metrics,
            'active_menu'      => 'admin_access',
            'currentUser'      => $currentUser,
            'availableModules' => AdminUser::AVAILABLE_MODULES,
        ]);
    }

    /**
     * Creates a new administrator account with modular module permissions.
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
        $selectedModules = (array) $request->request->all('modules');

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

        $financeEnabled = $request->request->get('finance_enabled') === '1';
        if (in_array(AdminUser::MODULE_REGISTRATIONS, $selectedModules, true)) {
            if ($financeEnabled) {
                $selectedModules = array_diff($selectedModules, [AdminUser::MODULE_REGISTRATIONS]);
                $selectedModules[] = AdminUser::MODULE_FINANCE;
            }
        } else {
            $selectedModules = array_diff($selectedModules, [AdminUser::MODULE_FINANCE]);
        }

        $validModuleKeys = array_merge(array_keys(AdminUser::AVAILABLE_MODULES), [AdminUser::MODULE_FINANCE]);
        $filteredModules = array_values(array_intersect($selectedModules, $validModuleKeys));

        if (empty($filteredModules)) {
            $this->addFlash('error', 'Please select at least one module for the administrator.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 1. Verify existence in campus-specific Associate database
        $assoc = $assocLoginService->findByEmployeeIdForCampus($empNumRaw, $campusCode);
        if (!$assoc) {
            if ($assocLoginService->existsInOtherCampus($empNumRaw, $campusCode)) {
                $this->addFlash('error', 'This Employee is not an associate of this campus');
                return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
            }

            $this->addFlash('error', sprintf('Employee #%d was not found in the Associate directory.', $empNum));
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // 2. Check if already an administrator in this campus
        $existingEmp = $adminUserRepo->find($empNum);

        if ($existingEmp && $existingEmp->getCampus() === $campusCode) {
            $this->addFlash('error', sprintf('%s is already registered as an admin', $existingEmp->getFullName()));
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $username = (string) ($assoc['user_name'] ?? ('user' . $empNum));
        $firstName = trim((string) ($assoc['first_name'] ?? ''));
        $lastName = trim((string) ($assoc['last_name'] ?? ''));

        if (!$firstName && !empty($assoc['full_name'])) {
            $parts = explode(' ', (string) $assoc['full_name'], 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? 'Staff';
        }
        if (!$firstName) {
            $firstName = ucfirst($username);
            $lastName = 'Staff';
        }

        $newAdmin = new AdminUser();
        $newAdmin->setEmpNum($empNum);
        $newAdmin->setFirstName($firstName);
        $newAdmin->setLastName($lastName ?: 'Staff');
        $newAdmin->setEmail($username . '@feu' . ($campusCode === 'feu_alabang' ? 'alabang' : 'diliman') . '.edu.ph');
        $newAdmin->setCampus($campusCode);
        $newAdmin->setTier(AdminUser::TIER_STAFF);
        $newAdmin->setModules($filteredModules);
        $newAdmin->setRoles(['ROLE_ADMIN']);
        $newAdmin->setIsActive(true);
        $newAdmin->setPassword(''); // Delegated to central assoc_login MD5

        $em->persist($newAdmin);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Associate @%s (Employee #%d) has been successfully added as an Administrator for %s with %d module(s) granted.',
            $username,
            $empNum,
            $campusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
            count($filteredModules)
        ));

        return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
    }

    /**
     * Updates module access permissions for an existing administrator.
     * Can be executed by the Master Admin or any administrator with Admin Access module permissions.
     */
    #[Route('/{campus}/update-modules/{id}', name: 'app_admin_access_update_modules', methods: ['POST'])]
    public function updateModules(
        string $campus,
        int $id,
        Request $request,
        AdminUserRepository $adminUserRepo,
        EntityManagerInterface $em
    ): Response {
        $currentUser = $this->getAuthenticatedAdmin();
        $campusCode = $this->resolveAndAuthorizeCampus($campus, $currentUser);
        $this->assertCanAccessModule($currentUser);

        if (!$this->isCsrfTokenValid('admin_access_update_modules_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target = $adminUserRepo->find($id);
        if (!$target || $target->getCampus() !== $campusCode) {
            $this->addFlash('error', 'Administrator not found.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if ($target->isMasterAdmin()) {
            $this->addFlash('error', 'Master Admin module access cannot be modified.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        if (!$currentUser->canEditAdmin($target)) {
            $this->addFlash('error', 'You do not have permission to modify module access for this administrator.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $selectedModules = (array) $request->request->all('modules');
        $financeEnabled = $request->request->get('finance_enabled') === '1';

        if (in_array(AdminUser::MODULE_REGISTRATIONS, $selectedModules, true)) {
            if ($financeEnabled) {
                $selectedModules = array_diff($selectedModules, [AdminUser::MODULE_REGISTRATIONS]);
                $selectedModules[] = AdminUser::MODULE_FINANCE;
            }
        } else {
            $selectedModules = array_diff($selectedModules, [AdminUser::MODULE_FINANCE]);
        }

        $validModuleKeys = array_merge(array_keys(AdminUser::AVAILABLE_MODULES), [AdminUser::MODULE_FINANCE]);
        $filteredModules = array_values(array_intersect($selectedModules, $validModuleKeys));

        if (empty($filteredModules)) {
            $this->addFlash('error', 'An administrator must have at least one permitted module.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        // Prevent self-lockout from Admin Access module
        if ($target->getEmpNum() === $currentUser->getEmpNum() && !in_array(AdminUser::MODULE_ADMIN_ACCESS, $filteredModules, true)) {
            $this->addFlash('error', 'You cannot revoke your own access to the Admin Access module.');
            return $this->redirectToRoute('app_admin_access_index', ['campus' => $campus]);
        }

        $target->setModules($filteredModules);
        $target->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', sprintf(
            'Module access permissions for %s have been successfully updated (%d module(s) granted).',
            $target->getFullName(),
            count($filteredModules)
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
        $allModules = array_keys(AdminUser::AVAILABLE_MODULES);
        $target->setTier(AdminUser::TIER_MASTER);
        $target->setModules($allModules);
        $target->setCanManageAdmins(true);
        $target->setUpdatedAt(new \DateTimeImmutable());

        $currentUser->setTier(AdminUser::TIER_STAFF);
        $currentUser->setModules($allModules);
        $currentUser->setUpdatedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash('success', sprintf(
            'Master Admin role has been successfully transferred to %s.',
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
