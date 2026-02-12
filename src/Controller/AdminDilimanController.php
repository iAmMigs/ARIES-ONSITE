<?php

namespace App\Controller;

use App\Entity\ApplicantBed;
use App\Repository\ApplicantBedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ApplicantDeletionService;

#[Route('/diliman-admin')]
class AdminDilimanController extends AbstractController
{
    #[Route('/', name: 'app_admin_diliman_dashboard')]
    public function dashboard(ApplicantBedRepository $repository): Response
    {
        // Calculate Stats for Dashboard
        $qb = $repository->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN);

        $total = (clone $qb)->getQuery()->getSingleScalarResult();
        
        $today = (clone $qb)->andWhere('a.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))->getQuery()->getSingleScalarResult();
            
        $week = (clone $qb)->andWhere('a.createdAt >= :week')
            ->setParameter('week', new \DateTime('monday this week'))->getQuery()->getSingleScalarResult();
            
        $month = (clone $qb)->andWhere('a.createdAt >= :month')
            ->setParameter('month', new \DateTime('first day of this month'))->getQuery()->getSingleScalarResult();

        // Chart Data (Simple daily count for last 7 days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.id)')
                ->where('a.campus = :campus')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()
                ->getSingleScalarResult();
            $chartData[] = ['date' => $date->format('M d'), 'count' => $count];
        }

        return $this->render('admin-onsite/diliman/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData
        ]);
    }

    #[Route('/registrations', name: 'app_admin_diliman_registrations')]
    public function registrations(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_DILIMAN)
            ->orderBy('a.id', 'DESC');

        // Filters
        if ($grade = $request->query->get('grade')) {
            $qb->andWhere('a.gradeLevel = :grade')->setParameter('grade', $grade);
        }
        
        if ($eduLevel = $request->query->get('edu_level')) {
            if ($eduLevel === 'Primary') {
                $qb->andWhere('a.gradeLevel IN (:levels)')
                   ->setParameter('levels', ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6']);
            } elseif ($eduLevel === 'Secondary') {
                $qb->andWhere('a.gradeLevel IN (:levels)')
                   ->setParameter('levels', ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']);
            }
        }

        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')->setParameter('date', "$date%");
        }

        return $this->render('admin-onsite/diliman/registrations.html.twig', [
            'registrations' => $qb->getQuery()->getResult(),
            'filters' => $request->query->all()
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_diliman_registration_view')]
    public function view(int $id, ApplicantBedRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        return $this->render('admin-onsite/diliman/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_diliman_registration_edit')]
    public function edit(int $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            // Manual mapping of fields based on your previous twig
            $registration->setFirstName($request->request->get('first_name'));
            $registration->setLastName($request->request->get('last_name'));
            $registration->setMiddleName($request->request->get('middle_name'));
            // ... map other fields similarly ...
            $registration->setAdmissionStatus($request->request->get('status'));
            
            $em->flush();
            $this->addFlash('success', 'Registration updated successfully.');
            return $this->redirectToRoute('app_admin_diliman_registrations');
        }

        return $this->render('admin-onsite/diliman/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_diliman_delete', methods: ['POST'])]
    public function delete(int $id, ApplicantBedRepository $repository, ApplicantDeletionService $service): Response
    {
        $registration = $repository->find($id);
        if ($registration) {
            $service->deleteApplicant($registration);
            $this->addFlash('success', 'Applicant deleted.');
        }
        return $this->redirectToRoute('app_admin_diliman_registrations');
    }
}