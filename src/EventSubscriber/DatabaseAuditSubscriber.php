<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsDoctrineListener(event: Events::onFlush)]
class DatabaseAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * Sets @app_user_emp_num on the MySQL connection at the start of any request
     * made by an authenticated admin, ensuring all subsequent queries and triggers
     * record the admin's employee number in the audit logs.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        $connection = $this->em->getConnection();

        if ($user instanceof AdminUser && $user->getEmpNum() !== null) {
            $connection->executeStatement(
                'SET @app_user_emp_num = :emp_num',
                ['emp_num' => (string) $user->getEmpNum()]
            );
        } else {
            $connection->executeStatement('SET @app_user_emp_num = NULL');
        }
    }

    /**
     * Fallback listener before entity flushes to ensure @app_user_emp_num is set.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $user = $this->security->getUser();
        $em = $args->getObjectManager();
        $connection = $em->getConnection();

        if ($user instanceof AdminUser && $user->getEmpNum() !== null) {
            $connection->executeStatement(
                'SET @app_user_emp_num = :emp_num', 
                ['emp_num' => (string) $user->getEmpNum()]
            );
        }
    }
}