<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404130019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians ADD OfwCountry VARCHAR(100) DEFAULT NULL, ADD Email VARCHAR(255) DEFAULT NULL, ADD Address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE bed_schools ADD SchoolYear VARCHAR(20) DEFAULT NULL, DROP YearEnd');
        $this->addSql('ALTER TABLE lookup_country CHANGE country_id country_id INT AUTO_INCREMENT NOT NULL, CHANGE country_name country_name VARCHAR(255) NOT NULL, CHANGE country_sf_id country_sf_id VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_guardians DROP OfwCountry, DROP Email, DROP Address');
        $this->addSql('ALTER TABLE bed_schools ADD YearEnd INT DEFAULT NULL, DROP SchoolYear');
        $this->addSql('ALTER TABLE lookup_country CHANGE country_id country_id INT NOT NULL, CHANGE country_name country_name VARCHAR(100) NOT NULL, CHANGE country_sf_id country_sf_id INT DEFAULT NULL');
    }
}
