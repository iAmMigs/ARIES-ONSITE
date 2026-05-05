<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505030438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians ADD current_region VARCHAR(100) DEFAULT NULL, ADD current_province VARCHAR(100) DEFAULT NULL, ADD current_city VARCHAR(100) DEFAULT NULL, ADD current_barangay VARCHAR(100) DEFAULT NULL, ADD current_address LONGTEXT DEFAULT NULL, ADD current_zip VARCHAR(10) DEFAULT NULL, ADD permanent_region VARCHAR(100) DEFAULT NULL, ADD permanent_province VARCHAR(100) DEFAULT NULL, ADD permanent_city VARCHAR(100) DEFAULT NULL, ADD permanent_barangay VARCHAR(100) DEFAULT NULL, ADD permanent_address LONGTEXT DEFAULT NULL, ADD permanent_zip VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians DROP current_region, DROP current_province, DROP current_city, DROP current_barangay, DROP current_address, DROP current_zip, DROP permanent_region, DROP permanent_province, DROP permanent_city, DROP permanent_barangay, DROP permanent_address, DROP permanent_zip');
    }
}
