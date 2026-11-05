<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use App\Service\AssocLoginService;
use App\Service\CampusDatabaseSwitcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads and bridges authenticated users between the campus-specific Associate database
 * and ARIES campus-specific AdminUser entities.
 *
 * @implements UserProviderInterface<AdminUser>
 */
class AssocAdminUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly AssocLoginService $assocLoginService,
        private readonly AdminUserRepository $adminUserRepo,
        private readonly CampusDatabaseSwitcher $databaseSwitcher,
        private readonly RequestStack $requestStack
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $cleanIdentifier = trim($identifier);

        // Determine target campus from current HTTP request or session
        $request = $this->requestStack->getMainRequest();
        $targetCampus = null;
        if ($request) {
            $targetCampus = (string) $request->request->get('_campus', '');
            if (!$targetCampus) {
                $failurePath = (string) $request->request->get('_failure_path', '');
                if (str_contains($failurePath, 'alabang')) {
                    $targetCampus = CampusDatabaseSwitcher::CAMPUS_ALABANG;
                } elseif (str_contains($failurePath, 'diliman')) {
                    $targetCampus = CampusDatabaseSwitcher::CAMPUS_DILIMAN;
                } else {
                    $path = strtolower($request->getPathInfo());
                    if (str_contains($path, 'alabang')) {
                        $targetCampus = CampusDatabaseSwitcher::CAMPUS_ALABANG;
                    } elseif (str_contains($path, 'diliman')) {
                        $targetCampus = CampusDatabaseSwitcher::CAMPUS_DILIMAN;
                    } elseif ($request->hasSession()) {
                        $targetCampus = (string) $request->getSession()->get('_active_campus', '');
                    }
                }
            }
        }

        $resolvedCampus = $this->assocLoginService->resolveCampus($targetCampus ?: CampusDatabaseSwitcher::CAMPUS_DILIMAN);

        // 1. Check campus-specific assoc_login table strictly by Employee ID
        $assoc = $this->assocLoginService->findByEmployeeIdForCampus($cleanIdentifier, $resolvedCampus);

        // Cross-campus check: if not found in this campus, check if they exist in the other campus
        if (!$assoc) {
            if ($this->assocLoginService->existsInOtherCampus($cleanIdentifier, $resolvedCampus)) {
                throw new CustomUserMessageAuthenticationException('This Employee is not an associate of this campus');
            }

            $e = new UserNotFoundException(sprintf('User with Employee ID "%s" not found in Associate directory.', $cleanIdentifier));
            $e->setUserIdentifier($cleanIdentifier);
            throw $e;
        }

        $empId = (int) $assoc['emp_id'];

        // 2. Switch DB to target campus and find AdminUser
        $this->databaseSwitcher->switchToCampus($resolvedCampus);
        $adminUser = $this->adminUserRepo->find($empId);

        if (!$adminUser) {
            throw new CustomUserMessageAuthenticationException(
                'This associate account is not registered as an ARIES Administrator for this campus. Please contact your system administrator.'
            );
        }

        if (!$adminUser->isActive()) {
            throw new CustomUserMessageAuthenticationException(
                'This administrator account has been deactivated.'
            );
        }

        // 3. Inject the password hash and username from assoc_login into in-memory AdminUser
        $adminUser->setPassword((string) ($assoc['password'] ?? ''));
        if (!empty($assoc['user_name'])) {
            $adminUser->setUsername((string) $assoc['user_name']);
        }

        return $adminUser;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $empNum = $user->getEmpNum();
        if ($empNum === null) {
            throw new UserNotFoundException('Cannot refresh user with null employee number.');
        }

        $campus = $user->getCampus() ?: CampusDatabaseSwitcher::CAMPUS_DILIMAN;
        $this->databaseSwitcher->switchToCampus($campus);

        $refreshed = $this->adminUserRepo->find($empNum);
        if (!$refreshed) {
            $e = new UserNotFoundException(sprintf('AdminUser with employee number "%d" not found.', $empNum));
            $e->setUserIdentifier((string) $empNum);
            throw $e;
        }

        if (!$refreshed->isActive()) {
            $e = new UserNotFoundException(sprintf('This administrator account (#%d) has been deactivated.', $empNum));
            $e->setUserIdentifier((string) $empNum);
            throw $e;
        }

        // Maintain password hash parity with token to prevent deauthentication
        $assoc = $this->assocLoginService->findByEmployeeIdForCampus((string) $empNum, $campus);
        if ($assoc) {
            $refreshed->setPassword((string) ($assoc['password'] ?? ''));
            if (!empty($assoc['user_name'])) {
                $refreshed->setUsername((string) $assoc['user_name']);
            }
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return AdminUser::class === $class || is_subclass_of($class, AdminUser::class);
    }
}
