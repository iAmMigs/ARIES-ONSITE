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

#[Route('/alabang-admin')]
class AdminAlabangController extends AbstractController
{
    #[Route('/', name: 'app_admin_alabang_dashboard')]
    public function dashboard(ApplicantBedRepository $repository): Response
    {
        // Calculate Stats
        $qb = $repository->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG);

        $total = (clone $qb)->getQuery()->getSingleScalarResult();
        $today = (clone $qb)->andWhere('a.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))->getQuery()->getSingleScalarResult();
        $week = (clone $qb)->andWhere('a.createdAt >= :week')
            ->setParameter('week', new \DateTime('monday this week'))->getQuery()->getSingleScalarResult();
        $month = (clone $qb)->andWhere('a.createdAt >= :month')
            ->setParameter('month', new \DateTime('first day of this month'))->getQuery()->getSingleScalarResult();

        // Chart Data
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i days");
            $count = $repository->createQueryBuilder('a')
                ->select('count(a.id)')
                ->where('a.campus = :campus')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG)
                ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                ->getQuery()->getSingleScalarResult();
            $chartData[] = ['date' => $date->format('M d'), 'count' => $count];
        }

        return $this->render('admin-onsite/alabang/dashboard.html.twig', [
            'stats' => compact('total', 'today', 'week', 'month'),
            'chartData' => $chartData
        ]);
    }

    #[Route('/registrations', name: 'app_admin_alabang_registrations')]
    public function registrations(Request $request, ApplicantBedRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('a')
            ->where('a.campus = :campus')
            ->setParameter('campus', ApplicantBed::CAMPUS_ALABANG)
            ->orderBy('a.id', 'DESC');

        if ($grade = $request->query->get('grade')) {
            $qb->andWhere('a.gradeLevel = :grade')->setParameter('grade', $grade);
        }
        if ($date = $request->query->get('date')) {
            $qb->andWhere('a.createdAt LIKE :date')->setParameter('date', "$date%");
        }

        return $this->render('admin-onsite/alabang/registrations.html.twig', [
            'registrations' => $qb->getQuery()->getResult(),
            'filters' => $request->query->all()
        ]);
    }

    // View, Edit, Delete are structurally identical to Diliman, mapped to Alabang routes
    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(int $id, ApplicantBedRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();
        return $this->render('admin-onsite/alabang/view_registration.html.twig', ['registration' => $registration]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(int $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            $registration->setAdmissionStatus($request->request->get('status'));
            // ... map other fields ...
            $em->flush();
            $this->addFlash('success', 'Updated.');
            return $this->redirectToRoute('app_admin_alabang_registrations');
        }
        return $this->render('admin-onsite/alabang/edit_registration.html.twig', ['registration' => $registration]);
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_alabang_delete', methods: ['POST'])]
    public function delete(int $id, ApplicantBedRepository $repository, ApplicantDeletionService $service): Response
    {
        $registration = $repository->find($id);
        if ($registration) $service->deleteApplicant($registration);
        return $this->redirectToRoute('app_admin_alabang_registrations');
    }
}