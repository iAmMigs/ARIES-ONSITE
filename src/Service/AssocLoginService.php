<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Service to interact with the central associate login database schema.
 */
class AssocLoginService
{
    public function __construct(
        private readonly Connection $assocConnection
    ) {
    }

    /**
     * Looks up an associate record strictly by employee ID.
     *
     * @return array{employee_id: string, user_name: ?string, pass_word: ?string}|null
     */
    public function findByEmployeeId(string $employeeId): ?array
    {
        $cleanId = trim($employeeId);
        if ($cleanId === '') {
            return null;
        }

        $sql = 'SELECT employee_id, user_name, pass_word FROM assoc_login WHERE employee_id = :id LIMIT 1';
        $row = $this->assocConnection->executeQuery($sql, ['id' => $cleanId])->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    /**
     * Looks up an associate record by either user_name or employee_id.
     *
     * @return array{employee_id: string, user_name: ?string, pass_word: ?string}|null
     */
    public function findByIdentifier(string $identifier): ?array
    {
        $clean = trim($identifier);
        if ($clean === '') {
            return null;
        }

        $sql = 'SELECT employee_id, user_name, pass_word FROM assoc_login WHERE user_name = :identifier OR employee_id = :identifier LIMIT 1';
        $row = $this->assocConnection->executeQuery($sql, ['identifier' => $clean])->fetchAssociative();

        return is_array($row) ? $row : null;
    }

    /**
     * Verifies plain text password against stored MD5 hash in a timing-safe manner.
     */
    public function verifyPassword(string $plainPassword, string $storedMd5): bool
    {
        if ($plainPassword === '' || $storedMd5 === '') {
            return false;
        }

        return hash_equals(strtolower($storedMd5), md5($plainPassword));
    }
}
