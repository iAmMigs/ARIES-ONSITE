<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase5FormAndSurveyTest extends KernelTestCase
{
    public function testOtherSocialMediaLabelInTemplates(): void
    {
        $alabangTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/alabang/enroll.html.twig');
        $dilimanTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/diliman/enroll.html.twig');

        $this->assertStringContainsString("'name': 'Other Social Media'", $alabangTpl, 'Alabang enrollment template must have Other Social Media');
        $this->assertStringContainsString("'name': 'Other Social Media'", $dilimanTpl, 'Diliman enrollment template must have Other Social Media');

        $this->assertStringNotContainsString("'name': 'Social Media'", $alabangTpl, 'Old Social Media label must be replaced in Alabang');
        $this->assertStringNotContainsString("'name': 'Social Media'", $dilimanTpl, 'Old Social Media label must be replaced in Diliman');
    }

    public function testLandlineHintAndOptionalInTemplates(): void
    {
        $alabangTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/alabang/enroll.html.twig');
        $dilimanTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/diliman/enroll.html.twig');

        $this->assertStringContainsString('(Leave blank if none)', $alabangTpl, 'Alabang template must show Leave blank if none hint for landline');
        $this->assertStringContainsString('(Leave blank if none)', $dilimanTpl, 'Diliman template must show Leave blank if none hint for landline');

        // Verify input does not have required
        $this->assertMatchesRegularExpression('/name="landline"[^>]*placeholder="Leave blank if none"/', $alabangTpl);
        $this->assertMatchesRegularExpression('/name="landline"[^>]*placeholder="Leave blank if none"/', $dilimanTpl);
        $this->assertDoesNotMatchRegularExpression('/name="landline"[^>]*required/', $alabangTpl);
        $this->assertDoesNotMatchRegularExpression('/name="landline"[^>]*required/', $dilimanTpl);
    }

    public function testAddressLineOptionalInTemplates(): void
    {
        $alabangTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/alabang/enroll.html.twig');
        $dilimanTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/diliman/enroll.html.twig');

        // Verify current address line is not required
        $this->assertDoesNotMatchRegularExpression('/name="address"[^>]*required/', $alabangTpl);
        $this->assertDoesNotMatchRegularExpression('/name="address"[^>]*required/', $dilimanTpl);

        // Verify permanent address line is not required
        $this->assertDoesNotMatchRegularExpression('/name="perm_address"[^>]*required/', $alabangTpl);
        $this->assertDoesNotMatchRegularExpression('/name="perm_address"[^>]*required/', $dilimanTpl);
    }

    public function testDashboardIncludesOtherSocialMedia(): void
    {
        $alabangCtrl = file_get_contents(__DIR__ . '/../src/Controller/AdminAlabangController.php');
        $dilimanCtrl = file_get_contents(__DIR__ . '/../src/Controller/AdminDilimanController.php');

        $this->assertStringContainsString("'Other Social Media'", $alabangCtrl);
        $this->assertStringContainsString("'Other Social Media'", $dilimanCtrl);

        // Checks legacy conversion
        $this->assertStringContainsString("str_starts_with(\$singleSrc, 'Social Media')", $alabangCtrl);
        $this->assertStringContainsString("str_starts_with(\$singleSrc, 'Social Media')", $dilimanCtrl);
    }

    public function testEnrollmentControllersEnforceSurveyRequired(): void
    {
        $alabangCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentAlabangController.php');
        $dilimanCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentDilimanController.php');

        $this->assertStringContainsString("empty(\$applicant->getMarketingSource())", $alabangCtrl);
        $this->assertStringContainsString("Please select at least one option in the survey", $alabangCtrl);

        $this->assertStringContainsString("empty(\$applicant->getMarketingSource())", $dilimanCtrl);
        $this->assertStringContainsString("Please select at least one option in the survey", $dilimanCtrl);
    }
}

