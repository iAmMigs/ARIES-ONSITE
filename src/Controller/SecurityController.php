<?php

namespace App\Controller;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_auth_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // 1. If already logged in, redirect based on their ASSIGNED CAMPUS
        if ($this->getUser()) {
            return $this->redirectToDashboard($this->getUser());
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * STRICT REDIRECTION LOGIC
     * Checks the actual 'campus' property in the database, not the email address.
     */
    private function redirectToDashboard(mixed $user): Response
    {
        // Ensure we are dealing with our AdminUser entity
        if (!$user instanceof AdminUser) {
            return $this->redirectToRoute('app_home');
        }

        // Get the specific value saved in the database (e.g., 'feu_alabang')
        $assignedCampus = $user->getCampus();

        return match ($assignedCampus) {
            'feu_diliman' => $this->redirectToRoute('app_admin_diliman_dashboard'),
            'feu_alabang' => $this->redirectToRoute('app_admin_alabang_dashboard'),
            default => $this->redirectToRoute('app_home'), // Unknown/Invalid campus -> Kick to Home
        };
    }
}