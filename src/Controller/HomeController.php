<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Renders the public landing page.
     *
     * Passes the enrollment open/closed status for each campus so the page
     * can reflect the correct call-to-action per university without a redirect.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(SchoolYearRepository $syRepo): Response
    {
        $alabangSY = $syRepo->findActiveByCampus(SchoolYear::CAMPUS_ALABANG);
        $dilimanSY = $syRepo->findActiveByCampus(SchoolYear::CAMPUS_DILIMAN);

        return $this->render('home-onsite/index.html.twig', [
            'alabang_enrollment_open' => $alabangSY && $alabangSY->isEnrollmentOpen(),
            'diliman_enrollment_open' => $dilimanSY && $dilimanSY->isEnrollmentOpen(),
        ]);
    }

    #[Route('/status', name: 'app_admission_status')]
    public function status(): Response { return new Response('Status Page Coming Soon'); }

    #[Route('/login', name: 'app_auth_login')]
    public function login(): Response { return new Response('Login Page Coming Soon'); }
}