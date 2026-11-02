<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedGuardian;
use App\Entity\SchoolYear;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class Phase6EmergencyContactTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CampusDatabaseSwitcher $switcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
    }

    public function testEnrollmentTemplatesContainGuardianOccupation(): void
    {
        $alabangTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/alabang/enroll.html.twig');
        $dilimanTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/diliman/enroll.html.twig');

        $this->assertStringContainsString('name="guardian_occupation"', $alabangTpl, 'Alabang enrollment template must have guardian_occupation input');
        $this->assertStringContainsString('id="guardian_occupation"', $alabangTpl, 'Alabang enrollment template must have id guardian_occupation');
        $this->assertMatchesRegularExpression('/name="guardian_occupation"[^>]*required/', $alabangTpl, 'Alabang guardian_occupation should be required');

        $this->assertStringContainsString('name="guardian_occupation"', $dilimanTpl, 'Diliman enrollment template must have guardian_occupation input');
        $this->assertStringContainsString('id="guardian_occupation"', $dilimanTpl, 'Diliman enrollment template must have id guardian_occupation');
        $this->assertMatchesRegularExpression('/name="guardian_occupation"[^>]*required/', $dilimanTpl, 'Diliman guardian_occupation should be required');
    }

    public function testEnrollmentJsSyncsGuardianOccupation(): void
    {
        $js = file_get_contents(__DIR__ . '/../public/js/enrollment.js');

        $this->assertStringContainsString('guardian_occupation', $js, 'enrollment.js must reference guardian_occupation');
        $this->assertStringContainsString('pOccupation', $js, 'enrollment.js must read parent occupation');
        $this->assertStringContainsString('gOccupation.value = pOccupation', $js, 'enrollment.js must sync occupation to guardian');
        $this->assertStringContainsString('gOccupation.readOnly = true', $js, 'enrollment.js must lock guardian occupation when synced');
    }

    public function testAdminTemplatesContainEmergencyContactEmail(): void
    {
        // View templates
        $alabangView = file_get_contents(__DIR__ . '/../templates/admin-onsite/alabang/view_registration.html.twig');
        $dilimanView = file_get_contents(__DIR__ . '/../templates/admin-onsite/diliman/view_registration.html.twig');

        $this->assertStringContainsString('emergencyContact.email', $alabangView, 'Alabang view_registration must render emergencyContact.email');
        $this->assertStringContainsString('emergencyContact.email', $dilimanView, 'Diliman view_registration must render emergencyContact.email');

        // Edit templates
        $alabangEdit = file_get_contents(__DIR__ . '/../templates/admin-onsite/alabang/edit_registration.html.twig');
        $dilimanEdit = file_get_contents(__DIR__ . '/../templates/admin-onsite/diliman/edit_registration.html.twig');

        $this->assertMatchesRegularExpression('/name="guardians\[\{\{\s*emergencyIndex\s*\}\}\]\[email\]"/', $alabangEdit, 'Alabang edit_registration must have emergency contact email input');
        $this->assertMatchesRegularExpression('/name="guardians\[\{\{\s*emergencyIndex\s*\}\}\]\[email\]"/', $dilimanEdit, 'Diliman edit_registration must have emergency contact email input');
    }

    public function testAdminControllersPersistEmergencyContactEmail(): void
    {
        $alabangCtrl = file_get_contents(__DIR__ . '/../src/Controller/AdminAlabangController.php');
        $dilimanCtrl = file_get_contents(__DIR__ . '/../src/Controller/AdminDilimanController.php');

        $this->assertStringContainsString("array_key_exists('email', \$data)", $alabangCtrl, 'AdminAlabangController must persist guardian email');
        $this->assertStringContainsString("array_key_exists('email', \$data)", $dilimanCtrl, 'AdminDilimanController must persist guardian email');
    }

    public function testEnrollmentControllersHandleGuardianOccupation(): void
    {
        $alabangCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentAlabangController.php');
        $dilimanCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentDilimanController.php');

        $this->assertStringContainsString("guardian_occupation", $alabangCtrl, 'EnrollmentAlabangController must read guardian_occupation');
        $this->assertStringContainsString("guardian_occupation", $dilimanCtrl, 'EnrollmentDilimanController must read guardian_occupation');
    }

    public function testPersistenceOfGuardianOccupationAndEmailInDatabase(): void
    {
        $this->switcher->switchToCampus('feu_alabang');

        // Clean previous test data
        $existing = $this->em->getRepository(ApplicantBed::class)->findBy(['studentNumber' => 'TEST_P6_001']);
        foreach ($existing as $app) {
            foreach ($app->getGuardians() as $g) {
                $this->em->remove($g);
            }
            $this->em->remove($app);
        }
        $this->em->flush();

        $sy = $this->em->getRepository(SchoolYear::class)->findOneBy(['isActive' => true]);
        if (!$sy) {
            $sy = new SchoolYear();
            $sy->setCode('SY2026-2027');
            $sy->setDescription('2026-2027');
            $sy->setIsActive(true);
            $sy->setPromissoryDeadline(new \DateTime('+30 days'));
            $this->em->persist($sy);
            $this->em->flush();
        }

        $app = new ApplicantBed();
        $app->setStudentNumber('TEST_P6_001');
        $app->setCampus(ApplicantBed::CAMPUS_ALABANG);
        $app->setCitizenship('FILIPINO');
        $app->setAdmissionType('New Student');
        $app->setFirstName('PhaseSix');
        $app->setLastName('Tester');
        $app->setGradeLevel('Grade 11');
        $app->setEducationType('Senior High School');
        $app->setPersonalEmail('p6tester@example.com');
        $app->setMobileNumber('+63 9123456789');
        $app->setSchoolYearOfEntry($sy->getLabel());
        $this->em->persist($app);

        // Add Emergency Contact Guardian
        $guardian = new ApplicantBedGuardian();
        $guardian->setApplicant($app);
        $guardian->setGuardianType('GUARDIAN');
        $guardian->setRelationship('AUNT');
        $guardian->setParentName('MARIA CLARA');
        $guardian->setOccupation('SOFTWARE ARCHITECT');
        $guardian->setContactNo('+63 9998887777');
        $guardian->setEmail('maria.clara@example.com');
        $app->addGuardian($guardian);
        $this->em->persist($guardian);

        $this->em->flush();

        // Clear and re-fetch to verify database persistence
        $this->em->clear();
        $this->switcher->switchToCampus('feu_alabang');

        $savedApp = $this->em->getRepository(ApplicantBed::class)->findOneBy(['studentNumber' => 'TEST_P6_001']);
        $this->assertNotNull($savedApp);

        $savedGuardian = null;
        foreach ($savedApp->getGuardians() as $g) {
            if ($g->getGuardianType() === 'GUARDIAN') {
                $savedGuardian = $g;
                break;
            }
        }

        $this->assertNotNull($savedGuardian, 'Guardian must be retrieved from database');
        $this->assertSame('SOFTWARE ARCHITECT', $savedGuardian->getOccupation());
        $this->assertSame('maria.clara@example.com', $savedGuardian->getEmail());

        // Cleanup
        foreach ($savedApp->getGuardians() as $g) {
            $this->em->remove($g);
        }
        $this->em->remove($savedApp);
        $this->em->flush();
    }
}

