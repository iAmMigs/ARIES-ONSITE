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
        // Initial load of the page
        $allRegistrations = $repository->findBy(['campus' => 'feu_alabang'], ['id' => 'DESC']);

        // Filter Logic
        $gradeFilter = $request->query->get('grade');
        if ($gradeFilter) {
            $registrations = array_filter($allRegistrations, fn($r) => $r->getGradeLevel() === $gradeFilter);
        } else {
            $registrations = $allRegistrations;
        }

        // Get unique grades for the dropdown
        $availableGrades = array_unique(array_map(fn($r) => $r->getGradeLevel(), $allRegistrations));
        sort($availableGrades);

        return $this->render('admin-onsite/alabang/alabang_dashboard.html.twig', [
            'registrations' => $registrations,
            'current_filter' => $gradeFilter,
            'available_grades' => $availableGrades
        ]);
    }

    // --- FEATURE 1: Real-time Polling Route ---
    #[Route('/table-content', name: 'app_admin_alabang_table_content')]
    public function tableContent(StudentProfileRepository $repository): Response
    {
        // This returns ONLY the HTML for the table rows
        $registrations = $repository->findBy(['campus' => 'feu_alabang'], ['id' => 'DESC']);
        
        return $this->render('admin-onsite/alabang/_table_rows.html.twig', [
            'registrations' => $registrations
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(int $id, StudentProfileRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            $registration->setStatus($request->request->get('status'));
            $em->flush();
            $this->addFlash('success', 'Status updated.');
            return $this->redirectToRoute('app_admin_alabang_dashboard');
        }

        return $this->render('admin-onsite/alabang/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(int $id, StudentProfileRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        return $this->render('admin-onsite/alabang/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    // --- FEATURE 3: Delete Registration ---
    #[Route('/registration/{id}/delete', name: 'app_admin_alabang_delete', methods: ['POST'])]
    public function delete(int $id, StudentProfileRepository $repository, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        
        if ($registration) {
            $em->remove($registration);
            $em->flush();
            $this->addFlash('success', 'Registration deleted successfully.');
        }
        
        return $this->redirectToRoute('app_admin_alabang_dashboard');
    }
}