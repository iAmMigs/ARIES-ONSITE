<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\AdminUser;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsDoctrineListener(event: Events::onFlush)]
class DatabaseAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly CampusDatabaseSwitcher $databaseSwitcher
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 300 runs before Symfony's FirewallListener (priority 8)
            KernelEvents::REQUEST => ['onKernelRequest', 300],
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    /**
     * Routes the active database connection to aries_diliman_db or aries_alabang_db
     * based on request URL, query/POST parameters, or authenticated admin campus.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = strtolower($request->getPathInfo());

        // 1. Direct path matching
        if (str_contains($path, 'alabang')) {
            if ($request->hasSession()) {
                $request->getSession()->set('_active_campus', CampusDatabaseSwitcher::CAMPUS_ALABANG);
            }
            $this->databaseSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_ALABANG);
            return;
        }

        if (str_contains($path, 'diliman')) {
            if ($request->hasSession()) {
                $request->getSession()->set('_active_campus', CampusDatabaseSwitcher::CAMPUS_DILIMAN);
            }
            $this->databaseSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_DILIMAN);
            return;
        }

        // 2. Explicit form parameter during login (e.g. POST /login)
        $campusParam = $request->request->get('_campus');
        if (is_string($campusParam) && $campusParam !== '') {
            if ($request->hasSession()) {
                $request->getSession()->set('_active_campus', $campusParam);
            }
            $this->databaseSwitcher->switchToCampus($campusParam);
            return;
        }

        $failurePath = (string) $request->request->get('_failure_path', '');
        if (str_contains($failurePath, 'alabang')) {
            $this->databaseSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_ALABANG);
            return;
        }
        if (str_contains($failurePath, 'diliman')) {
            $this->databaseSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_DILIMAN);
            return;
        }

        // 3. Query string parameter (e.g. /api/documents/required?campus=FALAB)
        $queryCampus = $request->query->get('campus');
        if (is_string($queryCampus) && $queryCampus !== '') {
            $this->databaseSwitcher->switchToCampus($queryCampus);
            return;
        }

        // 4. Fallback to active campus stored in session
        if ($request->hasSession()) {
            $sessionCampus = $request->getSession()->get('_active_campus');
            if (is_string($sessionCampus) && $sessionCampus !== '') {
                $this->databaseSwitcher->switchToCampus($sessionCampus);
                return;
            }
        }

        // 4. Fallback to authenticated admin's campus if logged in
        $user = $this->security->getUser();
        if ($user instanceof AdminUser && $user->getCampus()) {
            $this->databaseSwitcher->switchToCampus($user->getCampus());
            return;
        }

        // Default baseline to Diliman
        $this->databaseSwitcher->switchToCampus(CampusDatabaseSwitcher::CAMPUS_DILIMAN);
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
        $request = $event->getRequest();
        $path = strtolower($request->getPathInfo());

        // Prioritize explicit path context, then request campus parameter, then authenticated user campus
        $targetCampus = null;
        if (str_contains($path, 'alabang')) {
            if ($request->hasSession()) {
                $request->getSession()->set('_active_campus', CampusDatabaseSwitcher::CAMPUS_ALABANG);
            }
            $targetCampus = CampusDatabaseSwitcher::CAMPUS_ALABANG;
        } elseif (str_contains($path, 'diliman')) {
            $targetCampus = CampusDatabaseSwitcher::CAMPUS_DILIMAN;
        } elseif ($request->query->has('campus') && is_string($request->query->get('campus'))) {
            $targetCampus = (string)$request->query->get('campus');
        } elseif ($user instanceof AdminUser && $user->getCampus()) {
            $targetCampus = $user->getCampus();
        }

        if ($targetCampus !== null) {
            $this->databaseSwitcher->switchToCampus($targetCampus);
        }

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
