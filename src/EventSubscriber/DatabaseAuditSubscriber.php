<?php

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush)]
class DatabaseAuditSubscriber
{
    public function __construct(private Security $security) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $user = $this->security->getUser();

        if ($user instanceof AdminUser) {
            $em = $args->getObjectManager();
            $connection = $em->getConnection();

            // Only pass the employee number to track who is logged into the web app
            $connection->executeStatement(
                'SET @app_user_emp_num = :emp_num', 
                ['emp_num' => $user->getEmpNum()]
            );
        }
    }
}