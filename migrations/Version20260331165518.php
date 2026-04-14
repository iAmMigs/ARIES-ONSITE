<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331165518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_applicants ADD school_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE bed_guardians ADD OfwCountry VARCHAR(100) DEFAULT NULL, ADD Email VARCHAR(255) DEFAULT NULL, ADD Address LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_applicants DROP school_type');
        $this->addSql('ALTER TABLE bed_guardians DROP OfwCountry, DROP Email, DROP Address');
    }
}
