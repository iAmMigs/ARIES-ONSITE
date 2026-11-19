<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modify general_average in bed_applicants and audit_bed_applicants to VARCHAR(50) to allow alphanumeric letter grades.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bed_applicants MODIFY general_average VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_applicants MODIFY general_average VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bed_applicants MODIFY general_average DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_applicants MODIFY general_average DOUBLE PRECISION DEFAULT NULL');
    }
}
