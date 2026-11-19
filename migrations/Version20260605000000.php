<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing is_deleted column to bed_requirements table';
    }

    public function up(Schema $schema): void
    {
        // Check if column exists first, just in case
        $this->addSql('ALTER TABLE bed_requirements ADD is_deleted TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bed_requirements DROP is_deleted');
    }
}
