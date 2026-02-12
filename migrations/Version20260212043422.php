<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212043422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_applicant_requirements (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(255) NOT NULL, requirement VARCHAR(255) NOT NULL, stored_file_name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, date_submitted DATETIME DEFAULT NULL, is_required TINYINT NOT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_8F1AA97197139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_applicant_requirements ADD CONSTRAINT FK_8F1AA97197139001 FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id)');
        $this->addSql('ALTER TABLE bed_requirements DROP FOREIGN KEY `FK_93039D6A97139001`');
        $this->addSql('DROP TABLE bed_requirements');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_requirements (id INT AUTO_INCREMENT NOT NULL, ad_con VARCHAR(15) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Requirement VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, StoredFileName VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Slug VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Status VARCHAR(1) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, DateSubmitted DATETIME DEFAULT NULL, IsRequired TINYINT DEFAULT 0 NOT NULL, applicant_id BIGINT NOT NULL, INDEX IDX_93039D6A97139001 (applicant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE bed_requirements ADD CONSTRAINT `FK_93039D6A97139001` FOREIGN KEY (applicant_id) REFERENCES bed_applicants (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_applicant_requirements DROP FOREIGN KEY FK_8F1AA97197139001');
        $this->addSql('DROP TABLE bed_applicant_requirements');
    }
}
