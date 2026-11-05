<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;

/**
 * Service to interact with campus-specific associate profile and login databases:
 *  - aries_diliman_associates / aries_alabang_associates (associates table)
 *  - aries_diliman_assoc_login / aries_alabang_assoc_login (assoc_login table)
 */
class AssocLoginService
{
    public function __construct(
        private readonly Connection $defaultConnection,
        private readonly CampusDatabaseSwitcher $databaseSwitcher
    ) {
    }

    /**
     * Resolves the campus string to normalized 'feu_diliman' or 'feu_alabang'.
     */
    public function resolveCampus(?string $campus): string
    {
        $normalized = strtolower(trim((string) $campus));
        if (in_array($normalized, [CampusDatabaseSwitcher::CAMPUS_ALABANG, 'falab', 'alabang'], true)) {
            return CampusDatabaseSwitcher::CAMPUS_ALABANG;
        }
        return CampusDatabaseSwitcher::CAMPUS_DILIMAN;
    }

    /**
     * Looks up an associate profile and login credentials strictly by employee ID for a specific campus.
     *
     * @return array{
     *     emp_id: int,
     *     user_name: string,
     *     password: string,
     *     first_name: ?string,
     *     last_name: ?string,
     *     middle_name: ?string,
     *     title: ?string,
     *     email: ?string,
     *     is_active: bool,
     *     campus: string
     * }|null
     */
    public function findByEmployeeIdForCampus(string $employeeId, string $campus): ?array
    {
        $cleanId = trim($employeeId);
        if ($cleanId === '' || !ctype_digit($cleanId)) {
            return null;
        }

        $campusCode = $this->resolveCampus($campus);
        $assocDb = $this->databaseSwitcher->getAssociatesDatabaseForCampus($campusCode);
        $loginDb = $this->databaseSwitcher->getAssocLoginDatabaseForCampus($campusCode);

        try {
            $sql = sprintf(
                'SELECT 
                    a.emp_id,
                    a.last_name,
                    a.first_name,
                    a.middle_name,
                    a.title,
                    a.email,
                    a.is_active,
                    l.user_name,
                    l.password
                FROM `%s`.`associates` a
                LEFT JOIN `%s`.`assoc_login` l ON l.emp_id = a.emp_id
                WHERE a.emp_id = :id
                LIMIT 1',
                $assocDb,
                $loginDb
            );

            $row = $this->defaultConnection->executeQuery($sql, ['id' => (int) $cleanId])->fetchAssociative();
            if (!$row) {
                return null;
            }

            return [
                'emp_id'      => (int) $row['emp_id'],
                'user_name'   => (string) ($row['user_name'] ?? ('user' . $cleanId)),
                'password'    => (string) ($row['password'] ?? ''),
                'first_name'  => isset($row['first_name']) ? (string) $row['first_name'] : null,
                'last_name'   => isset($row['last_name']) ? (string) $row['last_name'] : null,
                'middle_name' => isset($row['middle_name']) ? (string) $row['middle_name'] : null,
                'title'       => isset($row['title']) ? (string) $row['title'] : null,
                'email'       => isset($row['email']) ? (string) $row['email'] : null,
                'is_active'   => (bool) ($row['is_active'] ?? true),
                'campus'      => $campusCode,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Checks whether an associate exists in the opposite campus.
     */
    public function existsInOtherCampus(string $employeeId, string $currentCampus): bool
    {
        $cleanId = trim($employeeId);
        if ($cleanId === '' || !ctype_digit($cleanId)) {
            return false;
        }

        $currentCampusCode = $this->resolveCampus($currentCampus);
        $otherCampusCode = ($currentCampusCode === CampusDatabaseSwitcher::CAMPUS_ALABANG)
            ? CampusDatabaseSwitcher::CAMPUS_DILIMAN
            : CampusDatabaseSwitcher::CAMPUS_ALABANG;

        $otherAssocDb = $this->databaseSwitcher->getAssociatesDatabaseForCampus($otherCampusCode);

        try {
            $count = (int) $this->defaultConnection->fetchOne(
                sprintf('SELECT COUNT(*) FROM `%s`.`associates` WHERE emp_id = :id', $otherAssocDb),
                ['id' => (int) $cleanId]
            );
            return $count > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Legacy compatibility method: looks up associate across target campus or both campuses.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmployeeId(string $employeeId, ?string $campus = null): ?array
    {
        if ($campus !== null) {
            return $this->findByEmployeeIdForCampus($employeeId, $campus);
        }

        // Try Diliman first, then Alabang
        $result = $this->findByEmployeeIdForCampus($employeeId, CampusDatabaseSwitcher::CAMPUS_DILIMAN);
        if ($result !== null) {
            return $result;
        }

        return $this->findByEmployeeIdForCampus($employeeId, CampusDatabaseSwitcher::CAMPUS_ALABANG);
    }

    /**
     * Verifies plain text password against stored hash (MD5, password_hash, or plain).
     */
    public function verifyPassword(string $plainPassword, string $storedHash): bool
    {
        if ($plainPassword === '' || $storedHash === '') {
            return false;
        }

        $stored = trim($storedHash);
        if (hash_equals(strtolower($stored), md5($plainPassword))) {
            return true;
        }
        if (password_verify($plainPassword, $stored)) {
            return true;
        }
        return hash_equals($stored, $plainPassword);
    }
}
