<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Validates legacy MD5 password hashes used by the central Associate Login database.
 * Employs constant-time string comparison to prevent timing attacks.
 */
class Md5PasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return md5($plainPassword);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if ($hashedPassword === '' || $plainPassword === '') {
            return false;
        }

        return hash_equals(strtolower($hashedPassword), md5($plainPassword));
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
