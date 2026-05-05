<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeDilimanController extends AbstractController
{
    #[Route('/diliman', name: 'app_home_diliman', methods: ['GET'])]
    public function index(SchoolYearRepository $syRepo): Response
    {
        $dilimanSY = $syRepo->findActiveByCampus(SchoolYear::CAMPUS_DILIMAN);

        return $this->render('home-onsite/diliman/index.html.twig', [
            'enrollment_open' => $dilimanSY && $dilimanSY->isEnrollmentOpen(),
            'campus' => 'diliman',
        ]);
    }
}
