<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; // Corrected namespace for PHP 8+ Attributes

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    // --- DUMMY ROUTES FOR FRONTEND MOCKUP ---
    
    #[Route('/apply', name: 'app_admission_apply')]
    public function apply(): Response { return new Response('Apply Page Coming Soon'); }

    #[Route('/status', name: 'app_admission_status')]
    public function status(): Response { return new Response('Status Page Coming Soon'); }

    #[Route('/login', name: 'app_auth_login')]
    public function login(): Response { return new Response('Login Page Coming Soon'); }
}