<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\AdminUserRepository;
use App\Service\AssocLoginService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/assoc')]
class AssocEmployeeApiController
{
    public function __construct(
        private readonly AssocLoginService $assocLoginService,
        private readonly AdminUserRepository $adminUserRepo
    ) {
    }

    #[Route('/lookup', name: 'api_admin_assoc_lookup', methods: ['GET'])]
    public function lookup(Request $request): JsonResponse
    {
        $rawEmployeeId = trim((string) $request->query->get('employee_id', ''));
        $campus = strtolower(trim((string) $request->query->get('campus', '')));
        $targetCampusCode = ($campus === 'alabang' || $campus === 'feu_alabang') ? 'feu_alabang' : 'feu_diliman';

        if ($rawEmployeeId === '') {
            return new JsonResponse([
                'success' => false,
                'status' => 'empty',
                'message' => 'Employee Number is required.',
            ], 400);
        }

        // 1. Look up in central Associate table
        $assoc = $this->assocLoginService->findByEmployeeId($rawEmployeeId);
        if (!$assoc) {
            return new JsonResponse([
                'success' => false,
                'status' => 'not_found',
                'message' => sprintf('Employee #%s was not found in the Associate directory.', $rawEmployeeId),
            ]);
        }

        $empNum = (int) $assoc['employee_id'];
        $existingAdmin = $this->adminUserRepo->find($empNum);
        $userNameLower = strtolower((string) ($assoc['user_name'] ?? ''));

        // PRIORITY 1: If employee is from a different school / campus
        $isDifferentCampus = ($existingAdmin && $existingAdmin->getCampus() !== $targetCampusCode)
            || (str_contains($userNameLower, 'diliman') && $targetCampusCode === 'feu_alabang')
            || (str_contains($userNameLower, 'alabang') && $targetCampusCode === 'feu_diliman');

        if ($isDifferentCampus) {
            return new JsonResponse([
                'success' => false,
                'status' => 'different_campus',
                'title' => 'Campus Mismatch',
                'message' => 'This Employee is not an associate of this campus',
            ]);
        }

        // PRIORITY 2: If employee is already registered as admin in correct campus
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

        // PRIORITY 3: Viable to be added - showcase Full Name
        $fullName = trim(($assoc['first_name'] ?? '') . ' ' . ($assoc['last_name'] ?? ''));
        if (!$fullName && !empty($assoc['full_name'])) {
            $fullName = (string) $assoc['full_name'];
        }
        if (!$fullName && !empty($assoc['name'])) {
            $fullName = (string) $assoc['name'];
        }
        if (!$fullName) {
            $fullName = ucwords(str_replace(['.', '_', '-'], ' ', (string) ($assoc['user_name'] ?? '')));
        }

        return new JsonResponse([
            'success' => true,
            'status' => 'available',
            'employee_id' => $assoc['employee_id'],
            'user_name' => $assoc['user_name'],
            'full_name' => $fullName,
        ]);
    }
}
