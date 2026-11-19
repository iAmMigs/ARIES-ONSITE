<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Service\AssocLoginService;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/assoc')]
class AssocEmployeeApiController
{
    public function __construct(
        private readonly AssocLoginService $assocLoginService,
        private readonly AdminUserRepository $adminUserRepo,
        private readonly Security $security,
        private readonly CampusDatabaseSwitcher $databaseSwitcher,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/lookup', name: 'api_admin_assoc_lookup', methods: ['GET'])]
    public function lookup(Request $request): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof AdminUser) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        if (!$currentUser->canManageAdmins()) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied: Admin Access module permission required.'], 403);
        }

        $rawEmployeeId = trim((string) $request->query->get('employee_id', ''));
        $campus = strtolower(trim((string) $request->query->get('campus', '')));
        $targetCampusCode = $this->assocLoginService->resolveCampus($campus);

        if ($currentUser->getCampus() !== $targetCampusCode) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied for this campus.'], 403);
        }

        if ($rawEmployeeId === '') {
            return new JsonResponse([
                'success' => false,
                'status' => 'empty',
                'message' => 'Employee Number is required.',
            ], 400);
        }

        // 1. Look up in campus-specific Associate table
        $assoc = $this->assocLoginService->findByEmployeeIdForCampus($rawEmployeeId, $targetCampusCode);

        // PRIORITY 1: Cross-campus check: verify if associate belongs to the other campus
        if (!$assoc) {
            if ($this->assocLoginService->existsInOtherCampus($rawEmployeeId, $targetCampusCode)) {
                return new JsonResponse([
                    'success' => false,
                    'status' => 'different_campus',
                    'title' => 'Campus Mismatch',
                    'message' => 'This Employee is not an associate of this campus',
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'status' => 'not_found',
                'message' => sprintf('Employee #%s was not found in the Associate directory.', $rawEmployeeId),
            ]);
        }

        $empNum = (int) $assoc['emp_id'];

        // Switch to target campus DB to check existing admin status
        $this->databaseSwitcher->switchToCampus($targetCampusCode);
        $existingAdmin = $this->adminUserRepo->find($empNum);

        // PRIORITY 2: If employee is already registered as admin in this campus
        if ($existingAdmin && $existingAdmin->getCampus() === $targetCampusCode) {
            $fullName = $existingAdmin->getFullName();

            return new JsonResponse([
                'success' => false,
                'status' => 'already_admin',
                'title' => 'Already Registered',
                'full_name' => $fullName,
                'message' => sprintf('%s Is already registered as an admin', $fullName),
            ]);
        }

        // PRIORITY 3: Viable to be added - format Full Name
        $nameParts = array_filter([$assoc['first_name'] ?? null, $assoc['middle_name'] ?? null, $assoc['last_name'] ?? null]);
        $fullName = !empty($nameParts) ? implode(' ', $nameParts) : ucwords(str_replace(['.', '_', '-'], ' ', (string) ($assoc['user_name'] ?? '')));

        return new JsonResponse([
            'success' => true,
            'status' => 'available',
            'employee_id' => $assoc['emp_id'],
            'user_name' => $assoc['user_name'],
            'full_name' => $fullName,
            'email' => $assoc['email'] ?? null,
            'title' => $assoc['title'] ?? null,
            'campus' => $targetCampusCode === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman',
        ]);
    }
}
