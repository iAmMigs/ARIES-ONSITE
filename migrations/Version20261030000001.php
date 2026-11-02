<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261030000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds modules column to admin_users and creates audit_admin_users, audit_school_year, audit_document_setup tables';
    }

    public function up(Schema $schema): void
    {
        // 1. Ensure modules column on admin_users
        if ($schema->hasTable('admin_users') && !$schema->getTable('admin_users')->hasColumn('modules')) {
            $this->addSql("ALTER TABLE admin_users ADD modules LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)'");
        }

        // 2. Create audit_admin_users table
        if (!$schema->hasTable('audit_admin_users')) {
            $this->addSql("
                CREATE TABLE IF NOT EXISTS audit_admin_users (
                    audit_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                    audit_action VARCHAR(20) NOT NULL,
                    emp_num VARCHAR(50) DEFAULT NULL,
                    audit_date_time DATETIME NOT NULL,
                    host VARCHAR(50) DEFAULT NULL,
                    remarks LONGTEXT DEFAULT NULL,
                    target_emp_num INT UNSIGNED NOT NULL,
                    email VARCHAR(180) DEFAULT NULL,
                    roles LONGTEXT DEFAULT NULL,
                    first_name VARCHAR(100) DEFAULT NULL,
                    last_name VARCHAR(100) DEFAULT NULL,
                    campus VARCHAR(50) DEFAULT NULL,
                    tier VARCHAR(30) DEFAULT NULL,
                    old_modules LONGTEXT DEFAULT NULL,
                    new_modules LONGTEXT DEFAULT NULL,
                    can_manage_admins TINYINT(1) DEFAULT NULL,
                    old_is_active TINYINT(1) DEFAULT NULL,
                    is_active TINYINT(1) DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // 3. Create audit_school_year table
        if (!$schema->hasTable('audit_school_year')) {
            $this->addSql("
                CREATE TABLE IF NOT EXISTS audit_school_year (
                    audit_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                    audit_action VARCHAR(20) NOT NULL,
                    emp_num VARCHAR(50) DEFAULT NULL,
                    audit_date_time DATETIME NOT NULL,
                    host VARCHAR(50) DEFAULT NULL,
                    remarks LONGTEXT DEFAULT NULL,
                    school_year_id INT NOT NULL,
                    label VARCHAR(10) DEFAULT NULL,
                    year_start INT DEFAULT NULL,
                    year_end INT DEFAULT NULL,
                    campus VARCHAR(10) DEFAULT NULL,
                    old_is_active TINYINT(4) DEFAULT NULL,
                    is_active TINYINT(4) DEFAULT NULL,
                    old_enrollment_open TINYINT(4) DEFAULT NULL,
                    enrollment_open TINYINT(4) DEFAULT NULL,
                    old_promissory_deadline DATE DEFAULT NULL,
                    promissory_deadline DATE DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // 4. Create audit_document_setup table
        if (!$schema->hasTable('audit_document_setup')) {
            $this->addSql("
                CREATE TABLE IF NOT EXISTS audit_document_setup (
                    audit_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                    audit_action VARCHAR(20) NOT NULL,
                    emp_num VARCHAR(50) DEFAULT NULL,
                    audit_date_time DATETIME NOT NULL,
                    host VARCHAR(50) DEFAULT NULL,
                    remarks LONGTEXT DEFAULT NULL,
                    document_setup_id INT NOT NULL,
                    document_name VARCHAR(255) DEFAULT NULL,
                    slug VARCHAR(100) DEFAULT NULL,
                    folder_name VARCHAR(100) DEFAULT NULL,
                    campus VARCHAR(50) DEFAULT NULL,
                    allowed_file_types VARCHAR(255) DEFAULT NULL,
                    student_type VARCHAR(20) DEFAULT NULL,
                    nationality_type VARCHAR(20) DEFAULT NULL,
                    grade_levels LONGTEXT DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS audit_document_setup");
        $this->addSql("DROP TABLE IF EXISTS audit_school_year");
        $this->addSql("DROP TABLE IF EXISTS audit_admin_users");
        if ($schema->hasTable('admin_users') && $schema->getTable('admin_users')->hasColumn('modules')) {
            $this->addSql("ALTER TABLE admin_users DROP COLUMN modules");
        }
    }
}

