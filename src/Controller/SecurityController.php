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
        // If already logged in, let the dispatcher handle it
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dispatch');
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

    #[Route('/admin', name: 'app_admin_index')]
    public function indexAdmin(): Response
    {
        return $this->redirectToRoute('app_admin_dispatch');
    }

    #[Route('/admin/dispatch', name: 'app_admin_dispatch')]
    public function dispatch(): Response
    {
        $user = $this->getUser();

        // 1. Safety Check: If not an AdminUser, kick them out
        if (!$user instanceof AdminUser) {
            return $this->redirectToRoute('app_home');
        }

        // 2. Strict Campus Redirection
        return match ($user->getCampus()) {
            'feu_alabang' => $this->redirectToRoute('app_admin_alabang_dashboard'),
            'feu_diliman' => $this->redirectToRoute('app_admin_diliman_dashboard'),
            default       => $this->redirectToRoute('app_home'),
        };
    }
}