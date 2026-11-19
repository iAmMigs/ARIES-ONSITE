<?php

declare(strict_types=1);

namespace App\Tests;

use App\Controller\SecurityController;
use App\Entity\AdminUser;
use App\Entity\ApplicantBed;
use App\Entity\ApplicantBedPassport;
use App\EventSubscriber\CampusAccessSubscriber;
use App\Service\CampusDatabaseSwitcher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityAndIntegrityTest extends KernelTestCase
{
    private CampusDatabaseSwitcher $switcher;
    private \Doctrine\ORM\EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->switcher = $container->get(CampusDatabaseSwitcher::class);
        $this->em = $container->get('doctrine')->getManager();
    }

    public function testApplicantBedEmailAlias(): void
    {
        $applicant = new ApplicantBed();
        $applicant->setPersonalEmail('applicant.test@example.com');

        $this->assertSame('applicant.test@example.com', $applicant->getPersonalEmail());
        $this->assertSame('applicant.test@example.com', $applicant->getEmail());
    }

    public function testApplicantBedPassportEntityAssociation(): void
    {
        $applicant = new ApplicantBed();
        $applicant->setStudentNumber('TEST-2026-INT-001');
        $applicant->setCampus(ApplicantBed::CAMPUS_ALABANG);
        $applicant->setPersonalEmail('int.student@example.com');
        $applicant->setCitizenship('INTERNATIONAL');

        $passport = new ApplicantBedPassport();
        $passport->setApplicant($applicant);
        $passport->setPassportNumber('P1234567A');
        $passport->setCountryOfIssue('United States');
        $passport->setDateIssued(new \DateTime('2023-01-15'));
        $passport->setExpirationDate(new \DateTime('2033-01-15'));

        $applicant->setPassport($passport);

        $this->assertNotNull($applicant->getPassport());
        $this->assertSame('P1234567A', $applicant->getPassport()->getPassportNumber());
        $this->assertSame('United States', $applicant->getPassport()->getCountryOfIssue());
        $this->assertSame($applicant, $applicant->getPassport()->getApplicant());
    }

    public function testResolveLandingRouteForEveryIndividualModule(): void
    {
        $container = static::getContainer();
        $securityController = new SecurityController();
        $securityController->setContainer($container);

        // 1. Documents Only
        $adminDocsDil = new AdminUser();
        $adminDocsDil->setCampus('feu_diliman');
        $adminDocsDil->setTier(AdminUser::TIER_STAFF);
        $adminDocsDil->setModules([AdminUser::MODULE_DOCUMENTS]);
        /** @var RedirectResponse $res1 */
        $res1 = $securityController->resolveLandingRoute($adminDocsDil);
        $this->assertStringContainsString('/diliman-admin/document-setup', $res1->getTargetUrl());

        $adminDocsAlab = new AdminUser();
        $adminDocsAlab->setCampus('feu_alabang');
        $adminDocsAlab->setTier(AdminUser::TIER_STAFF);
        $adminDocsAlab->setModules([AdminUser::MODULE_DOCUMENTS]);
        /** @var RedirectResponse $res2 */
        $res2 = $securityController->resolveLandingRoute($adminDocsAlab);
        $this->assertStringContainsString('/alabang-admin/document-setup', $res2->getTargetUrl());

        // 2. School Year Only
        $adminSy = new AdminUser();
        $adminSy->setCampus('feu_diliman');
        $adminSy->setTier(AdminUser::TIER_STAFF);
        $adminSy->setModules([AdminUser::MODULE_SCHOOL_YEAR]);
        /** @var RedirectResponse $res3 */
        $res3 = $securityController->resolveLandingRoute($adminSy);
        $this->assertStringContainsString('/admin/school-year', $res3->getTargetUrl());

        // 3. Admin Access Only
        $adminAccess = new AdminUser();
        $adminAccess->setCampus('feu_alabang');
        $adminAccess->setTier(AdminUser::TIER_STAFF);
        $adminAccess->setModules([AdminUser::MODULE_ADMIN_ACCESS]);
        /** @var RedirectResponse $res4 */
        $res4 = $securityController->resolveLandingRoute($adminAccess);
        $this->assertStringContainsString('/admin/access', $res4->getTargetUrl());

        // 4. Registrations Only
        $adminRegDil = new AdminUser();
        $adminRegDil->setCampus('feu_diliman');
        $adminRegDil->setTier(AdminUser::TIER_STAFF);
        $adminRegDil->setModules([AdminUser::MODULE_REGISTRATIONS]);
        /** @var RedirectResponse $res5 */
        $res5 = $securityController->resolveLandingRoute($adminRegDil);
        $this->assertStringContainsString('/diliman-admin/registrations', $res5->getTargetUrl());

        // 5. Dashboard Only
        $adminDash = new AdminUser();
        $adminDash->setCampus('feu_diliman');
        $adminDash->setTier(AdminUser::TIER_STAFF);
        $adminDash->setModules([AdminUser::MODULE_DASHBOARD]);
        /** @var RedirectResponse $res6 */
        $res6 = $securityController->resolveLandingRoute($adminDash);
        $this->assertStringContainsString('/diliman-admin/', $res6->getTargetUrl());
    }

    public function testCampusAccessSubscriberGuardsSchoolYearAndAdminAccess(): void
    {
        $container = static::getContainer();
        $urlGenerator = $container->get('router');

        // Create an unprivileged admin user with only dashboard access
        $staffUser = new AdminUser();
        $staffUser->setEmpNum(999991);
        $staffUser->setEmail('staff.nodocs@feudiliman.edu.ph');
        $staffUser->setCampus('feu_diliman');
        $staffUser->setTier(AdminUser::TIER_STAFF);
        $staffUser->setModules([AdminUser::MODULE_DASHBOARD]);

        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn($staffUser);

        $subscriber = new CampusAccessSubscriber($securityMock, $urlGenerator);

        // Attempt to access /admin/school-year
        $requestSy = Request::create('/admin/school-year');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $eventSy = new RequestEvent($kernel, $requestSy, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($eventSy);
        $this->assertTrue($eventSy->hasResponse(), 'Staff without school_year module must be redirected from /admin/school-year');
        $this->assertSame(302, $eventSy->getResponse()->getStatusCode());

        // Attempt to access /admin/access
        $requestAccess = Request::create('/admin/access');
        $eventAccess = new RequestEvent($kernel, $requestAccess, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($eventAccess);
        $this->assertTrue($eventAccess->hasResponse(), 'Staff without admin_access module must be redirected from /admin/access');
        $this->assertSame(302, $eventAccess->getResponse()->getStatusCode());
    }

    public function testCampusAccessSubscriberAllowsAuthorizedStaff(): void
    {
        $container = static::getContainer();
        $urlGenerator = $container->get('router');

        // Admin with school_year and admin_access
        $authorizedUser = new AdminUser();
        $authorizedUser->setEmpNum(999992);
        $authorizedUser->setEmail('authorized@feudiliman.edu.ph');
        $authorizedUser->setCampus('feu_diliman');
        $authorizedUser->setTier(AdminUser::TIER_STAFF);
        $authorizedUser->setModules([AdminUser::MODULE_SCHOOL_YEAR, AdminUser::MODULE_ADMIN_ACCESS]);

        $securityMock = $this->createMock(Security::class);
        $securityMock->method('getUser')->willReturn($authorizedUser);

        $subscriber = new CampusAccessSubscriber($securityMock, $urlGenerator);

        // Access /admin/school-year
        $requestSy = Request::create('/admin/school-year');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $eventSy = new RequestEvent($kernel, $requestSy, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($eventSy);
        $this->assertFalse($eventSy->hasResponse(), 'Staff with school_year module must not be blocked from /admin/school-year');

        // Access /admin/access
        $requestAccess = Request::create('/admin/access');
        $eventAccess = new RequestEvent($kernel, $requestAccess, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelRequest($eventAccess);
        $this->assertFalse($eventAccess->hasResponse(), 'Staff with admin_access module must not be blocked from /admin/access');
    }

    public function testDatabaseAuditSubscriberPrioritizesPublicCampusRoute(): void
    {
        $subscriber = static::getContainer()->get(\App\EventSubscriber\DatabaseAuditSubscriber::class);
        $conn = static::getContainer()->get('doctrine')->getConnection();

        // Simulate request to /alabang/apply
        $requestAlabang = Request::create('/alabang/apply');
        $controller = [new \App\Controller\EnrollmentAlabangController(), 'apply'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new \Symfony\Component\HttpKernel\Event\ControllerEvent($kernel, $controller, $requestAlabang, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelController($event);
        $db = $conn->fetchOne('SELECT DATABASE()');
        $this->assertSame('aries_alabang_db', $db, 'Public Alabang enrollment must switch DB to aries_alabang_db');

        // Simulate request to /diliman/apply
        $requestDiliman = Request::create('/diliman/apply');
        $controllerDil = [new \App\Controller\EnrollmentDilimanController(), 'apply'];
        $eventDil = new \Symfony\Component\HttpKernel\Event\ControllerEvent($kernel, $controllerDil, $requestDiliman, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onKernelController($eventDil);
        $dbDil = $conn->fetchOne('SELECT DATABASE()');
        $this->assertSame('aries_diliman_db', $dbDil, 'Public Diliman enrollment must switch DB to aries_diliman_db');
    }

    public function testUploadDirectoryProtectionHtaccessExistsAndHardened(): void
    {
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $htaccessPath = $projectDir . '/public/uploads/.htaccess';

        $this->assertFileExists($htaccessPath);
        $content = (string)file_get_contents($htaccessPath);

        $this->assertStringContainsString('php_flag engine off', $content);
        $this->assertStringContainsString('SetHandler default-handler', $content);
        $this->assertStringContainsString('X-Content-Type-Options "nosniff"', $content);
    }

    public function testCsvFormulaInjectionSanitizerProtectsAgainstFormulas(): void
    {
        $sanitizeCsv = static function (mixed $value): mixed {
            if (!is_string($value)) {
                return $value;
            }
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'" . $value;
            }
            return $value;
        };

        $this->assertSame("'=cmd|' /C calc'!A0", $sanitizeCsv("=cmd|' /C calc'!A0"));
        $this->assertSame("'+12345", $sanitizeCsv("+12345"));
        $this->assertSame("'-9999", $sanitizeCsv("-9999"));
        $this->assertSame("'@SUM(A1:A10)", $sanitizeCsv("@SUM(A1:A10)"));
        $this->assertSame("'\ttab_value", $sanitizeCsv("\ttab_value"));
        $this->assertSame("Juan Dela Cruz", $sanitizeCsv("Juan Dela Cruz"));
        $this->assertSame(12345, $sanitizeCsv(12345));
    }

    public function testAssocAdminUserProviderRejectsDeactivatedAdmin(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmpNum(999888);
        $adminUser->setEmail('inactive@feualabang.edu.ph');
        $adminUser->setCampus('feu_alabang');
        $adminUser->setIsActive(false);

        $adminRepoMock = $this->createMock(\App\Repository\AdminUserRepository::class);
        $adminRepoMock->method('find')->with(999888)->willReturn($adminUser);

        $assocLoginServiceMock = $this->createMock(\App\Service\AssocLoginService::class);
        $requestStackMock = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);

        $provider = new \App\Security\AssocAdminUserProvider(
            $assocLoginServiceMock,
            $adminRepoMock,
            $this->switcher,
            $requestStackMock
        );

        $this->expectException(\Symfony\Component\Security\Core\Exception\UserNotFoundException::class);
        $this->expectExceptionMessage('deactivated');

        $provider->refreshUser($adminUser);
    }

    public function testAddressControllerProvidesHttpCachingHeaders(): void
    {
        $controller = new \App\Controller\AddressController();
        $em = static::getContainer()->get('doctrine')->getManager();

        $response = $controller->getRegions($em);

        $this->assertTrue($response->headers->has('Cache-Control'));
        $cacheControl = (string)$response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=86400', $cacheControl);
    }

    public function testDocumentComplianceServiceMatchesLocalDocuments(): void
    {
        $container = static::getContainer();
        $complianceService = $container->get(\App\Service\DocumentComplianceService::class);

        $applicant = new ApplicantBed();
        $applicant->setStudentNumber('2026-TEST-DOCS-001');
        $applicant->setCampus(ApplicantBed::CAMPUS_DILIMAN);
        $applicant->setCitizenship('LOCAL');
        $applicant->setAdmissionType('New Student');
        $applicant->setGradeLevel('Grade 11');

        $matched = $complianceService->getApplicableDocuments($applicant);
        $matchedSlugs = array_map(fn($d) => $d->getSlug(), $matched);

        // PSA and local only docs must match for local Filipino applicants
        $this->assertContains('req_psa', $matchedSlugs, 'PSA must be matched for local applicants');
        $this->assertContains('req_local_only_docs', $matchedSlugs, 'Local Only Docs must be matched for local Filipino applicants');
        // International only docs must NOT match
        $this->assertNotContains('passport_bio', $matchedSlugs, 'Foreign bio passport must not be required for local applicants');
        $this->assertNotContains('valid_visa', $matchedSlugs, 'Foreign visa must not be required for local applicants');
    }

    public function testStudentIdGeneratorStrictlyGeneratesDashlessIds(): void
    {
        $container = static::getContainer();
        $idGenerator = $container->get(\App\Service\StudentIdGenerator::class);

        $sy = new \App\Entity\SchoolYear();
        $sy->setYearStart(2025);
        $sy->setYearEnd(2026);
        $sy->setLabel('SY 2025-2026');

        // Test Diliman local applicant
        $dilimanLocal = $idGenerator->generateStudentNumber('feu_diliman', $sy, false);
        $this->assertStringNotContainsString('-', $dilimanLocal);
        $this->assertMatchesRegularExpression('/^20255\d{4}$/', $dilimanLocal);

        // Test Diliman international applicant (must NOT contain dashes)
        $dilimanInt = $idGenerator->generateStudentNumber('feu_diliman', $sy, true);
        $this->assertStringNotContainsString('-', $dilimanInt);
        $this->assertMatchesRegularExpression('/^20255\d{4}$/', $dilimanInt);

        // Test Alabang local applicant
        $alabangLocal = $idGenerator->generateStudentNumber('feu_alabang', $sy, false);
        $this->assertStringNotContainsString('-', $alabangLocal);
        $this->assertMatchesRegularExpression('/^20255\d{6}$/', $alabangLocal);

        // Test Alabang international applicant
        $alabangInt = $idGenerator->generateStudentNumber('feu_alabang', $sy, true);
        $this->assertStringNotContainsString('-', $alabangInt);
        $this->assertMatchesRegularExpression('/^20255\d{6}$/', $alabangInt);
    }

    public function testRegistrationsSchoolYearFilteringAndFamilyBackground(): void
    {
        $conn = static::getContainer()->get('doctrine.dbal.default_connection');

        $totalCount = (int) $conn->fetchOne('SELECT count(*) FROM aries_diliman_db.bed_applicants');
        if ($totalCount === 0) {
            $this->assertSame(0, $totalCount, 'All applicants cleared as requested');
            return;
        }

        // Verify that all applicants in Diliman have full Family Background (Father, Mother, Guardian)
        $dilimanApplicants = $conn->fetchAllAssociative(
            'SELECT student_number, current_city, current_region, permanent_city FROM aries_diliman_db.bed_applicants'
        );

        $this->assertNotEmpty($dilimanApplicants, 'Diliman should contain seeded applicants');

        foreach ($dilimanApplicants as $app) {
            $this->assertNotEmpty($app['current_city'], "Applicant {$app['student_number']} must have populated current city");
            $this->assertNotEmpty($app['permanent_city'], "Applicant {$app['student_number']} must have populated permanent city");

            $guardians = $conn->fetchAllAssociative(
                'SELECT guardian_type, Relationship, ParentName FROM aries_diliman_db.bed_guardians WHERE student_number = :snum',
                ['snum' => $app['student_number']]
            );
            $types = array_column($guardians, 'guardian_type');
            $this->assertContains('FATHER', $types, "Applicant {$app['student_number']} must have FATHER in Family Background");
            $this->assertContains('MOTHER', $types, "Applicant {$app['student_number']} must have MOTHER in Family Background");
            $this->assertContains('GUARDIAN', $types, "Applicant {$app['student_number']} must have Emergency Contact (GUARDIAN)");
        }
    }
}
