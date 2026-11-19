<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedRequirement;
use App\Entity\DocumentSetup;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;

class DocumentComplianceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SchoolYearRepository $schoolYearRepository
    ) {
    }

    /**
     * Returns the applicable document setups for a given applicant based on
     * campus, grade level, student admission type, and nationality/citizenship.
     *
     * @return DocumentSetup[]
     */
    public function getApplicableDocuments(ApplicantBed $applicant): array
    {
        $campus = $applicant->getCampus();
        $allSetups = $this->em->getRepository(DocumentSetup::class)->findBy([
            'campus' => [$campus, null],
        ]);

        $filtered = [];
        $gradeLevel = (string)$applicant->getGradeLevel();
        $admissionType = (string)$applicant->getAdmissionType();
        $citizenship = (string)$applicant->getCitizenship();

        $isApplicantLocal = (strtoupper($citizenship) === 'LOCAL' || strtoupper($citizenship) === 'FILIPINO');

        foreach ($allSetups as $setup) {
            // 1. Grade level check
            $grades = $setup->getGradeLevels();
            if ($grades && !empty($grades)) {
                $normalizedGrades = array_map(fn($g) => strtolower(str_replace([' ', '_', '-'], '', (string)$g)), $grades);
                $normalizedApplicantGrade = strtolower(str_replace([' ', '_', '-'], '', $gradeLevel));
                if (!in_array('all', $normalizedGrades, true) && !in_array($normalizedApplicantGrade, $normalizedGrades, true)) {
                    continue;
                }
            }

            // 2. Student admission type check
            $sReq = $setup->getStudentType();
            if ($sReq && strtoupper($sReq) !== 'ALL' && strtoupper($sReq) !== 'BOTH') {
                $sReqNorm = strtoupper(trim(str_replace([' ', '_', '-'], '', (string)$sReq)));
                $admNorm = strtoupper(trim(str_replace([' ', '_', '-'], '', $admissionType)));
                if (($sReqNorm === 'NEW' || $sReqNorm === 'NEWSTUDENT') && $admNorm !== 'FRESHMAN' && $admNorm !== 'NEW' && $admNorm !== 'NEWSTUDENT') {
                    continue;
                }
                if ($sReqNorm === 'TRANSFEREE' && $admNorm !== 'TRANSFEREE') {
                    continue;
                }
            }

            // 3. Nationality / Citizenship check
            $nReq = strtoupper((string)$setup->getNationalityType());
            if ($nReq !== '' && $nReq !== 'ALL') {
                $isLocalReq = ($nReq === 'LOCAL' || $nReq === 'FILIPINO');
                if ($isLocalReq && !$isApplicantLocal) {
                    continue;
                }
                if (!$isLocalReq && $isApplicantLocal) {
                    continue;
                }
            }

            // 4. Foreign-specific document slug checks
            $slug = (string)$setup->getSlug();
            if ($isApplicantLocal) {
                if (in_array($slug, [
                    'passport_bio', 'valid_visa', 'acr_icard', 'special_study_permit',
                    'ph_birth_certificate', 'english_translations', 'parent_passport_id',
                    'proof_of_residency', 'medical_record'
                ], true)) {
                    continue;
                }
            } else {
                $visaType = strtolower((string)$applicant->getVisaType());
                if ($slug === 'special_study_permit' && !str_contains($visaType, 'special') && !str_contains($visaType, 'ssp')) {
                    continue;
                }
                if ($slug === 'acr_icard' && (str_contains($visaType, 'tourist') || str_contains($visaType, 'special') || empty($visaType))) {
                    continue;
                }
                if ($slug === 'ph_birth_certificate' && strtoupper((string)$applicant->getCountryOfBirth()) !== 'PHILIPPINES') {
                    continue;
                }
            }

            $filtered[] = $setup;
        }

        return $filtered;
    }

    /**
     * Checks if all applicable requirements for the applicant are satisfied (either uploaded or waived).
     * Automatically synchronizes documentsAgreedDate and isDocumentsAgreed:
     * - If fully compliant: documentsAgreedDate = null, isDocumentsAgreed = false
     * - If missing documents: documentsAgreedDate is set to promissory deadline, isDocumentsAgreed = true
     */
    public function syncComplianceStatus(ApplicantBed $applicant, ?\DateTimeInterface $explicitDate = null): void
    {
        $applicableSetups = $this->getApplicableDocuments($applicant);

        $requirementsBySlug = [];
        foreach ($applicant->getRequirements() as $r) {
            if ($r->getSlug() !== null) {
                $requirementsBySlug[strtolower(trim((string)$r->getSlug()))] = $r;
            }
        }

        $allSatisfied = true;
        foreach ($applicableSetups as $setup) {
            $slug = (string)$setup->getSlug();
            $slugNorm = strtolower(trim($slug));
            $req = $requirementsBySlug[$slugNorm] ?? null;

            if (!$req) {
                $req = $this->em->getRepository(ApplicantBedRequirement::class)->findOneBy([
                    'applicant' => $applicant,
                    'Slug' => $slug,
                ]);
            }

            $isSatisfied = ($req && !$req->isDeleted() && ($req->isWaived() || !empty($req->getStoredFileName())));
            if (!$isSatisfied) {
                $allSatisfied = false;
                break;
            }
        }

        if ($allSatisfied) {
            $applicant->setDocumentsAgreed(false);
            $applicant->setDocumentsAgreedDate(null);
        } else {
            $applicant->setDocumentsAgreed(true);
            if ($explicitDate !== null) {
                $applicant->setDocumentsAgreedDate($explicitDate);
            } elseif ($applicant->getDocumentsAgreedDate() === null) {
                $activeSY = $this->schoolYearRepository->findActiveByCampus((string)$applicant->getCampus());
                $deadline = $activeSY?->getPromissoryDeadline() ?? new \DateTime('+30 days');
                $applicant->setDocumentsAgreedDate($deadline);
            }
        }
    }
}
