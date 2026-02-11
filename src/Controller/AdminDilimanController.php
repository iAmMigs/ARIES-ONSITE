<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Repository\StudentProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/diliman-admin')]
class AdminDilimanController extends AbstractController
{
    #[Route('/', name: 'app_admin_diliman_dashboard')]
    public function index(Request $request, StudentProfileRepository $repository): Response
    {
        $allRegistrations = $repository->findBy(['campus' => 'feu_diliman'], ['id' => 'DESC']);

        $gradeFilter = $request->query->get('grade');
        if ($gradeFilter) {
            $registrations = array_filter($allRegistrations, fn($r) => $r->getGradeLevel() === $gradeFilter);
        } else {
            $registrations = $allRegistrations;
        }

        $availableGrades = array_unique(array_map(fn($r) => $r->getGradeLevel(), $allRegistrations));
        sort($availableGrades);

        return $this->render('admin-onsite/diliman/diliman_dashboard.html.twig', [
            'registrations' => $registrations,
            'current_filter' => $gradeFilter,
            'available_grades' => $availableGrades
        ]);
    }

    // --- FEATURE 1: Polling Route ---
    #[Route('/table-content', name: 'app_admin_diliman_table_content')]
    public function tableContent(StudentProfileRepository $repository): Response
    {
        $registrations = $repository->findBy(['campus' => 'feu_diliman'], ['id' => 'DESC']);
        
        return $this->render('admin-onsite/diliman/_table_rows.html.twig', [
            'registrations' => $registrations
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_diliman_registration_edit')]
    public function edit(int $id, StudentProfileRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            $registration->setStatus($request->request->get('status'));
            $em->flush();
            $this->addFlash('success', 'Status updated.');
            return $this->redirectToRoute('app_admin_diliman_dashboard');
        }

        return $this->render('admin-onsite/diliman/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_diliman_registration_view')]
    public function view(int $id, StudentProfileRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        return $this->render('admin-onsite/diliman/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    // --- FEATURE 3: Delete Route ---
    #[Route('/registration/{id}/delete', name: 'app_admin_diliman_delete', methods: ['POST'])]
    public function delete(int $id, StudentProfileRepository $repository, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        
        if ($registration) {
            $em->remove($registration);
            $em->flush();
            $this->addFlash('success', 'Registration deleted successfully.');
        }
        
        return $this->redirectToRoute('app_admin_diliman_dashboard');
    }
}