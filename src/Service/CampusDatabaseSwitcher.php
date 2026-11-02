<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Manages dynamic runtime database switching between FEU Diliman (aries_diliman_db)
 * and FEU Alabang (aries_alabang_db) on the primary MySQL connection.
 */
class CampusDatabaseSwitcher
{
    public const DB_DILIMAN = 'aries_diliman_db';
    public const DB_ALABANG = 'aries_alabang_db';

    public const CAMPUS_DILIMAN = 'feu_diliman';
    public const CAMPUS_ALABANG = 'feu_alabang';

    private ?string $currentDatabase = null;
    private readonly Connection $connection;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        $this->connection = $this->em->getConnection();
    }

    /**
     * Resolves the database name for a given campus code or identifier.
     */
    public function getDatabaseForCampus(?string $campus): string
    {
        if ($campus === null) {
            return self::DB_DILIMAN;
        }

        $normalized = strtolower(trim($campus));

        if (in_array($normalized, ['feu_alabang', 'falab', 'alabang'], true)) {
            return self::DB_ALABANG;
        }

        return self::DB_DILIMAN;
    }

    /**
     * Switches the active connection database to the specified campus.
     */
    public function switchToCampus(string $campus): void
    {
        $targetDb = $this->getDatabaseForCampus($campus);
        $this->switchToDatabase($targetDb);
    }

    /**
     * Switches the active connection database to the given database name.
     */
    public function switchToDatabase(string $databaseName): void
    {
        if ($this->currentDatabase === $databaseName) {
            return;
        }

        // Validate database name against allowable databases to avoid injection
        if (!in_array($databaseName, [self::DB_DILIMAN, self::DB_ALABANG, 'aries_db'], true)) {
            throw new \InvalidArgumentException(sprintf('Disallowed database name: "%s"', $databaseName));
        }

        $this->connection->executeStatement(sprintf('USE `%s`', $databaseName));
        $this->currentDatabase = $databaseName;
    }

    /**
     * Returns the name of the currently selected database.
     */
    public function getCurrentDatabase(): ?string
    {
        return $this->currentDatabase;
    }
}

