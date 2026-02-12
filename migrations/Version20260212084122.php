<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212084122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
{
    // 1. Add the column allowing NULL values initially
    $this->addSql('ALTER TABLE bed_applicants ADD created_at DATETIME NULL COMMENT \'(DC2Type:datetime_immutable)\'');

    // 2. Set the current date for all existing rows
    $this->addSql('UPDATE bed_applicants SET created_at = CURRENT_TIMESTAMP');

    // 3. Now that data exists, modify the column to NOT NULL
    $this->addSql('ALTER TABLE bed_applicants MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
}

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_applicants DROP created_at');
    }
}
