<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211100130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student_profile ADD birth_place VARCHAR(255) DEFAULT NULL, ADD birth_country VARCHAR(100) DEFAULT NULL, ADD birth_province VARCHAR(100) DEFAULT NULL, ADD lrn VARCHAR(50) DEFAULT NULL, ADD landline_number VARCHAR(50) DEFAULT NULL, ADD address_street VARCHAR(255) DEFAULT NULL, ADD address_barangay VARCHAR(100) DEFAULT NULL, ADD address_city VARCHAR(100) DEFAULT NULL, ADD address_province VARCHAR(100) DEFAULT NULL, ADD address_zip VARCHAR(20) DEFAULT NULL, ADD term VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE student_profile DROP birth_place, DROP birth_country, DROP birth_province, DROP lrn, DROP landline_number, DROP address_street, DROP address_barangay, DROP address_city, DROP address_province, DROP address_zip, DROP term');
    }
}
