<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218033202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_applicant_guardians (id INT AUTO_INCREMENT NOT NULL, parent_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, relationship VARCHAR(50) NOT NULL, occupation VARCHAR(100) DEFAULT NULL, contact_no VARCHAR(50) DEFAULT NULL, deceased TINYINT DEFAULT 0 NOT NULL, ofw TINYINT DEFAULT 0 NOT NULL, student_number VARCHAR(20) NOT NULL, INDEX IDX_F09123E818A6C7D4 (student_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_applicant_guardians ADD CONSTRAINT FK_F09123E818A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_guardians DROP FOREIGN KEY `FK_9F5A3C4E18A6C7D4`');
        $this->addSql('DROP TABLE bed_guardians');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bed_guardians (Relationship VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, ParentName VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, Occupation VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ContactNo VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, IsDeceased TINYINT DEFAULT 0 NOT NULL, IsOFW TINYINT DEFAULT 0 NOT NULL, student_number VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX IDX_9F5A3C4E18A6C7D4 (student_number), PRIMARY KEY (student_number, Relationship)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT `FK_9F5A3C4E18A6C7D4` FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_applicant_guardians DROP FOREIGN KEY FK_F09123E818A6C7D4');
        $this->addSql('DROP TABLE bed_applicant_guardians');
    }
}
