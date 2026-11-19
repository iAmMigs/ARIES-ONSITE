<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to convert admin_users.emp_num to INT UNSIGNED and remove AUTO_INCREMENT.
 */
final class Version20260929000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert admin_users.emp_num to INT UNSIGNED and remove AUTO_INCREMENT for manual employee number input';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('admin_users')) {
            $table = $schema->getTable('admin_users');
            if ($table->hasColumn('emp_num')) {
                $this->addSql("ALTER TABLE admin_users MODIFY emp_num INT UNSIGNED NOT NULL");
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('admin_users')) {
            $table = $schema->getTable('admin_users');
            if ($table->hasColumn('emp_num')) {
                $this->addSql("ALTER TABLE admin_users MODIFY emp_num INT NOT NULL AUTO_INCREMENT");
            }
        }
    }
}
