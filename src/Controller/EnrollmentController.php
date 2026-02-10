<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/enrollment')]
class EnrollmentController extends AbstractController
{
    /**
     * Returns the specific SHS strands for each campus based on your requirements.
     */
    private function getStrandsByCampus(string $campus): array
    {
        $strands = [
            'feu_alabang' => [
                'SHS_ABM'   => 'ABM - Accountancy, Business and Management',
                'SHS_BAE'   => 'BAE - Business And Entrepreneurship',
                'SHS_CSMA'  => 'CSMA - Computer Studies And Multimedia Arts',
                'SHS_ENG'   => 'ENG - Engineering',
                'SHS_GAS'   => 'GAS - General Academic Strand',
                'SHS_HA'    => 'HA - Health Allied',
                'SHS_HUMSS' => 'HUMSS - Humanities and Social Sciences',
                'SHS_SSH'   => 'SSH - Social Sciences And Humanities',
                'SHS_STEM'  => 'STEM - Science, Technology, Engineering and Mathematics',
            ],
            'feu_diliman' => [
                'SHS_ABM'    => 'ABM - Accountancy, Business and Management',
                'SHS_GAS'    => 'GAS - General Academic Strand',
                'SHS_HUMSS'  => 'HUMSS - Humanities and Social Sciences',
                'SHS_SPORTS' => 'SPORTS - Sports Track',
                'SHS_STEM'   => 'STEM - Science, Technology, Engineering and Mathematics',
            ],
        ];

        return $strands[$campus] ?? [];
    }

    #[Route('/apply', name: 'app_enrollment_apply', methods: ['GET'])]
    public function apply(Request $request): Response
    {
        // Capture the campus passed from the homepage (e.g. ?campus=feu_diliman)
        $selectedCampus = $request->query->get('campus');

        return $this->render('enrollment-onsite/enroll.html.twig', [
            'selected_campus' => $selectedCampus,
            'available_strands' => $selectedCampus ? $this->getStrandsByCampus($selectedCampus) : []
        ]);
    }

    #[Route('/apply/submit', name: 'app_enrollment_apply_submit', methods: ['POST'])]
    public function submit(Request $request, LoggerInterface $logger): Response
    {
        // 1. Extract Application Context
        $campus = $request->request->get('campus_selected');
        $educationLevel = $request->request->get('education_level');
        $applicationType = $request->request->get('application_type');

        // 2. Validate Mandatory File Uploads
        $documents = [
            'psa'   => $request->files->get('req_psa'),
            'card'  => $request->files->get('req_card'),
            'moral' => $request->files->get('req_moral'),
        ];

        // ESC is conditional, so we handle it separately if needed, or assume front-end handles the requirement
        if ($request->files->get('req_esc')) {
            $documents['esc'] = $request->files->get('req_esc');
        }

        foreach ($documents as $key => $file) {
            // Note: 'esc' might be optional depending on logic, strict check applied here for standard docs
            if ($key !== 'esc' && !$file instanceof UploadedFile) {
                $this->addFlash('error', "The " . strtoupper($key) . " document is required.");
                return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
            }
        }

        // 3. Extract Student Details (Expanded based on student.html.twig)
        $studentData = [
            // Name
            'first_name'     => $request->request->get('first_name'),
            'last_name'      => $request->request->get('last_name'),
            'middle_name'    => $request->request->get('middle_name'),
            'suffix'         => $request->request->get('suffix'),
            
            // Personal
            'birthday'       => $request->request->get('birthday'),
            'age'            => $request->request->get('age'),
            'gender'         => $request->request->get('gender'),
            'email'          => $request->request->get('email'),
            'contact_number' => $request->request->get('contact_number'),
            
            // Extended Personal Details
            'birth_country'  => $request->request->get('birth_country'),
            'birth_province' => $request->request->get('birth_province'),
            'birth_place'    => $request->request->get('birth_place'), // City/Municipality
            'citizenship'    => $request->request->get('citizenship'),
            'religion'       => $request->request->get('religion'),
            'mother_tongue'  => $request->request->get('mother_tongue'),
            'lrn'            => $request->request->get('lrn'),
            'indigenous_group' => $request->request->get('indigenous_group'),
            'special_needs'  => $request->request->get('special_needs'),

            // Address (Expanded)
            'address_street'   => $request->request->get('address_street'),
            'address_barangay' => $request->request->get('address_barangay'),
            'address_city'     => $request->request->get('address_city'),
            'address_province' => $request->request->get('address_province'),
            'address_region'   => $request->request->get('address_region'),
            'address_zip'      => $request->request->get('address_zip'),
        ];

        // 4. Extract Parent/Guardian Details (Expanded based on guardian.html.twig)
        $parentData = [
            // Father
            'father_lastname'     => $request->request->get('father_lastname'),
            'father_firstname'    => $request->request->get('father_firstname'),
            'father_middlename'   => $request->request->get('father_middlename'),
            'father_contact'      => $request->request->get('father_contact'),
            'father_email'        => $request->request->get('father_email'),
            'father_occupation'   => $request->request->get('father_occupation'),
            'father_employer'     => $request->request->get('father_employer'),
            'father_education'    => $request->request->get('father_education'),
            'father_status'       => $request->request->get('father_status'),

            // Mother
            'mother_lastname'     => $request->request->get('mother_lastname'),
            'mother_firstname'    => $request->request->get('mother_firstname'),
            'mother_middlename'   => $request->request->get('mother_middlename'),
            'mother_contact'      => $request->request->get('mother_contact'),
            'mother_email'        => $request->request->get('mother_email'),
            'mother_occupation'   => $request->request->get('mother_occupation'),
            'mother_employer'     => $request->request->get('mother_employer'),
            'mother_education'    => $request->request->get('mother_education'),
            'mother_status'       => $request->request->get('mother_status'),

            // Guardian
            'guardian_name'       => $request->request->get('guardian_name'),
            'guardian_relationship' => $request->request->get('guardian_relationship'),
            'guardian_contact'    => $request->request->get('guardian_contact'),
            'guardian_email'      => $request->request->get('guardian_email'),
            'guardian_occupation' => $request->request->get('guardian_occupation'),
            'guardian_address'    => $request->request->get('guardian_address'),
            
            // Emergency Contact
            'emergency_name'         => $request->request->get('emergency_name'),
            'emergency_relationship' => $request->request->get('emergency_relationship'),
            'emergency_contact'      => $request->request->get('emergency_contact'),
            'emergency_address'      => $request->request->get('emergency_address'),
        ];

        // 5. Extract School Records
        $schoolData = [
            'prev_school'    => $request->request->get('prev_school'),
            'school_address' => $request->request->get('school_address'),
            'school_email'   => $request->request->get('school_email'),
            'school_contact' => $request->request->get('school_contact'),
        ];

        // 6. Validate Strand (if applicable)
        if ($educationLevel === 'senior_high') {
            $strand = $request->request->get('strand');
            $validStrands = $this->getStrandsByCampus($campus);
            
            if (!array_key_exists($strand, $validStrands)) {
                $this->addFlash('error', 'Invalid strand selected for this campus.');
                return $this->redirectToRoute('app_enrollment_apply', ['campus' => $campus]);
            }
        }

        // 7. Logging (Updated structure)
        $logger->info('ARIES Application Received', [
            'campus' => $campus,
            'level' => $educationLevel,
            'type'  => $applicationType,
            'student' => $studentData['last_name'] . ', ' . $studentData['first_name'],
            'docs_uploaded' => array_keys($documents)
        ]);

        // Success Feedback
        $this->addFlash('success', 'Application submitted successfully for ' . strtoupper(str_replace('_', ' ', $campus)));

        return $this->redirectToRoute('app_home'); 
    }
}