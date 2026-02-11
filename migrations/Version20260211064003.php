<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211064003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, campus VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_AD8A54A9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE admission_requirement (id INT AUTO_INCREMENT NOT NULL, document_type VARCHAR(50) NOT NULL, file_path VARCHAR(255) NOT NULL, student_profile_id INT NOT NULL, INDEX IDX_BAB6E3A62125FF59 (student_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_parent (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, relationship VARCHAR(100) NOT NULL, occupation VARCHAR(255) DEFAULT NULL, contact_number VARCHAR(50) DEFAULT NULL, student_profile_id INT NOT NULL, INDEX IDX_B3B8B8CF2125FF59 (student_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_profile (id INT AUTO_INCREMENT NOT NULL, ad_con_number VARCHAR(20) DEFAULT NULL, student_number VARCHAR(20) DEFAULT NULL, campus VARCHAR(50) NOT NULL, last_name VARCHAR(100) NOT NULL, first_name VARCHAR(100) NOT NULL, middle_name VARCHAR(100) DEFAULT NULL, extension_name VARCHAR(10) DEFAULT NULL, birth_date DATE DEFAULT NULL, gender VARCHAR(10) DEFAULT NULL, religion VARCHAR(50) DEFAULT NULL, citizenship VARCHAR(50) DEFAULT NULL, civil_status VARCHAR(50) DEFAULT NULL, grade_level VARCHAR(20) NOT NULL, strand VARCHAR(20) DEFAULT NULL, school_year_start VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_6C611FF792A75FEA (ad_con_number), UNIQUE INDEX UNIQ_6C611FF718A6C7D4 (student_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_school (id INT AUTO_INCREMENT NOT NULL, school_name VARCHAR(255) NOT NULL, level VARCHAR(20) NOT NULL, year_graduated VARCHAR(10) DEFAULT NULL, student_profile_id INT NOT NULL, INDEX IDX_77A8023B2125FF59 (student_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_sibling (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, school_or_company VARCHAR(255) DEFAULT NULL, age INT DEFAULT NULL, student_profile_id INT NOT NULL, INDEX IDX_7F8DD0492125FF59 (student_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE admission_requirement ADD CONSTRAINT FK_BAB6E3A62125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
        $this->addSql('ALTER TABLE student_parent ADD CONSTRAINT FK_B3B8B8CF2125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
        $this->addSql('ALTER TABLE student_school ADD CONSTRAINT FK_77A8023B2125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
        $this->addSql('ALTER TABLE student_sibling ADD CONSTRAINT FK_7F8DD0492125FF59 FOREIGN KEY (student_profile_id) REFERENCES student_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE admission_requirement DROP FOREIGN KEY FK_BAB6E3A62125FF59');
        $this->addSql('ALTER TABLE student_parent DROP FOREIGN KEY FK_B3B8B8CF2125FF59');
        $this->addSql('ALTER TABLE student_school DROP FOREIGN KEY FK_77A8023B2125FF59');
        $this->addSql('ALTER TABLE student_sibling DROP FOREIGN KEY FK_7F8DD0492125FF59');
        $this->addSql('DROP TABLE admin_user');
        $this->addSql('DROP TABLE admission_requirement');
        $this->addSql('DROP TABLE student_parent');
        $this->addSql('DROP TABLE student_profile');
        $this->addSql('DROP TABLE student_school');
        $this->addSql('DROP TABLE student_sibling');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
