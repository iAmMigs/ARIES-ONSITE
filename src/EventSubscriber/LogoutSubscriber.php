<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => ['onLogout', 64],
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $user = $token?->getUser();
        $request = $event->getRequest();

        $campus = null;
        if ($user instanceof AdminUser) {
            $campus = $user->getCampus();
        }

        if (!$campus) {
            $queryCampus = $request->query->get('campus');
            if ($queryCampus === 'alabang' || $queryCampus === 'feu_alabang') {
                $campus = 'feu_alabang';
            } elseif ($queryCampus === 'diliman' || $queryCampus === 'feu_diliman') {
                $campus = 'feu_diliman';
            } else {
                $referer = (string) $request->headers->get('referer', '');
                if (str_contains(strtolower($referer), 'alabang')) {
                    $campus = 'feu_alabang';
                }
            }
        }

        $reason = $request->query->get('reason');
        if ($reason === 'timeout') {
            if ($campus === 'feu_alabang') {
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_alabang', ['reason' => 'timeout'])));
            } else {
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_auth_login_diliman', ['reason' => 'timeout'])));
            }
            return;
        }

        if ($campus === 'feu_alabang') {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home_alabang')));
        } else {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_home_diliman')));
        }
    }
}
