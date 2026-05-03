<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503213756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_bed_guardians CHANGE address address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_requirements ADD IsRequired TINYINT DEFAULT NULL, ADD IsDeleted TINYINT DEFAULT NULL, DROP is_deleted');
        $this->addSql('ALTER TABLE audit_bed_schools CHANGE is_international is_international TINYINT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_campus ON bed_applicants (campus)');
        $this->addSql('CREATE INDEX idx_admission_status ON bed_applicants (admission_status)');
        $this->addSql('CREATE INDEX idx_school_year ON bed_applicants (school_year_of_entry)');
        $this->addSql('CREATE INDEX idx_grade_level ON bed_applicants (grade_level)');
        $this->addSql('CREATE INDEX idx_admission_type ON bed_applicants (admission_type)');
        $this->addSql('CREATE INDEX idx_created_at ON bed_applicants (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_bed_guardians CHANGE address address TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_requirements ADD is_deleted TINYINT DEFAULT 0 NOT NULL, DROP IsRequired, DROP IsDeleted');
        $this->addSql('ALTER TABLE audit_bed_schools CHANGE is_international is_international TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX idx_campus ON bed_applicants');
        $this->addSql('DROP INDEX idx_admission_status ON bed_applicants');
        $this->addSql('DROP INDEX idx_school_year ON bed_applicants');
        $this->addSql('DROP INDEX idx_grade_level ON bed_applicants');
        $this->addSql('DROP INDEX idx_admission_type ON bed_applicants');
        $this->addSql('DROP INDEX idx_created_at ON bed_applicants');
    }
}
