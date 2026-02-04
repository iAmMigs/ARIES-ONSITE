<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/admission')]
class AdmissionController extends AbstractController
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

    #[Route('/apply', name: 'app_admission_apply', methods: ['GET'])]
    public function apply(Request $request): Response
    {
        // Capture the campus passed from the homepage (e.g. ?campus=feu_diliman)
        $selectedCampus = $request->query->get('campus');

        return $this->render('admission/apply.html.twig', [
            'selected_campus' => $selectedCampus,
            'available_strands' => $selectedCampus ? $this->getStrandsByCampus($selectedCampus) : []
        ]);
    }

    #[Route('/apply/submit', name: 'app_admission_apply_submit', methods: ['POST'])]
    public function submit(Request $request, LoggerInterface $logger): Response
    {
        // 1. Extract Application Context
        $campus = $request->request->get('campus_selected');
        $educationLevel = $request->request->get('education_level');
        $applicationType = $request->request->get('application_type');

        // 2. Validate Mandatory File Uploads
        // Note: 2x2 picture removed as requested. ESC is optional/conditional.
        $documents = [
            'psa'   => $request->files->get('req_psa'),
            'card'  => $request->files->get('req_card'),
            'moral' => $request->files->get('req_moral'),
        ];

        foreach ($documents as $key => $file) {
            if (!$file instanceof UploadedFile) {
                $this->addFlash('error', "The " . strtoupper($key) . " document is required.");
                return $this->redirectToRoute('app_admission_apply', ['campus' => $campus]);
            }
        }

        // 3. Extract Student Details
        $studentData = [
            'first_name'     => $request->request->get('first_name'),
            'last_name'      => $request->request->get('last_name'),
            // Contact number is optional for HS, so we just retrieve it without strict validation here
            'contact_number' => $request->request->get('contact_number'), 
            'birthday'       => $request->request->get('birthday'),
            'gender'         => $request->request->get('gender'),
            'home_address'   => $request->request->get('home_address'),
        ];

        // 4. Validate Strand (if applicable)
        if ($educationLevel === 'senior_high') {
            $strand = $request->request->get('strand');
            $validStrands = $this->getStrandsByCampus($campus);
            
            if (!array_key_exists($strand, $validStrands)) {
                $this->addFlash('error', 'Invalid strand selected for this campus.');
                return $this->redirectToRoute('app_admission_apply', ['campus' => $campus]);
            }
        }

        // 5. Logging
        $logger->info('ARIES Application Received', [
            'campus' => $campus,
            'level' => $educationLevel,
            'name' => $studentData['last_name'] . ', ' . $studentData['first_name'],
            'docs_uploaded' => array_keys($documents)
        ]);

        // Success Feedback
        $this->addFlash('success', 'Application submitted successfully for ' . strtoupper(str_replace('_', ' ', $campus)));

        return $this->redirectToRoute('app_home'); 
    }
}