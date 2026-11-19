<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to make date_issued and expiration_date nullable in bed_passports table.
 */
final class Version20260929000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make date_issued and expiration_date nullable in bed_passports table for optional passport registration';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('bed_passports')) {
            $table = $schema->getTable('bed_passports');
            if ($table->hasColumn('date_issued')) {
                $this->addSql("ALTER TABLE bed_passports MODIFY date_issued DATE DEFAULT NULL");
            }
            if ($table->hasColumn('expiration_date')) {
                $this->addSql("ALTER TABLE bed_passports MODIFY expiration_date DATE DEFAULT NULL");
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('bed_passports')) {
            $table = $schema->getTable('bed_passports');
            if ($table->hasColumn('date_issued')) {
                $this->addSql("ALTER TABLE bed_passports MODIFY date_issued DATE NOT NULL");
            }
            if ($table->hasColumn('expiration_date')) {
                $this->addSql("ALTER TABLE bed_passports MODIFY expiration_date DATE NOT NULL");
            }
        }
    }
}
