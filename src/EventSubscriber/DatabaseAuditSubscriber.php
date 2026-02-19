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
        // 1. Check if a user is currently logged into the app
        $user = $this->security->getUser();
        
        if ($user instanceof AdminUser) {
            // 2. Get the active database connection
            $em = $args->getObjectManager();
            $connection = $em->getConnection();
            
            // 3. Set the MySQL session variable with the employee number
            $connection->executeStatement(
                'SET @app_user_emp_num = :emp_num', 
                ['emp_num' => $user->getEmpNum()]
            );
        }
    }
}