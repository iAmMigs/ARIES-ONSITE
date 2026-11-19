<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedRequirement;
use App\Entity\DocumentSetup;
use App\Entity\SchoolYear;
use App\Service\CampusDatabaseSwitcher;
use App\Service\DocumentComplianceService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase4DocumentComplianceTest extends KernelTestCase
{
    private \Doctrine\ORM\EntityManagerInterface $em;
    private DocumentComplianceService $complianceService;
    private CampusDatabaseSwitcher $switcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get('doctrine')->getManager();
        $this->complianceService = $container->get(DocumentComplianceService::class);
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
        $this->cleanTestApplicants();
    }

    protected function tearDown(): void
    {
        $this->cleanTestApplicants();
        parent::tearDown();
    }

    private function cleanTestApplicants(): void
    {
        try {
            $conn = $this->em->getConnection();
            $conn->executeStatement("DELETE FROM aries_diliman_db.bed_applicants WHERE student_number LIKE 'TEST_COMPLIANCE_%'");
        } catch (\Throwable $e) {
        }
    }

    public function testDilimanDocumentMatchingForFilipinoJuniorHigh(): void
    {
        $this->switcher->switchToCampus('feu_diliman');

        $applicant = new ApplicantBed();
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setCitizenship('FILIPINO');
        $applicant->setAdmissionType('New Student');
        $applicant->setEducationType('Junior High School');
        $applicant->setGradeLevel('Grade 7');

        $applicableDocs = $this->complianceService->getApplicableDocuments($applicant);
        $applicableSlugs = array_map(fn(DocumentSetup $d) => $d->getSlug(), $applicableDocs);

        // In Diliman setup: req_psa and req_local_only_docs must be applicable
        $this->assertContains('req_psa', $applicableSlugs, 'req_psa must match Filipino Grade 7 applicant');
        $this->assertContains('req_local_only_docs', $applicableSlugs, 'req_local_only_docs must match Filipino Grade 7 applicant');

        // Foreign-only documents must NOT match
        $this->assertNotContains('passport_bio', $applicableSlugs, 'Foreign passport_bio must not match Filipino applicant');
        $this->assertNotContains('special_study_permit', $applicableSlugs, 'Foreign special_study_permit must not match Filipino applicant');
    }

    public function testDilimanDocumentMatchingForInternationalApplicant(): void
    {
        $this->switcher->switchToCampus('feu_diliman');

        $applicant = new ApplicantBed();
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setCitizenship('INTERNATIONAL');
        $applicant->setAdmissionType('New Student');
        $applicant->setGradeLevel('Grade 11');
        $applicant->setVisaType('Special Study Permit (SSP)');
        $applicant->setCountryOfBirth('South Korea');

        $applicableDocs = $this->complianceService->getApplicableDocuments($applicant);
        $applicableSlugs = array_map(fn(DocumentSetup $d) => $d->getSlug(), $applicableDocs);

        // International docs with student_type = 'Both' and grade_levels = ['All'] must match
        $this->assertContains('passport_bio', $applicableSlugs, 'passport_bio must match international applicant');
        $this->assertContains('special_study_permit', $applicableSlugs, 'special_study_permit must match international applicant with SSP visa');
        $this->assertNotContains('req_local_only_docs', $applicableSlugs, 'req_local_only_docs must not match international applicant');
    }

    public function testMissingDocumentsSetsPromissoryDeadlineAndNotComplied(): void
    {
        $this->switcher->switchToCampus('feu_diliman');

        $applicant = new ApplicantBed();
        $applicant->setStudentNumber('TEST_COMPLIANCE_' . bin2hex(random_bytes(4)));
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setCitizenship('FILIPINO');
        $applicant->setAdmissionType('New Student');
        $applicant->setEducationType('Junior High School');
        $applicant->setGradeLevel('Grade 7');
        $applicant->setMobileNumber('+63 9171112222');
        $applicant->setPersonalEmail('test@example.com');
        $applicant->setLastName('TEST');
        $applicant->setFirstName('COMPLIANCE');

        // Initially no requirements attached -> documents are missing
        $this->complianceService->syncComplianceStatus($applicant);

        $this->assertTrue($applicant->isDocumentsAgreed(), 'Applicant must have isDocumentsAgreed = true when documents are missing');
        $this->assertNotNull($applicant->getDocumentsAgreedDate(), 'documentsAgreedDate must NOT be null when documents are missing (not automatically complied)');
    }

    public function testSatisfiedDocumentsFlipsToDocumentsComplied(): void
    {
        $this->switcher->switchToCampus('feu_diliman');

        $applicant = new ApplicantBed();
        $applicant->setStudentNumber('TEST_COMPLIANCE_' . bin2hex(random_bytes(4)));
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setCitizenship('FILIPINO');
        $applicant->setAdmissionType('New Student');
        $applicant->setEducationType('Junior High School');
        $applicant->setGradeLevel('Grade 7');
        $applicant->setMobileNumber('+63 9171112222');
        $applicant->setPersonalEmail('test@example.com');
        $applicant->setLastName('TEST');
        $applicant->setFirstName('COMPLIANCE');

        $this->em->persist($applicant);

        $applicableDocs = $this->complianceService->getApplicableDocuments($applicant);
        $this->assertNotEmpty($applicableDocs);

        // Attach submitted / waived requirements for all applicable documents
        foreach ($applicableDocs as $doc) {
            $req = new ApplicantBedRequirement();
            $req->setApplicant($applicant);
            $req->setSlug($doc->getSlug());
            $req->setRequirement($doc->getDocumentName());
            $req->setStoredFileName('uploads/sample/' . $doc->getSlug() . '.pdf');
            $req->setStatus(ApplicantBedRequirement::STATUS_SUBMITTED);
            $req->setDateSubmitted(new \DateTime());
            $req->setIsDeleted(false);
            $applicant->addRequirement($req);
            $this->em->persist($req);
        }

        $this->em->flush();

        // Sync compliance status
        $this->complianceService->syncComplianceStatus($applicant);

        $this->assertFalse($applicant->isDocumentsAgreed(), 'Applicant must have isDocumentsAgreed = false when all documents are submitted');
        $this->assertNull($applicant->getDocumentsAgreedDate(), 'documentsAgreedDate must be null when all documents are submitted (Documents Complied)');

        // Now test: waive one and delete another -> should become not complied
        $firstReq = $applicant->getRequirements()->first();
        $firstReq->setIsDeleted(true);
        $this->complianceService->syncComplianceStatus($applicant);

        $this->assertTrue($applicant->isDocumentsAgreed());
        $this->assertNotNull($applicant->getDocumentsAgreedDate());

        // Waive the missing one -> should become complied again
        $firstReq->setIsDeleted(false);
        $firstReq->setStatus(ApplicantBedRequirement::STATUS_WAIVED);
        $this->complianceService->syncComplianceStatus($applicant);

        $this->assertFalse($applicant->isDocumentsAgreed());
        $this->assertNull($applicant->getDocumentsAgreedDate(), 'Waiving all missing requirements must result in Documents Complied');

        // Cleanup
        $this->em->remove($applicant);
        $this->em->flush();
    }
}
