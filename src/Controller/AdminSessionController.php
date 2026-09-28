<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Dedicated controller for handling administrator session lifecycle,
 * keep-alive heartbeat pings, and timeout interactions.
 */
#[IsGranted('ROLE_ADMIN')]
class AdminSessionController extends AbstractController
{
    #[Route('/admin/session-keepalive', name: 'app_admin_session_keepalive', methods: ['GET', 'POST'])]
    public function keepAlive(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => time(),
            'message' => 'Admin session successfully refreshed.'
        ]);
    }
}
