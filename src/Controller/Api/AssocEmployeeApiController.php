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

        // 2. Check if already an administrator in ARIES
        $empNum = (int) $assoc['employee_id'];
        $existingAdmin = $this->adminUserRepo->find($empNum);

        if ($existingAdmin) {
            $existingCampus = $existingAdmin->getCampus();
            $campusLabel = $existingCampus === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman';

            return new JsonResponse([
                'success' => false,
                'status' => 'already_admin',
                'message' => sprintf(
                    'Employee #%s (@%s) is already registered as an administrator for %s.',
                    $assoc['employee_id'],
                    $assoc['user_name'],
                    $campusLabel
                ),
            ]);
        }

        // 3. Available to be added
        return new JsonResponse([
            'success' => true,
            'status' => 'available',
            'employee_id' => $assoc['employee_id'],
            'user_name' => $assoc['user_name'],
        ]);
    }
}
