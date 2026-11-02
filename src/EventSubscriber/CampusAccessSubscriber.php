<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CampusAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $user = $this->security->getUser();
        if (!$user instanceof AdminUser) {
            return;
        }

        $userCampus = $user->getCampus();

        $isAdminPath = str_starts_with($path, '/alabang-admin')
            || str_starts_with($path, '/diliman-admin')
            || str_starts_with($path, '/admin')
            || str_starts_with($path, '/login')
            || str_starts_with($path, '/logout')
            || str_starts_with($path, '/api/')
            || str_starts_with($path, '/_')
            || str_starts_with($path, '/assets')
            || str_starts_with($path, '/build');

        if (!$isAdminPath) {
            $this->security->logout(false);
            return;
        }

        // 1. Campus Isolation
        if (str_starts_with($path, '/alabang-admin') && $userCampus !== 'feu_alabang') {
            $this->security->logout(false);
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_alabang')));
            return;
        }

        if (str_starts_with($path, '/diliman-admin') && $userCampus !== 'feu_diliman') {
            $this->security->logout(false);
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_diliman')));
            return;
        }

        if (str_starts_with($path, '/admin/access/alabang') && $userCampus !== 'feu_alabang') {
            $this->security->logout(false);
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_alabang')));
            return;
        }

        if (str_starts_with($path, '/admin/access/diliman') && $userCampus !== 'feu_diliman') {
            $this->security->logout(false);
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_diliman')));
            return;
        }

        // 2. Module Access Enforcement (Master admins bypass)
        if ($user->isMasterAdmin()) {
            return;
        }

        $requiredModule = null;

        if (str_contains($path, '/dashboard')) {
            $requiredModule = AdminUser::MODULE_DASHBOARD;
        } elseif (
            str_contains($path, '/registrations') ||
            str_contains($path, '/registration/') ||
            str_contains($path, '/view/') ||
            str_contains($path, '/edit/') ||
            str_contains($path, '/export') ||
            str_contains($path, '/waive')
        ) {
            $requiredModule = AdminUser::MODULE_REGISTRATIONS;
        } elseif (str_contains($path, '/document-setup')) {
            $requiredModule = AdminUser::MODULE_DOCUMENTS;
        } elseif (str_contains($path, '/school-year')) {
            $requiredModule = AdminUser::MODULE_SCHOOL_YEAR;
        } elseif (str_contains($path, '/admin/access') || str_contains($path, '/api/admin/assoc')) {
            $requiredModule = AdminUser::MODULE_ADMIN_ACCESS;
        }

        if ($requiredModule !== null && !$user->hasModule($requiredModule)) {
            $redirectUrl = $this->resolveFallbackUrl($user);
            $event->setResponse(new RedirectResponse($redirectUrl));
        }
    }

    private function resolveFallbackUrl(AdminUser $user): string
    {
        $campus = $user->getCampus();
        $isAlabang = ($campus === 'feu_alabang');

        if ($user->hasModule(AdminUser::MODULE_DASHBOARD)) {
            return $this->urlGenerator->generate($isAlabang ? 'app_admin_alabang_dashboard' : 'app_admin_diliman_dashboard');
        }

        if ($user->hasModule(AdminUser::MODULE_REGISTRATIONS)) {
            return $this->urlGenerator->generate($isAlabang ? 'app_admin_alabang_registrations' : 'app_admin_diliman_registrations');
        }

        if ($user->hasModule(AdminUser::MODULE_DOCUMENTS)) {
            return $this->urlGenerator->generate($isAlabang ? 'app_admin_alabang_documents' : 'app_admin_diliman_documents');
        }

        if ($user->hasModule(AdminUser::MODULE_SCHOOL_YEAR)) {
            return $this->urlGenerator->generate('app_admin_school_year_index', ['campus' => $isAlabang ? 'alabang' : 'diliman']);
        }

        if ($user->hasModule(AdminUser::MODULE_ADMIN_ACCESS)) {
            return $this->urlGenerator->generate('app_admin_access_index', ['campus' => $isAlabang ? 'alabang' : 'diliman']);
        }

        return $this->urlGenerator->generate($isAlabang ? 'app_auth_login_alabang' : 'app_auth_login_diliman');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }
}
