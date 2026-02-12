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
    public function index(Request $request, ApplicantBedRepository $repository): Response
    {
        // Use the constant for filtering (matches 'FALAB' in db)
        $allRegistrations = $repository->findBy(['campus' => ApplicantBed::CAMPUS_ALABANG], ['id' => 'DESC']);

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

    #[Route('/table-content', name: 'app_admin_alabang_table_content')]
    public function tableContent(ApplicantBedRepository $repository): Response
    {
        $registrations = $repository->findBy(['campus' => ApplicantBed::CAMPUS_ALABANG], ['id' => 'DESC']);
        
        return $this->render('admin-onsite/alabang/_table_rows.html.twig', [
            'registrations' => $registrations
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(int $id, ApplicantBedRepository $repository, Request $request, EntityManagerInterface $em): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        if ($request->isMethod('POST')) {
            // Updated to setAdmissionStatus
            $registration->setAdmissionStatus($request->request->get('status'));
            $em->flush();
            $this->addFlash('success', 'Status updated.');
            return $this->redirectToRoute('app_admin_alabang_dashboard');
        }

        return $this->render('admin-onsite/alabang/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(int $id, ApplicantBedRepository $repository): Response
    {
        $registration = $repository->find($id);
        if (!$registration) throw $this->createNotFoundException();

        return $this->render('admin-onsite/alabang/view_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/delete', name: 'app_admin_alabang_delete', methods: ['POST'])]
    public function delete(
        int $id, 
        ApplicantBedRepository $repository, 
        ApplicantDeletionService $deletionService // Inject the service
    ): Response
    {
        $registration = $repository->find($id);
        
        if ($registration) {
            // Use the service to handle file cleanup + db removal
            $deletionService->deleteApplicant($registration);
            
            $this->addFlash('success', 'Registration and associated files deleted successfully.');
        } else {
            $this->addFlash('error', 'Registration not found.');
        }
        
        return $this->redirectToRoute('app_admin_alabang_dashboard');
    }
}