<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class CampusAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $path = $request->getPathInfo();

        if (str_starts_with($path, '/alabang-admin')) {
            return new RedirectResponse($this->urlGenerator->generate('app_auth_login_alabang'));
        }
        
        if (str_starts_with($path, '/diliman-admin')) {
            return new RedirectResponse($this->urlGenerator->generate('app_auth_login_diliman'));
        }

        // Default fallback
        return new RedirectResponse($this->urlGenerator->generate('app_auth_login_diliman'));
    }
}
