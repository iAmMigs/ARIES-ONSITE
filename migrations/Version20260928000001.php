<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add tier hierarchy, management permissions, and audit timestamps to admin_users.
 * Sets admin@feualabang.edu.ph and admin@feudiliman.edu.ph as default Master Admins.
 */
final class Version20260928000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tier, can_manage_admins, is_active, created_at, updated_at to admin_users, and set default Master Admins';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('admin_users');

        if (!$table->hasColumn('tier')) {
            $this->addSql("ALTER TABLE admin_users ADD tier VARCHAR(30) NOT NULL DEFAULT 'staff_admin'");
        }

        if (!$table->hasColumn('can_manage_admins')) {
            $this->addSql("ALTER TABLE admin_users ADD can_manage_admins TINYINT(1) NOT NULL DEFAULT 0");
        }

        if (!$table->hasColumn('is_active')) {
            $this->addSql("ALTER TABLE admin_users ADD is_active TINYINT(1) NOT NULL DEFAULT 1");
        }

        if (!$table->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE admin_users ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        }

        if (!$table->hasColumn('updated_at')) {
            $this->addSql("ALTER TABLE admin_users ADD updated_at DATETIME DEFAULT NULL");
        }

        // Idempotent seeding for default Master Admins per campus
        $this->addSql("UPDATE admin_users SET tier = 'master_admin', can_manage_admins = 1 WHERE email = 'admin@feualabang.edu.ph'");
        $this->addSql("UPDATE admin_users SET tier = 'master_admin', can_manage_admins = 1 WHERE email = 'admin@feudiliman.edu.ph'");
        $this->addSql("UPDATE admin_users SET tier = 'staff_admin', can_manage_admins = 0 WHERE email NOT IN ('admin@feualabang.edu.ph', 'admin@feudiliman.edu.ph') AND (tier IS NULL OR tier = '' OR tier = 'staff_admin')");
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('admin_users');

        if ($table->hasColumn('tier')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN tier");
        }
        if ($table->hasColumn('can_manage_admins')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN can_manage_admins");
        }
        if ($table->hasColumn('is_active')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN is_active");
        }
        if ($table->hasColumn('created_at')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN created_at");
        }
        if ($table->hasColumn('updated_at')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN updated_at");
        }
    }
}
