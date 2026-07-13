<?php

namespace App\Controller\Api;

use App\Entity\DocumentSetup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DocumentApiController extends AbstractController
{
    #[Route('/api/documents/required', name: 'api_documents_required', methods: ['GET'])]
    public function getRequiredDocuments(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $campusParam = $request->query->get('campus');
        $studentType = $request->query->get('admissionType'); // "New Student" or "Transferee"
        $gradeLevel = $request->query->get('gradeLevel');     // "Grade 7", etc.
        $nationality = $request->query->get('nationality', 'FILIPINO'); // Default to FILIPINO if not provided
        $visaType = $request->query->get('visaType');
        $bornInPhilippines = $request->query->get('bornInPhilippines') === 'true';
        $isPreviousSchoolInternational = $request->query->get('isPreviousSchoolInternational') === 'true';

        if (!$campusParam) {
            return $this->json(['error' => 'Missing campus parameter'], 400);
        }

        // Map frontend campus names to backend codes
        $campusCode = match($campusParam) {
            'feu_alabang' => \App\Entity\ApplicantBed::CAMPUS_ALABANG,
            'feu_diliman' => \App\Entity\ApplicantBed::CAMPUS_DILIMAN,
            default => $campusParam
        };

        // Fetch documents for the specific campus OR global documents (campus is null)
        $allDocs = $em->getRepository(DocumentSetup::class)->findBy(['campus' => [$campusCode, null]]);
        
        $matchedDocs = [];

        foreach ($allDocs as $doc) {
            // 1. Check Student Type
            $docStudentType = $doc->getStudentType();
            if ($docStudentType && $docStudentType !== 'Both' && $docStudentType !== $studentType) {
                continue;
            }

            // 2. Check Nationality
            $docNationality = $doc->getNationalityType();
            if ($docNationality) {
                $isMatch = false;
                if ($docNationality === 'FILIPINO' && strtoupper($nationality) === 'FILIPINO') {
                    $isMatch = true;
                } elseif ($docNationality === 'FOREIGN' && strtoupper($nationality) !== 'FILIPINO') {
                    $isMatch = true;
                }
                
                if (!$isMatch) continue;
            }

            // 3. Check Grade Levels
            $docGradeLevels = $doc->getGradeLevels() ?? [];
            if (!empty($docGradeLevels) && !in_array('All', $docGradeLevels) && !in_array($gradeLevel, $docGradeLevels)) {
                continue;
            }

            // 4. Custom Conditions for International / Foreign Applicants
            $slug = $doc->getSlug();
            if (strtoupper($nationality) !== 'FILIPINO') {
                if ($slug === 'special_study_permit' && $visaType !== 'special_student') {
                    continue;
                }
                if ($slug === 'acr_icard' && ($visaType === 'special_student' || $visaType === 'tourist' || empty($visaType))) {
                    continue;
                }
                if ($slug === 'ph_birth_certificate' && !$bornInPhilippines) {
                    continue;
                }
                if ($slug === 'english_translations' && !$isPreviousSchoolInternational) {
                    continue;
                }
            } else {
                // If local (Filipino) applicant, skip these foreign-only specific documents
                if (in_array($slug, [
                    'passport_bio', 'valid_visa', 'acr_icard', 'special_study_permit',
                    'ph_birth_certificate', 'english_translations', 'parent_passport_id',
                    'proof_of_residency', 'medical_record'
                ])) {
                    continue;
                }
            }

            $matchedDocs[] = [
                'id' => $doc->getId(),
                'documentName' => $doc->getDocumentName(),
                'slug' => $doc->getSlug(),
                'allowedFileTypes' => $doc->getAllowedFileTypes(),
                'campus' => $doc->getCampus(),
            ];
        }

        return $this->json($matchedDocs);
    }
}
