<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503040845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_bed_applicants ADD school_type VARCHAR(50) DEFAULT NULL, ADD documents_agreed_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE bed_applicants ADD documents_agreed_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE bed_requirements DROP IsRequired');
        $this->addSql('ALTER TABLE bed_schools ADD is_international TINYINT DEFAULT 0 NOT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD region VARCHAR(100) DEFAULT NULL, ADD province VARCHAR(100) DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE document_setup ADD student_type VARCHAR(20) DEFAULT NULL, ADD nationality_type VARCHAR(20) DEFAULT NULL, ADD grade_levels JSON DEFAULT NULL, DROP is_required');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_bed_applicants DROP school_type, DROP documents_agreed_date');
        $this->addSql('ALTER TABLE bed_applicants DROP documents_agreed_date');
        $this->addSql('ALTER TABLE bed_requirements ADD IsRequired TINYINT NOT NULL');
        $this->addSql('ALTER TABLE bed_schools DROP is_international, DROP country, DROP region, DROP province, DROP city');
        $this->addSql('ALTER TABLE document_setup ADD is_required TINYINT DEFAULT 1 NOT NULL, DROP student_type, DROP nationality_type, DROP grade_levels');
    }
}
