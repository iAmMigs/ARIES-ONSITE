<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/alabang-admin')]
class AdminAlabangController extends AbstractController
{
    #[Route('/', name: 'app_admin_alabang_dashboard')]
    public function index(Request $request): Response
    {

        $allRegistrations = [
            [
                'id' => 1,
                'student_id' => '2025531291',
                'name' => 'Osinada, Mogs',
                'email' => 'miguelosinada@email.com',
                'grade' => 'Grade 11',
                'track' => 'STEM',
                'status' => 'Pending Examination',
                'date' => 'Feb 08, 2026',
                'avatar' => '300-11.png'
            ],
            [
                'id' => 2,
                'student_id' => '2025503322',
                'name' => 'Fischbach, Mark Edward',
                'email' => 'mark.fischbach@email.com',
                'grade' => 'Grade 12',
                'track' => 'ABM',
                'status' => 'Enrolled',
                'date' => 'Feb 07, 2026',
                'avatar' => '300-2.png'
            ],
            [
                'id' => 3,
                'student_id' => '2025500003',
                'name' => 'Reyes, Mary Jane',
                'email' => 'miguel.reyes@email.com',
                'grade' => 'Grade 3',
                'track' => null, // Elementary has no track
                'status' => 'Pending Examination',
                'date' => 'Feb 09, 2026',
                'avatar' => '300-1.png'
            ],
        ];

        // 2. Handle Filtering
        $gradeFilter = $request->query->get('grade');
        
        $filteredRegistrations = array_filter($allRegistrations, function($reg) use ($gradeFilter) {
            if (!$gradeFilter) return true; // Show all if no filter
            return $reg['grade'] === $gradeFilter;
        });

        // 3. Get Unique Grades for the Dropdown
        $availableGrades = array_unique(array_column($allRegistrations, 'grade'));
        sort($availableGrades);

        return $this->render('admin-onsite/alabang/alabang_dashboard.html.twig', [
            'registrations' => $filteredRegistrations,
            'current_filter' => $gradeFilter,
            'available_grades' => $availableGrades
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_alabang_registration_edit')]
    public function edit(int $id): Response
    {
        return new Response("To Do Skibidi");
    }

    #[Route('/registration/{id}/view', name: 'app_admin_alabang_registration_view')]
    public function view(int $id): Response
    {
        return new Response("To Do Skibidi");
    }
}