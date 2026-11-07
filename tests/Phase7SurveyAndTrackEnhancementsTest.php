<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\AdminUser;
use App\Entity\CampusSetting;
use App\Entity\SurveyOption;
use App\Entity\TrackSetup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class Phase7SurveyAndTrackEnhancementsTest extends KernelTestCase
{
    public function testLogoutRedirectsToCampusLogin(): void
    {
        $subscriberCode = file_get_contents(__DIR__ . '/../src/EventSubscriber/LogoutSubscriber.php');
        $securityYaml = file_get_contents(__DIR__ . '/../config/packages/security.yaml');

        $this->assertStringContainsString('app_auth_login_alabang', $subscriberCode);
        $this->assertStringContainsString('app_auth_login_diliman', $subscriberCode);
        $this->assertStringNotContainsString('app_home_diliman', $subscriberCode);
        $this->assertStringNotContainsString('app_home_alabang', $subscriberCode);

        $this->assertStringContainsString('target: app_auth_login_diliman', $securityYaml);
    }

    public function testTracksModuleRenamed(): void
    {
        $this->assertSame('tracks', AdminUser::MODULE_TRACKS);
        $this->assertArrayHasKey(AdminUser::MODULE_TRACKS, AdminUser::AVAILABLE_MODULES);
        $this->assertSame('Tracks', AdminUser::AVAILABLE_MODULES[AdminUser::MODULE_TRACKS]);

        $dilimanSidebar = file_get_contents(__DIR__ . '/../templates/admin-onsite/diliman/_sidebar.html.twig');
        $alabangSidebar = file_get_contents(__DIR__ . '/../templates/admin-onsite/alabang/_sidebar.html.twig');

        $this->assertStringContainsString('Tracks</span>', $dilimanSidebar);
        $this->assertStringContainsString('Tracks</span>', $alabangSidebar);
        $this->assertStringNotContainsString('Tracks & Strands', $dilimanSidebar);
        $this->assertStringNotContainsString('Tracks & Strands', $alabangSidebar);
    }

    public function testSurveyTemplatesHaveCollapsibleAndCancel(): void
    {
        $surveyTpl = file_get_contents(__DIR__ . '/../templates/admin-onsite/survey/index.html.twig');

        $this->assertStringContainsString('id="addSurveyContainer"', $surveyTpl);
        $this->assertStringContainsString('id="addSurveyFormContainer"', $surveyTpl);
        $this->assertStringContainsString('id="addSurveyIcon"', $surveyTpl);
        $this->assertStringContainsString('Cancel', $surveyTpl);
        // Verify order input is removed
        $this->assertStringNotContainsString('name="display_order"', $surveyTpl);
    }

    public function testTrackTemplatesHaveCollapsibleAndCancel(): void
    {
        $trackTpl = file_get_contents(__DIR__ . '/../templates/admin-onsite/tracks/index.html.twig');

        $this->assertStringContainsString('id="addTrackContainer"', $trackTpl);
        $this->assertStringContainsString('id="addTrackFormContainer"', $trackTpl);
        $this->assertStringContainsString('id="addTrackIcon"', $trackTpl);
        $this->assertStringContainsString('Cancel', $trackTpl);
        // Verify order input is removed
        $this->assertStringNotContainsString('name="display_order"', $trackTpl);
    }

    public function testControllersSortAlphabetically(): void
    {
        $surveyCtrl = file_get_contents(__DIR__ . '/../src/Controller/SurveyController.php');
        $trackCtrl = file_get_contents(__DIR__ . '/../src/Controller/TrackController.php');

        $this->assertStringContainsString("['label' => 'ASC']", $surveyCtrl);
        $this->assertStringContainsString("['name' => 'ASC']", $trackCtrl);
    }

    public function testControllersEnforceDuplicatePrevention(): void
    {
        $surveyCtrl = file_get_contents(__DIR__ . '/../src/Controller/SurveyController.php');
        $trackCtrl = file_get_contents(__DIR__ . '/../src/Controller/TrackController.php');

        // Survey duplicate checks
        $this->assertStringContainsString('LOWER(TRIM(s.label)) = :label', $surveyCtrl);
        $this->assertStringContainsString('already exists', $surveyCtrl);

        // Track duplicate checks
        $this->assertStringContainsString('(LOWER(TRIM(t.code)) = :code OR LOWER(TRIM(t.name)) = :name)', $trackCtrl);
        $this->assertStringContainsString('already exists', $trackCtrl);
    }

    public function testEnrollmentApplicationEnforcesMaxSurveyChoices(): void
    {
        $dilimanCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentDilimanController.php');
        $alabangCtrl = file_get_contents(__DIR__ . '/../src/Controller/EnrollmentAlabangController.php');

        $this->assertStringContainsString('maxSurveyChoices', $dilimanCtrl);
        $this->assertStringContainsString('maxSurveyChoices', $alabangCtrl);
        $this->assertStringContainsString('count($filteredRaw) > $maxSurveyChoices', $dilimanCtrl);
        $this->assertStringContainsString('count($filteredRaw) > $maxSurveyChoices', $alabangCtrl);

        $dilimanTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/diliman/enroll.html.twig');
        $alabangTpl = file_get_contents(__DIR__ . '/../templates/enrollment-onsite/alabang/enroll.html.twig');

        $this->assertStringContainsString('handleSurveySelection', $dilimanTpl);
        $this->assertStringContainsString('handleSurveySelection', $alabangTpl);
        $this->assertStringContainsString('maxSurveyChoices', $dilimanTpl);
        $this->assertStringContainsString('maxSurveyChoices', $alabangTpl);
    }
}
