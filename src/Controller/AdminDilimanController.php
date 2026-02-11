<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/diliman-admin')]
class AdminDilimanController extends AbstractController
{
    // Mock Data Simulator - Identical structure to Alabang but with Diliman Context
    private function getRegistrations(): array
    {
        return [
            1 => [
                'id' => 1,
                'status' => 'Pending Examination',
                'date' => 'Feb 08, 2026',
                'exam_score' => null,
                'exam_date' => null,
                'adcon_number' => 'AD-2026-001', 
                'student_id' => '2025531291',
                'name' => 'Osinada, Mogs', 
                'first_name' => 'Mogs',
                'last_name' => 'Osinada',
                'middle_name' => 'P.',
                'suffix' => '',
                'avatar' => '300-11.png',
                'email' => 'miguelosinada@email.com',
                'contact_number' => '09171234567',
                'landline_number' => '8842-1234',
                'birthday' => '2005-05-15',
                'gender' => 'Male',
                'citizenship' => 'Filipino',
                'civil_status' => 'Single',
                'religion' => 'Roman Catholic',
                'mother_tongue' => 'Tagalog',
                'lrn' => '123456789012',
                'birth_place' => 'Muntinlupa City',
                'birth_province' => 'Metro Manila',
                'birth_country' => 'Philippines',
                'address_street' => '123 Main St, Ayala Alabang',
                'address_barangay' => 'Ayala Alabang',
                'address_city' => 'Muntinlupa City',
                'address_province' => 'Metro Manila',
                'address_zip' => '1780',
                'campus' => 'FEU Diliman', // Changed to Diliman
                'grade' => 'Grade 11',
                'track' => 'STEM',
                'application_type' => 'Freshman',
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'prev_school' => 'De La Salle Zobel',
                'school_address' => 'University Ave, Muntinlupa',
                'father_name' => 'Ricardo Osinada',
                'father_contact' => '09179998888',
                'father_occupation' => 'Engineer',
                'father_deceased' => false,
                'father_ofw' => true,
                'mother_name' => 'Maria Osinada',
                'mother_contact' => '09177776666',
                'mother_occupation' => 'Accountant',
                'mother_deceased' => false,
                'mother_ofw' => false,
                'emergency_name' => 'Maria Osinada',
                'emergency_relationship' => 'Mother',
                'emergency_contact' => '09177776666',
                'siblings' => [
                    ['name' => 'Miguel Osinada', 'age' => 25, 'school' => 'UP Diliman', 'occupation' => 'Developer'],
                    ['name' => 'Mica Osinada', 'age' => 10, 'school' => 'DLSZ', 'occupation' => 'Student']
                ],
                'documents' => ['psa' => 'psa_osinada.pdf', 'card' => 'form138_osinada.jpg', 'moral' => 'good_moral.jpg']
            ],
            2 => [
                'id' => 2,
                'status' => 'Enrolled',
                'date' => 'Feb 07, 2026',
                'exam_score' => 94,
                'exam_date' => 'Feb 10, 2026',
                'adcon_number' => 'AD-2026-045',
                'student_id' => '2025503322',
                'name' => 'Fischbach, Mark Edward',
                'first_name' => 'Mark Edward',
                'last_name' => 'Fischbach',
                'middle_name' => '',
                'suffix' => '',
                'avatar' => '300-2.png',
                'email' => 'mark.fischbach@email.com',
                'contact_number' => '09187654321',
                'landline_number' => '',
                'birthday' => '2004-06-20',
                'gender' => 'Male',
                'citizenship' => 'American',
                'civil_status' => 'Single',
                'religion' => 'Christian',
                'mother_tongue' => 'English',
                'lrn' => '109876543210',
                'birth_place' => 'Honolulu',
                'birth_province' => 'Hawaii',
                'birth_country' => 'USA',
                'address_street' => '456 Ruby St',
                'address_barangay' => 'Pilar Village',
                'address_city' => 'Las Piñas',
                'address_province' => 'Metro Manila',
                'address_zip' => '1740',
                'campus' => 'FEU Diliman',
                'grade' => 'Grade 12',
                'track' => 'ABM',
                'application_type' => 'Transferee',
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'prev_school' => 'San Beda Alabang',
                'school_address' => 'Alabang Hills, Muntinlupa',
                'father_name' => 'Thomas Fischbach',
                'father_contact' => '09190001111',
                'father_occupation' => 'Retired',
                'father_deceased' => true,
                'father_ofw' => false,
                'mother_name' => 'Molly Fischbach',
                'mother_contact' => '09192223333',
                'mother_occupation' => 'Housewife',
                'mother_deceased' => false,
                'mother_ofw' => false,
                'emergency_name' => 'Molly Fischbach',
                'emergency_relationship' => 'Mother',
                'emergency_contact' => '09192223333',
                'siblings' => [],
                'documents' => ['psa' => 'psa_mark.pdf', 'visa' => 'student_visa.pdf']
            ],
            3 => [
                'id' => 3,
                'status' => 'Pending Examination',
                'date' => 'Feb 09, 2026',
                'exam_score' => null,
                'exam_date' => null,
                'adcon_number' => 'AD-2026-088',
                'student_id' => '2025500003',
                'name' => 'Reyes, Mary Jane',
                'first_name' => 'Mary Jane',
                'last_name' => 'Reyes',
                'middle_name' => 'Anne',
                'suffix' => '',
                'avatar' => '300-1.png',
                'email' => 'miguel.reyes@email.com',
                'contact_number' => '09191112222',
                'landline_number' => '8850-9999',
                'birthday' => '2015-09-10',
                'gender' => 'Female',
                'citizenship' => 'Filipino',
                'civil_status' => 'Single',
                'religion' => 'Catholic',
                'mother_tongue' => 'Tagalog',
                'lrn' => '555666777888',
                'birth_place' => 'Quezon City',
                'birth_province' => 'Metro Manila',
                'birth_country' => 'Philippines',
                'address_street' => '789 Emerald St',
                'address_barangay' => 'San Antonio',
                'address_city' => 'Parañaque',
                'address_province' => 'Metro Manila',
                'address_zip' => '1700',
                'campus' => 'FEU Diliman',
                'grade' => 'Grade 3',
                'track' => null,
                'application_type' => 'Freshman',
                'term' => '1st Term',
                'school_year' => '2026-2027',
                'prev_school' => 'Montessori De Manila',
                'school_address' => 'Las Piñas City',
                'father_name' => 'John Reyes',
                'father_contact' => '09205554444',
                'father_occupation' => 'Architect',
                'father_deceased' => false,
                'father_ofw' => true,
                'mother_name' => 'Sarah Reyes',
                'mother_contact' => '09206667777',
                'mother_occupation' => 'Doctor',
                'mother_deceased' => false,
                'mother_ofw' => false,
                'emergency_name' => 'Sarah Reyes',
                'emergency_relationship' => 'Mother',
                'emergency_contact' => '09206667777',
                'siblings' => [
                    ['name' => 'Peter Reyes', 'age' => 12, 'school' => 'Montessori', 'occupation' => 'Student']
                ],
                'documents' => ['psa' => 'psa_mary.pdf', 'card' => 'report_card.jpg']
            ],
        ];
    }

    #[Route('/', name: 'app_admin_diliman_dashboard')]
    public function index(Request $request): Response
    {
        $allRegistrations = $this->getRegistrations();

        $gradeFilter = $request->query->get('grade');
        
        $filteredRegistrations = array_filter($allRegistrations, function($reg) use ($gradeFilter) {
            if (!$gradeFilter) return true;
            return $reg['grade'] === $gradeFilter;
        });

        $availableGrades = array_unique(array_column($allRegistrations, 'grade'));
        sort($availableGrades);

        return $this->render('admin-onsite/diliman/diliman_dashboard.html.twig', [
            'registrations' => $filteredRegistrations,
            'current_filter' => $gradeFilter,
            'available_grades' => $availableGrades
        ]);
    }

    #[Route('/registration/{id}/edit', name: 'app_admin_diliman_registration_edit')]
    public function edit(int $id, Request $request): Response
    {
        $registrations = $this->getRegistrations();
        
        if (!isset($registrations[$id])) {
            throw $this->createNotFoundException('Registration not found');
        }

        $registration = $registrations[$id];

        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Registration updated successfully.');
            return $this->redirectToRoute('app_admin_diliman_dashboard');
        }

        return $this->render('admin-onsite/diliman/edit_registration.html.twig', [
            'registration' => $registration
        ]);
    }

    #[Route('/registration/{id}/view', name: 'app_admin_diliman_registration_view')]
    public function view(int $id): Response
    {
        $registrations = $this->getRegistrations();

        if (!isset($registrations[$id])) {
            throw $this->createNotFoundException('Registration not found');
        }

        return $this->render('admin-onsite/diliman/view_registration.html.twig', [
            'registration' => $registrations[$id]
        ]);
    }
}