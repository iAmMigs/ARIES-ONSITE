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
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }
}
