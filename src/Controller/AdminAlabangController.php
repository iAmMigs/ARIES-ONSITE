<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Repository\StudentProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alabang-admin')]
class AdminAlabangController extends AbstractController
{
    #[Route('/', name: 'app_admin_alabang_dashboard')]
    public function index(Request $request, StudentProfileRepository $repository): Response
    {
        // 1. Fetch Real Data from DB
        $allRegistrations = $repository->findBy(['campus' => 'feu_alabang'], ['id' => 'DESC']);

        $gradeFilter = $request->query->get('grade');
        
        // 2. Filter logic (PHP-side for simplicity, or convert to QueryBuilder)
        $filteredRegistrations = array_filter($allRegistrations, function($reg) use ($gradeFilter) {
            if (!$gradeFilter) return true;
            return $reg->getGradeLevel() === $gradeFilter;
        });

        // 3. Get unique grades for dropdown
        $availableGrades = array_unique(array_map(fn($r) => $r->getGradeLevel(), $allRegistrations));
        sort($availableGrades);

        return $this->render('admin-onsite/alabang/alabang_dashboard.html.twig', [
            'registrations' => $filteredRegistrations,
            'current_filter' => $gradeFilter,
            'available_grades' => $availableGrades
        ]);
    }

    // New: Route for Real-time Polling
    #[Route('/table-content', name: 'app_admin_alabang_table_content')]
    public function tableContent(StudentProfileRepository $repository): Response
    {
        $registrations = $repository->findBy(['campus' => 'feu_alabang'], ['id' => 'DESC']);
        
        return $this->render('admin-onsite/alabang/_table_rows.html.twig', [
            'registrations' => $registrations
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(int $id, StudentProfileRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }

        if ($request->isMethod('POST')) {
            // Handle edit logic here (e.g. status update)
            $status = $request->request->get('status');
            if ($status) {
                $registration->setStatus($status);
                $em->flush();
                $this->addFlash('success', 'Registration updated successfully.');
                return $this->redirectToRoute('app_admin_alabang_dashboard');
            }
        }

        return $this->render('admin-onsite/alabang/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(int $id, StudentProfileRepository $repository): Response
    {
        $registration = $repository->find($id);

        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }

        return $this->render('admin-onsite/alabang/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_alabang_delete', methods: ['POST'])]
    public function delete(int $id, StudentProfileRepository $repository, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        
        if ($registration) {
            $em->remove($registration);
            $em->flush();
            $this->addFlash('success', 'Registration record deleted permanently.');
        } else {
            $this->addFlash('error', 'Registration not found.');
        }
        
        return $this->redirectToRoute('app_admin_alabang_dashboard');
    }
}