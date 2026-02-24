<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223031809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE document_setup (id INT AUTO_INCREMENT NOT NULL, document_name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, folder_name VARCHAR(100) NOT NULL, is_required TINYINT DEFAULT 1 NOT NULL, campus VARCHAR(50) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bed_requirements ADD IsDeleted TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE document_setup');
        $this->addSql('ALTER TABLE bed_requirements DROP IsDeleted');
    }
}
