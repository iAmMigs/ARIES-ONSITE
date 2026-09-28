<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class CampusLoginCheckSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        $user = $passport->getUser();

        if (!$user instanceof AdminUser) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return;
        }

        $targetCampus = (string) $request->request->get('_campus', '');
        if (!$targetCampus) {
            $failurePath = (string) $request->request->get('_failure_path', '');
            if (str_contains($failurePath, 'alabang')) {
                $targetCampus = 'feu_alabang';
            } elseif (str_contains($failurePath, 'diliman')) {
                $targetCampus = 'feu_diliman';
            }
        }

        if ($targetCampus !== '') {
            $userCampus = (string) $user->getCampus();
            if ($userCampus !== $targetCampus) {
                $campusName = $targetCampus === 'feu_alabang' ? 'FEU Alabang' : 'FEU Diliman';
                throw new CustomUserMessageAuthenticationException(
                    sprintf('This account is not authorized to access the %s Admin Portal.', $campusName)
                );
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', -10],
        ];
    }
}
