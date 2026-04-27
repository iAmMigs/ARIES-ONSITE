<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427160843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the school_year table and seeds SY2526 as the active school year for both campuses.';
    }

    public function up(Schema $schema): void
    {
        // Create the school_year table used to manage per-campus enrollment windows and student ID generation
        $this->addSql(<<<'SQL'
            CREATE TABLE school_year (
              id INT AUTO_INCREMENT NOT NULL,
              label VARCHAR(10) NOT NULL,
              year_start INT NOT NULL,
              year_end INT NOT NULL,
              campus VARCHAR(10) NOT NULL,
              is_active TINYINT DEFAULT 0 NOT NULL,
              enrollment_open TINYINT DEFAULT 0 NOT NULL,
              created_at DATETIME NOT NULL,
              PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        // Seed SY2526 (2025-2026) as the initial active school year for both campuses.
        // Enrollment is closed by default — admins must explicitly open it.
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->addSql(
            "INSERT INTO school_year (label, year_start, year_end, campus, is_active, enrollment_open, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['SY2526', 2025, 2026, 'FALAB', 1, 0, $now]
        );
        $this->addSql(
            "INSERT INTO school_year (label, year_start, year_end, campus, is_active, enrollment_open, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
            ['SY2526', 2025, 2026, 'FDIL', 1, 0, $now]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE school_year');
    }
}
