<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218140750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_guardians (guardian_id INT AUTO_INCREMENT NOT NULL, Relationship VARCHAR(20) NOT NULL, ParentName VARCHAR(255) DEFAULT NULL, Occupation VARCHAR(100) DEFAULT NULL, ContactNo VARCHAR(50) DEFAULT NULL, IsDeceased TINYINT DEFAULT 0 NOT NULL, IsOFW TINYINT DEFAULT 0 NOT NULL, student_number VARCHAR(20) NOT NULL, INDEX IDX_9F5A3C4E18A6C7D4 (student_number), PRIMARY KEY (guardian_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT FK_9F5A3C4E18A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_applicant_guardians DROP FOREIGN KEY `FK_F09123E818A6C7D4`');
        $this->addSql('DROP TABLE bed_applicant_guardians');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_applicant_guardians (id INT AUTO_INCREMENT NOT NULL, parent_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, first_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, last_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, relationship VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, occupation VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contact_no VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, deceased TINYINT DEFAULT 0 NOT NULL, ofw TINYINT DEFAULT 0 NOT NULL, student_number VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX IDX_F09123E818A6C7D4 (student_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE bed_applicant_guardians ADD CONSTRAINT `FK_F09123E818A6C7D4` FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_guardians DROP FOREIGN KEY FK_9F5A3C4E18A6C7D4');
        $this->addSql('DROP TABLE bed_guardians');
    }
}
