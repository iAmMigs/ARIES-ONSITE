<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/admission')]
class AdmissionController extends AbstractController
{
    #[Route('/apply', name: 'app_admission_apply', methods: ['GET'])]
    public function apply(Request $request): Response
    {
        // Capture the campus passed from the homepage (e.g. ?campus=feu_diliman)
        // This allows the form to pre-select the button at the top
        $selectedCampus = $request->query->get('campus');

        return $this->render('admission/apply.html.twig', [
            'selected_campus' => $selectedCampus
        ]);
    }

    #[Route('/apply/submit', name: 'app_admission_apply_submit', methods: ['POST'])]
    public function submit(Request $request, LoggerInterface $logger): Response
    {
        // 1. Extract Application Info
        $applicationType = $request->request->get('application_type');
        $educationLevel = $request->request->get('education_level');
        $educationType = $request->request->get('education_type'); // 'bed' or 'tertiary'
        $campus = $request->request->get('campus_selected');

        // 2. Extract Program / Grade Info
        // For Tertiary
        $program = $request->request->get('program');
        // For BED
        $gradeLevel = $request->request->get('grade_level');
        $strand = $request->request->get('strand'); // For SHS

        // 3. Extract Student Details
        $studentData = [
            'first_name'     => $request->request->get('first_name'),
            'middle_name'    => $request->request->get('middle_name'),
            'last_name'      => $request->request->get('last_name'),
            'suffix'         => $request->request->get('suffix'),
            'contact_number' => $request->request->get('contact_number'),
            'birthday'       => $request->request->get('birthday'),
            'gender'         => $request->request->get('gender'),
            'email'          => $request->request->get('email'),
            'home_address'   => $request->request->get('home_address'),
        ];

        // 4. Extract Academic History
        $academicHistory = [
            'prev_school'    => $request->request->get('prev_school'),
            'school_address' => $request->request->get('school_address'),
            'school_email'   => $request->request->get('school_email'),
            'school_contact' => $request->request->get('school_contact'),
        ];

        // 5. Extract Parent Details
        $parentDetails = [
            'father' => [
                'lastname'   => $request->request->get('father_lastname'),
                'occupation' => $request->request->get('father_occupation'),
                'contact'    => $request->request->get('father_contact'),
            ],
            'mother' => [
                'firstname'  => $request->request->get('mother_firstname'),
                'lastname'   => $request->request->get('mother_lastname'),
                'occupation' => $request->request->get('mother_occupation'),
                'contact'    => $request->request->get('mother_contact'),
            ],
            'guardian' => [
                'name'    => $request->request->get('guardian_name'),
                'contact' => $request->request->get('guardian_contact'),
            ],
        ];

        // 6. Extract Requirements Checklist
        $requirements = [
            'psa'    => $request->request->has('req_psa'),
            'card'   => $request->request->has('req_card'),
            'moral'  => $request->request->has('req_moral'),
            'lrn'    => $request->request->has('req_lrn'),
            'id_pic' => $request->request->has('req_id_pic'),
            'esc'    => $request->request->has('req_esc'),
        ];

        // 7. Validation
        $privacyConsent = $request->request->has('privacy_consent');

        if (!$privacyConsent) {
            $this->addFlash('error', 'You must agree to the Data Privacy Agreement.');
            return $this->redirectToRoute('app_admission_apply', ['campus' => $campus]);
        }

        if (empty($campus)) {
            $this->addFlash('error', 'Please select a campus.');
            return $this->redirectToRoute('app_admission_apply');
        }

        if (empty($educationLevel)) {
            $this->addFlash('error', 'Please select an education level.');
            return $this->redirectToRoute('app_admission_apply', ['campus' => $campus]);
        }
        $logger->info('ARIES Application Received', [
            'campus' => $campus,
            'level' => $educationLevel,
            'name' => $studentData['last_name'] . ', ' . $studentData['first_name']
        ]);

        // Success Feedback
        $this->addFlash('success', 'Application submitted successfully for ' . strtoupper(str_replace('_', ' ', $campus)));

        return $this->redirectToRoute('app_home'); 
    }
}