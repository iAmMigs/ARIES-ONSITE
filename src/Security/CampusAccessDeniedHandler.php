<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class CampusAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->getFlashBag()->add('error', 'Access denied: You do not have permission to access that section.');
        }

        $path = strtolower($request->getPathInfo());
        if (str_contains($path, 'alabang')) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_alabang_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_admin_diliman_dashboard'));
    }
}

