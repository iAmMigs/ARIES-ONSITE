<?php

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeAlabangController extends AbstractController
{
    #[Route('/alabang', name: 'app_home_alabang', methods: ['GET'])]
    public function index(SchoolYearRepository $syRepo): Response
    {
        $alabangSY = $syRepo->findActiveByCampus(SchoolYear::CAMPUS_ALABANG);

        return $this->render('home-onsite/alabang/index.html.twig', [
            'enrollment_open' => $alabangSY && $alabangSY->isEnrollmentOpen(),
            'campus' => 'alabang',
        ]);
    }
}
