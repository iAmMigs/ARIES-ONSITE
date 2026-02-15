<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215223835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_applicants (ad_con VARCHAR(20) NOT NULL, student_number VARCHAR(20) DEFAULT NULL, campus VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL, education_type VARCHAR(20) DEFAULT NULL, grade_level VARCHAR(20) DEFAULT NULL, track_strand VARCHAR(50) DEFAULT NULL, lrn VARCHAR(20) DEFAULT NULL, admission_status VARCHAR(20) NOT NULL, school_year_of_entry VARCHAR(15) DEFAULT NULL, enrollment_step SMALLINT DEFAULT 1 NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, middle_name VARCHAR(100) DEFAULT NULL, extension_name VARCHAR(10) DEFAULT NULL, birth_date DATE DEFAULT NULL, birth_place VARCHAR(255) DEFAULT NULL, birth_country VARCHAR(255) DEFAULT NULL, gender VARCHAR(10) DEFAULT NULL, religion VARCHAR(50) DEFAULT NULL, citizenship VARCHAR(50) DEFAULT NULL, indigenous_group VARCHAR(255) DEFAULT NULL, mobile_number VARCHAR(50) NOT NULL, land_line_number VARCHAR(50) DEFAULT NULL, personal_email VARCHAR(255) NOT NULL, current_region VARCHAR(255) DEFAULT NULL, current_province VARCHAR(255) DEFAULT NULL, current_city VARCHAR(255) DEFAULT NULL, current_barangay VARCHAR(255) DEFAULT NULL, current_address LONGTEXT DEFAULT NULL, current_zip VARCHAR(50) DEFAULT NULL, permanent_region VARCHAR(255) DEFAULT NULL, permanent_province VARCHAR(255) DEFAULT NULL, permanent_city VARCHAR(255) DEFAULT NULL, permanent_barangay VARCHAR(255) DEFAULT NULL, permanent_address LONGTEXT DEFAULT NULL, permanent_zip VARCHAR(50) DEFAULT NULL, visa_type VARCHAR(50) DEFAULT NULL, passport_number VARCHAR(50) DEFAULT NULL, photo_slug LONGTEXT DEFAULT NULL, admission_date DATE DEFAULT NULL, UNIQUE INDEX UNIQ_F3E3873618A6C7D4 (student_number), PRIMARY KEY (ad_con)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_guardians (Relationship VARCHAR(20) NOT NULL, ParentName VARCHAR(255) DEFAULT NULL, Occupation VARCHAR(100) DEFAULT NULL, ContactNo VARCHAR(50) DEFAULT NULL, IsDeceased TINYINT DEFAULT 0 NOT NULL, IsOFW TINYINT DEFAULT 0 NOT NULL, ad_con VARCHAR(20) NOT NULL, INDEX IDX_9F5A3C4E8F48FD6B (ad_con), PRIMARY KEY (ad_con, Relationship)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_requirements (Slug VARCHAR(100) NOT NULL, Requirement VARCHAR(100) NOT NULL, StoredFileName VARCHAR(255) DEFAULT NULL, Status VARCHAR(1) DEFAULT \'P\' NOT NULL, IsRequired TINYINT NOT NULL, DateSubmitted DATETIME DEFAULT NULL, ad_con VARCHAR(20) NOT NULL, INDEX IDX_93039D6A8F48FD6B (ad_con), PRIMARY KEY (ad_con, Slug)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_schools (Level VARCHAR(1) NOT NULL, School VARCHAR(255) NOT NULL, YearEnd INT DEFAULT NULL, ad_con VARCHAR(20) NOT NULL, INDEX IDX_35084488F48FD6B (ad_con), PRIMARY KEY (ad_con, Level)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_siblings (SiblingName VARCHAR(255) NOT NULL, School VARCHAR(255) DEFAULT NULL, IsFeuStudent TINYINT DEFAULT 0 NOT NULL, FeuStudentNo VARCHAR(50) DEFAULT NULL, ad_con VARCHAR(20) NOT NULL, INDEX IDX_29000C328F48FD6B (ad_con), PRIMARY KEY (ad_con, SiblingName)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT FK_9F5A3C4E8F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_requirements ADD CONSTRAINT FK_93039D6A8F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_schools ADD CONSTRAINT FK_35084488F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_siblings ADD CONSTRAINT FK_29000C328F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians DROP FOREIGN KEY FK_9F5A3C4E8F48FD6B');
        $this->addSql('ALTER TABLE bed_requirements DROP FOREIGN KEY FK_93039D6A8F48FD6B');
        $this->addSql('ALTER TABLE bed_schools DROP FOREIGN KEY FK_35084488F48FD6B');
        $this->addSql('ALTER TABLE bed_siblings DROP FOREIGN KEY FK_29000C328F48FD6B');
        $this->addSql('DROP TABLE bed_applicants');
        $this->addSql('DROP TABLE bed_guardians');
        $this->addSql('DROP TABLE bed_requirements');
        $this->addSql('DROP TABLE bed_schools');
        $this->addSql('DROP TABLE bed_siblings');
    }
}
