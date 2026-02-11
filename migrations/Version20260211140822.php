<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211140822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lookup_barangay (barangay_code INT AUTO_INCREMENT NOT NULL, barangay_desc VARCHAR(255) NOT NULL, region_code VARCHAR(10) NOT NULL, province_code VARCHAR(10) NOT NULL, city_code VARCHAR(10) NOT NULL, zipcode INT DEFAULT NULL, PRIMARY KEY (barangay_code)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('DROP TABLE lookup_country');
        $this->addSql('DROP INDEX provinceCode ON lookup_city');
        $this->addSql('DROP INDEX regionCode ON lookup_city');
        $this->addSql('DROP INDEX cityCode ON lookup_city');
        $this->addSql('ALTER TABLE lookup_city MODIFY cityCode INT NOT NULL');
        $this->addSql('ALTER TABLE lookup_city ADD city_code VARCHAR(255) NOT NULL, ADD city_desc VARCHAR(255) NOT NULL, ADD province_code VARCHAR(10) NOT NULL, ADD region_code VARCHAR(10) NOT NULL, DROP cityCode, DROP provinceCode, DROP regionCode, DROP cityDesc, DROP zipcode, DROP PRIMARY KEY, ADD PRIMARY KEY (city_code)');
        $this->addSql('DROP INDEX provinceCode ON lookup_province');
        $this->addSql('DROP INDEX regionCode ON lookup_province');
        $this->addSql('ALTER TABLE lookup_province MODIFY provinceCode INT NOT NULL');
        $this->addSql('ALTER TABLE lookup_province ADD province_code VARCHAR(255) NOT NULL, ADD province_desc VARCHAR(255) NOT NULL, ADD region_code VARCHAR(10) NOT NULL, DROP provinceCode, DROP regionCode, DROP provinceDesc, DROP PRIMARY KEY, ADD PRIMARY KEY (province_code)');
        $this->addSql('DROP INDEX regionCode ON lookup_region');
        $this->addSql('ALTER TABLE lookup_region MODIFY regionCode INT NOT NULL');
        $this->addSql('ALTER TABLE lookup_region ADD region_code VARCHAR(255) NOT NULL, ADD region_desc VARCHAR(255) NOT NULL, DROP regionCode, DROP regionDesc, DROP ocatRegion, DROP PRIMARY KEY, ADD PRIMARY KEY (region_code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE lookup_country (country_id INT NOT NULL, country_name VARCHAR(100) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`, country_sf_id INT DEFAULT NULL, PRIMARY KEY (country_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE lookup_barangay');
        $this->addSql('ALTER TABLE lookup_city ADD cityCode INT AUTO_INCREMENT NOT NULL, ADD provinceCode INT DEFAULT NULL, ADD regionCode INT DEFAULT NULL, ADD cityDesc VARCHAR(100) DEFAULT NULL, ADD zipcode INT DEFAULT NULL, DROP city_code, DROP city_desc, DROP province_code, DROP region_code, DROP PRIMARY KEY, ADD PRIMARY KEY (cityCode)');
        $this->addSql('CREATE INDEX provinceCode ON lookup_city (provinceCode)');
        $this->addSql('CREATE INDEX regionCode ON lookup_city (regionCode)');
        $this->addSql('CREATE INDEX cityCode ON lookup_city (cityCode)');
        $this->addSql('ALTER TABLE lookup_province ADD provinceCode INT AUTO_INCREMENT NOT NULL, ADD regionCode INT DEFAULT NULL, ADD provinceDesc VARCHAR(100) DEFAULT NULL, DROP province_code, DROP province_desc, DROP region_code, DROP PRIMARY KEY, ADD PRIMARY KEY (provinceCode)');
        $this->addSql('CREATE INDEX provinceCode ON lookup_province (provinceCode)');
        $this->addSql('CREATE INDEX regionCode ON lookup_province (regionCode)');
        $this->addSql('ALTER TABLE lookup_region ADD regionCode INT AUTO_INCREMENT NOT NULL, ADD regionDesc VARCHAR(100) DEFAULT NULL, ADD ocatRegion VARCHAR(10) DEFAULT NULL, DROP region_code, DROP region_desc, DROP PRIMARY KEY, ADD PRIMARY KEY (regionCode)');
        $this->addSql('CREATE INDEX regionCode ON lookup_region (regionCode)');
    }
}
