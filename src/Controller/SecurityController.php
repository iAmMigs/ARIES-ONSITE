<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_auth_login')]
    public function loginChoice(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dispatch');
        }
        return $this->render('security/login_choice.html.twig');
    }

    #[Route('/admin/diliman/login', name: 'app_auth_login_diliman')]
    public function loginDiliman(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dispatch');
        }

        return $this->render('security/login_diliman.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/alabang/login', name: 'app_auth_login_alabang')]
    public function loginAlabang(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_dispatch');
        }

        return $this->render('security/login_alabang.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/check', name: 'app_auth_login_check')]
    public function loginCheck(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the login key on your firewall.');
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

        if (!$user instanceof AdminUser) {
            return $this->redirectToRoute('app_auth_login_diliman');
        }

        return match ($user->getCampus()) {
            'feu_alabang' => $this->redirectToRoute('app_admin_alabang_dashboard'),
            'feu_diliman' => $this->redirectToRoute('app_admin_diliman_dashboard'),
            default       => $this->redirectToRoute('app_auth_login_diliman'),
        };
    }
}