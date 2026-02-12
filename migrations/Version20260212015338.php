<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212015338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, campus VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_AD8A54A9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_applicants (id BIGINT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) NOT NULL, student_number VARCHAR(15) DEFAULT NULL, campus VARCHAR(10) NOT NULL, grade_level VARCHAR(15) DEFAULT NULL, track_strand VARCHAR(50) DEFAULT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, middle_name VARCHAR(100) DEFAULT NULL, extension_name VARCHAR(10) DEFAULT NULL, birth_date DATE DEFAULT NULL, birth_place VARCHAR(255) DEFAULT NULL, gender VARCHAR(10) DEFAULT NULL, religion VARCHAR(50) DEFAULT NULL, citizenship VARCHAR(50) DEFAULT NULL, indigenous_group VARCHAR(255) DEFAULT NULL, lrn VARCHAR(50) DEFAULT NULL, admission_status VARCHAR(1) NOT NULL, admission_date DATE DEFAULT NULL, school_year_of_entry VARCHAR(15) DEFAULT NULL, enrollment_step SMALLINT DEFAULT 1 NOT NULL, mobile_number VARCHAR(50) NOT NULL, land_line_number VARCHAR(50) DEFAULT NULL, personal_email VARCHAR(255) NOT NULL, visa_type VARCHAR(50) DEFAULT NULL, passport_number VARCHAR(50) DEFAULT NULL, current_region VARCHAR(255) DEFAULT NULL, current_province VARCHAR(255) DEFAULT NULL, current_city VARCHAR(255) DEFAULT NULL, current_barangay VARCHAR(255) DEFAULT NULL, current_address LONGTEXT DEFAULT NULL, current_zip VARCHAR(50) DEFAULT NULL, permanent_region VARCHAR(255) DEFAULT NULL, permanent_province VARCHAR(255) DEFAULT NULL, permanent_city VARCHAR(255) DEFAULT NULL, permanent_barangay VARCHAR(255) DEFAULT NULL, permanent_address LONGTEXT DEFAULT NULL, permanent_zip VARCHAR(50) DEFAULT NULL, photo_slug LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_F3E387368F48FD6B (ad_con), UNIQUE INDEX UNIQ_F3E3873618A6C7D4 (student_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_guardians (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) DEFAULT NULL, Relationship VARCHAR(20) NOT NULL, ParentName VARCHAR(255) NOT NULL, Occupation VARCHAR(255) DEFAULT NULL, ContactNo VARCHAR(20) DEFAULT NULL, Deceased TINYINT DEFAULT 0 NOT NULL, OFW TINYINT DEFAULT 0 NOT NULL, IsPrimaryContact TINYINT DEFAULT 0 NOT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_9F5A3C4E97139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_requirements (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) DEFAULT NULL, Requirement VARCHAR(255) NOT NULL, StoredFileName VARCHAR(255) DEFAULT NULL, Slug VARCHAR(255) DEFAULT NULL, Status VARCHAR(1) NOT NULL, DateSubmitted DATETIME DEFAULT NULL, IsRequired TINYINT DEFAULT 0 NOT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_93039D6A97139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_schools (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) DEFAULT NULL, Level VARCHAR(1) NOT NULL, School VARCHAR(500) NOT NULL, YearEnd INT DEFAULT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_350844897139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bed_siblings (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) DEFAULT NULL, SiblingName VARCHAR(255) NOT NULL, School VARCHAR(255) DEFAULT NULL, IsFeuStudent TINYINT DEFAULT 0 NOT NULL, FeuStudentNo VARCHAR(20) DEFAULT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_29000C3297139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lookup_barangay (barangayCode INT AUTO_INCREMENT NOT NULL, cityCode INT NOT NULL, provinceCode INT DEFAULT NULL, regionCode INT DEFAULT NULL, barangayDesc VARCHAR(100) DEFAULT NULL, zipcode INT DEFAULT NULL, INDEX idx_barangay_city (cityCode), INDEX idx_barangay_province (provinceCode), INDEX idx_barangay_region (regionCode), PRIMARY KEY (barangayCode)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lookup_city (cityCode INT AUTO_INCREMENT NOT NULL, provinceCode INT DEFAULT NULL, regionCode INT DEFAULT NULL, cityDesc VARCHAR(100) DEFAULT NULL, zipcode INT DEFAULT NULL, INDEX idx_city_province (provinceCode), INDEX idx_city_region (regionCode), PRIMARY KEY (cityCode)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lookup_province (provinceCode INT AUTO_INCREMENT NOT NULL, regionCode INT DEFAULT NULL, provinceDesc VARCHAR(100) DEFAULT NULL, INDEX idx_province_region (regionCode), PRIMARY KEY (provinceCode)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lookup_region (regionCode INT AUTO_INCREMENT NOT NULL, regionDesc VARCHAR(100) DEFAULT NULL, ocatRegion VARCHAR(10) DEFAULT NULL, PRIMARY KEY (regionCode)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT FK_9F5A3C4E97139001 FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_requirements ADD CONSTRAINT FK_93039D6A97139001 FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_schools ADD CONSTRAINT FK_350844897139001 FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_siblings ADD CONSTRAINT FK_29000C3297139001 FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians DROP FOREIGN KEY FK_9F5A3C4E97139001');
        $this->addSql('ALTER TABLE bed_requirements DROP FOREIGN KEY FK_93039D6A97139001');
        $this->addSql('ALTER TABLE bed_schools DROP FOREIGN KEY FK_350844897139001');
        $this->addSql('ALTER TABLE bed_siblings DROP FOREIGN KEY FK_29000C3297139001');
        $this->addSql('DROP TABLE admin_user');
        $this->addSql('DROP TABLE bed_applicants');
        $this->addSql('DROP TABLE bed_guardians');
        $this->addSql('DROP TABLE bed_requirements');
        $this->addSql('DROP TABLE bed_schools');
        $this->addSql('DROP TABLE bed_siblings');
        $this->addSql('DROP TABLE lookup_barangay');
        $this->addSql('DROP TABLE lookup_city');
        $this->addSql('DROP TABLE lookup_province');
        $this->addSql('DROP TABLE lookup_region');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
