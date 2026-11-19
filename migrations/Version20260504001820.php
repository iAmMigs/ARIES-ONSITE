<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504001820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE bed_applicants SET citizenship = \'LOCAL\' WHERE UPPER(citizenship) = \'FILIPINO\'');
        $this->addSql('UPDATE bed_applicants SET citizenship = \'INTERNATIONAL\' WHERE UPPER(citizenship) != \'LOCAL\' AND citizenship IS NOT NULL');
        $this->addSql('ALTER TABLE bed_applicants DROP is_naturalized');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bed_applicants ADD is_naturalized TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE bed_applicants SET citizenship = \'FILIPINO\' WHERE citizenship = \'LOCAL\'');
        $this->addSql('UPDATE bed_applicants SET citizenship = \'FOREIGN\' WHERE citizenship = \'INTERNATIONAL\'');
    }
}
