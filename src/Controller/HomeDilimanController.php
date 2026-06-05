<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeDilimanController extends AbstractController
{
    #[Route('/debug-applicant', name: 'app_debug_applicant')]
    public function debug(EntityManagerInterface $em): Response
    {
        $applicant = $em->getRepository(\App\Entity\ApplicantBed::class)->findOneBy(['studentNumber' => '202550002']);
        if (!$applicant) return new Response('Not found');
        return new Response(sprintf(
            'Campus: %s, SY: %s, AgreedDate: %s',
            $applicant->getCampus(),
            $applicant->getSchoolYearOfEntry(),
            $applicant->getDocumentsAgreedDate() ? $applicant->getDocumentsAgreedDate()->format('Y-m-d H:i:s') : 'NULL'
        ));
    }

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
