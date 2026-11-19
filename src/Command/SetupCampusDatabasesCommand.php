<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-campus-databases',
    description: 'Provisions aries_diliman_db and aries_alabang_db, sets up audit tables/triggers, and partitions data.',
)]
class SetupCampusDatabasesCommand extends Command
{
    private const DILIMAN_DB = 'aries_diliman_db';
    private const ALABANG_DB = 'aries_alabang_db';
    private const SOURCE_DB = 'aries_db';

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->em->getConnection();

        $io->title('Provisioning Campus Databases (Diliman & Alabang)');

        // 1. Create databases if not exists
        $io->section('1. Ensuring databases exist...');
        $conn->executeStatement(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', self::DILIMAN_DB));
        $conn->executeStatement(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', self::ALABANG_DB));
        $io->success('Databases aries_diliman_db and aries_alabang_db are ready.');

        // 2. Clone table schemas from aries_db
        $io->section('2. Cloning table schemas from ' . self::SOURCE_DB . '...');
        $tables = $conn->fetchFirstColumn(sprintf(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = '%s' AND table_type = 'BASE TABLE'",
            self::SOURCE_DB
        ));

        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $targetDb) {
            $io->text("Setting up tables in $targetDb...");
            foreach ($tables as $table) {
                $row = $conn->fetchNumeric(sprintf('SHOW CREATE TABLE `%s`.`%s`', self::SOURCE_DB, $table));
                $createSql = $row[1] ?? null;
                if ($createSql) {
                    $createSql = preg_replace('/^CREATE TABLE/i', 'CREATE TABLE IF NOT EXISTS', $createSql);
                    $conn->executeStatement(sprintf('USE `%s`', $targetDb));
                    $conn->executeStatement($createSql);
                }
            }
        }
        $io->success('Base table structures cloned.');

        // 3. Ensure modules column exists on admin_users in both databases
        $io->section('3. Ensuring modules column on admin_users...');
        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $db) {
            $columnExists = (int) $conn->fetchOne(sprintf(
                "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '%s' AND table_name = 'admin_users' AND column_name = 'modules'",
                $db
            ));
            if ($columnExists === 0) {
                $conn->executeStatement(sprintf(
                    'ALTER TABLE `%s`.`admin_users` ADD COLUMN `modules` LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\'',
                    $db
                ));
                $io->text("Added modules column to $db.admin_users");
            }
        }

        // 4. Ensure new audit tables exist
        $io->section('4. Creating extended audit tables...');
        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $db) {
            $conn->executeStatement(sprintf('USE `%s`', $db));

            // audit_admin_users
            $conn->executeStatement('
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
            ');

            // audit_school_year
            $conn->executeStatement('
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
            ');

            // audit_document_setup
            $conn->executeStatement('
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
            ');
        }
        $io->success('Extended audit tables created in all databases.');

        // 5. Clone existing triggers from aries_db into target databases
        $io->section('5. Replicating triggers...');
        $triggerNames = $conn->fetchFirstColumn(sprintf(
            "SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema = '%s'",
            self::SOURCE_DB
        ));

        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $targetDb) {
            $conn->executeStatement(sprintf('USE `%s`', $targetDb));
            foreach ($triggerNames as $trg) {
                $trgRow = $conn->fetchNumeric(sprintf('SHOW CREATE TRIGGER `%s`.`%s`', self::SOURCE_DB, $trg));
                $createTrg = $trgRow[2] ?? null;
                if ($createTrg) {
                    $conn->executeStatement(sprintf('DROP TRIGGER IF EXISTS `%s`', $trg));
                    $conn->executeStatement($createTrg);
                }
            }
        }

        // 6. Create new triggers for admin_users, school_year, and document_setup
        $io->section('6. Installing new audit triggers for Admin Access, School Year, and Document Setup...');
        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $db) {
            $conn->executeStatement(sprintf('USE `%s`', $db));

            // Triggers for admin_users
            $conn->executeStatement('DROP TRIGGER IF EXISTS admin_users_after_insert');
            $conn->executeStatement("
                CREATE TRIGGER admin_users_after_insert AFTER INSERT ON admin_users
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_admin_users (
                        audit_action, emp_num, audit_date_time, host, remarks, target_emp_num, email, roles, first_name, last_name, campus, tier, old_modules, new_modules, can_manage_admins, old_is_active, is_active, created_at, updated_at
                    ) VALUES (
                        'INSERT', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.emp_num, NEW.email, NEW.roles, NEW.first_name, NEW.last_name, NEW.campus, NEW.tier, NULL, NEW.modules, NEW.can_manage_admins, NULL, NEW.is_active, NEW.created_at, NEW.updated_at
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS admin_users_after_update');
            $conn->executeStatement("
                CREATE TRIGGER admin_users_after_update AFTER UPDATE ON admin_users
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_admin_users (
                        audit_action, emp_num, audit_date_time, host, remarks, target_emp_num, email, roles, first_name, last_name, campus, tier, old_modules, new_modules, can_manage_admins, old_is_active, is_active, created_at, updated_at
                    ) VALUES (
                        'UPDATE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.emp_num, NEW.email, NEW.roles, NEW.first_name, NEW.last_name, NEW.campus, NEW.tier, OLD.modules, NEW.modules, NEW.can_manage_admins, OLD.is_active, NEW.is_active, NEW.created_at, NEW.updated_at
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS admin_users_after_delete');
            $conn->executeStatement("
                CREATE TRIGGER admin_users_after_delete AFTER DELETE ON admin_users
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_admin_users (
                        audit_action, emp_num, audit_date_time, host, remarks, target_emp_num, email, roles, first_name, last_name, campus, tier, old_modules, new_modules, can_manage_admins, old_is_active, is_active, created_at, updated_at
                    ) VALUES (
                        'DELETE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, OLD.emp_num, OLD.email, OLD.roles, OLD.first_name, OLD.last_name, OLD.campus, OLD.tier, OLD.modules, NULL, OLD.can_manage_admins, OLD.is_active, NULL, OLD.created_at, OLD.updated_at
                    );
                END
            ");

            // Triggers for school_year
            $conn->executeStatement('DROP TRIGGER IF EXISTS school_year_after_insert');
            $conn->executeStatement("
                CREATE TRIGGER school_year_after_insert AFTER INSERT ON school_year
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_school_year (
                        audit_action, emp_num, audit_date_time, host, remarks, school_year_id, label, year_start, year_end, campus, old_is_active, is_active, old_enrollment_open, enrollment_open, old_promissory_deadline, promissory_deadline, created_at
                    ) VALUES (
                        'INSERT', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.id, NEW.label, NEW.year_start, NEW.year_end, NEW.campus, NULL, NEW.is_active, NULL, NEW.enrollment_open, NULL, NEW.promissory_deadline, NEW.created_at
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS school_year_after_update');
            $conn->executeStatement("
                CREATE TRIGGER school_year_after_update AFTER UPDATE ON school_year
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_school_year (
                        audit_action, emp_num, audit_date_time, host, remarks, school_year_id, label, year_start, year_end, campus, old_is_active, is_active, old_enrollment_open, enrollment_open, old_promissory_deadline, promissory_deadline, created_at
                    ) VALUES (
                        'UPDATE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.id, NEW.label, NEW.year_start, NEW.year_end, NEW.campus, OLD.is_active, NEW.is_active, OLD.enrollment_open, NEW.enrollment_open, OLD.promissory_deadline, NEW.promissory_deadline, NEW.created_at
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS school_year_after_delete');
            $conn->executeStatement("
                CREATE TRIGGER school_year_after_delete AFTER DELETE ON school_year
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_school_year (
                        audit_action, emp_num, audit_date_time, host, remarks, school_year_id, label, year_start, year_end, campus, old_is_active, is_active, old_enrollment_open, enrollment_open, old_promissory_deadline, promissory_deadline, created_at
                    ) VALUES (
                        'DELETE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, OLD.id, OLD.label, OLD.year_start, OLD.year_end, OLD.campus, OLD.is_active, NULL, OLD.enrollment_open, NULL, OLD.promissory_deadline, NULL, OLD.created_at
                    );
                END
            ");

            // Triggers for document_setup
            $conn->executeStatement('DROP TRIGGER IF EXISTS document_setup_after_insert');
            $conn->executeStatement("
                CREATE TRIGGER document_setup_after_insert AFTER INSERT ON document_setup
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_document_setup (
                        audit_action, emp_num, audit_date_time, host, remarks, document_setup_id, document_name, slug, folder_name, campus, allowed_file_types, student_type, nationality_type, grade_levels
                    ) VALUES (
                        'INSERT', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.id, NEW.document_name, NEW.slug, NEW.folder_name, NEW.campus, NEW.allowed_file_types, NEW.student_type, NEW.nationality_type, NEW.grade_levels
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS document_setup_after_update');
            $conn->executeStatement("
                CREATE TRIGGER document_setup_after_update AFTER UPDATE ON document_setup
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_document_setup (
                        audit_action, emp_num, audit_date_time, host, remarks, document_setup_id, document_name, slug, folder_name, campus, allowed_file_types, student_type, nationality_type, grade_levels
                    ) VALUES (
                        'UPDATE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, NEW.id, NEW.document_name, NEW.slug, NEW.folder_name, NEW.campus, NEW.allowed_file_types, NEW.student_type, NEW.nationality_type, NEW.grade_levels
                    );
                END
            ");

            $conn->executeStatement('DROP TRIGGER IF EXISTS document_setup_after_delete');
            $conn->executeStatement("
                CREATE TRIGGER document_setup_after_delete AFTER DELETE ON document_setup
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_document_setup (
                        audit_action, emp_num, audit_date_time, host, remarks, document_setup_id, document_name, slug, folder_name, campus, allowed_file_types, student_type, nationality_type, grade_levels
                    ) VALUES (
                        'DELETE', @app_user_emp_num, NOW(), SUBSTRING_INDEX(USER(), '@', -1), CASE WHEN @app_user_emp_num IS NULL THEN 'BACKDOOR' ELSE NULL END, OLD.id, OLD.document_name, OLD.slug, OLD.folder_name, OLD.campus, OLD.allowed_file_types, OLD.student_type, OLD.nationality_type, OLD.grade_levels
                    );
                END
            ");
        }
        $io->success('All audit triggers installed.');

        // 7. Copy lookup and shared tables
        $io->section('7. Syncing lookup tables to Diliman and Alabang...');
        $lookupTables = [
            'lookup_region',
            'lookup_province',
            'lookup_city',
            'lookup_barangay',
            'lookup_country',
            'sf_lookup_religion',
            'tblcitizenship',
            'doctrine_migration_versions',
        ];

        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $targetDb) {
            foreach ($lookupTables as $tbl) {
                $count = (int) $conn->fetchOne(sprintf('SELECT COUNT(*) FROM `%s`.`%s`', $targetDb, $tbl));
                if ($count === 0) {
                    $conn->executeStatement(sprintf('INSERT INTO `%s`.`%s` SELECT * FROM `%s`.`%s`', $targetDb, $tbl, self::SOURCE_DB, $tbl));
                    $io->text("Copied $tbl into $targetDb");
                }
            }
        }
        $io->success('Lookup tables synced.');

        // 8. Partition campus-specific data
        $io->section('8. Partitioning data into campus databases...');

        // Diliman Partitioning
        $dilimanCount = (int) $conn->fetchOne(sprintf("SELECT COUNT(*) FROM `%s`.`bed_applicants`", self::DILIMAN_DB));
        if ($dilimanCount === 0) {
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`school_year` SELECT * FROM `%s`.`school_year` WHERE campus = 'FDIL'", self::DILIMAN_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`document_setup` SELECT * FROM `%s`.`document_setup` WHERE campus = 'FDIL'", self::DILIMAN_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`admin_users` SELECT * FROM `%s`.`admin_users` WHERE campus = 'feu_diliman'", self::DILIMAN_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_applicants` SELECT * FROM `%s`.`bed_applicants` WHERE campus = 'FDIL'", self::DILIMAN_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_guardians` SELECT * FROM `%s`.`bed_guardians` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FDIL')", self::DILIMAN_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_passports` SELECT * FROM `%s`.`bed_passports` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FDIL')", self::DILIMAN_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_requirements` SELECT * FROM `%s`.`bed_requirements` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FDIL')", self::DILIMAN_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_schools` SELECT * FROM `%s`.`bed_schools` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FDIL')", self::DILIMAN_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_siblings` SELECT * FROM `%s`.`bed_siblings` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FDIL')", self::DILIMAN_DB, self::SOURCE_DB, self::SOURCE_DB));
            $io->text('Diliman data partitioned into ' . self::DILIMAN_DB);
        }

        // Alabang Partitioning
        $alabangCount = (int) $conn->fetchOne(sprintf("SELECT COUNT(*) FROM `%s`.`bed_applicants`", self::ALABANG_DB));
        if ($alabangCount === 0) {
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`school_year` SELECT * FROM `%s`.`school_year` WHERE campus = 'FALAB'", self::ALABANG_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`document_setup` SELECT * FROM `%s`.`document_setup` WHERE campus = 'FALAB'", self::ALABANG_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`admin_users` SELECT * FROM `%s`.`admin_users` WHERE campus = 'feu_alabang'", self::ALABANG_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_applicants` SELECT * FROM `%s`.`bed_applicants` WHERE campus = 'FALAB'", self::ALABANG_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_guardians` SELECT * FROM `%s`.`bed_guardians` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FALAB')", self::ALABANG_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_passports` SELECT * FROM `%s`.`bed_passports` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FALAB')", self::ALABANG_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_requirements` SELECT * FROM `%s`.`bed_requirements` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FALAB')", self::ALABANG_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_schools` SELECT * FROM `%s`.`bed_schools` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FALAB')", self::ALABANG_DB, self::SOURCE_DB, self::SOURCE_DB));
            $conn->executeStatement(sprintf("INSERT INTO `%s`.`bed_siblings` SELECT * FROM `%s`.`bed_siblings` WHERE student_number IN (SELECT student_number FROM `%s`.`bed_applicants` WHERE campus = 'FALAB')", self::ALABANG_DB, self::SOURCE_DB, self::SOURCE_DB));
            $io->text('Alabang data partitioned into ' . self::ALABANG_DB);
        }

        // 9. Backfill master admin modules
        $allModulesJson = json_encode(['dashboard', 'registrations', 'documents', 'school_year', 'admin_access']);
        foreach ([self::DILIMAN_DB, self::ALABANG_DB] as $db) {
            $conn->executeStatement(sprintf(
                "UPDATE `%s`.`admin_users` SET `modules` = '%s' WHERE `tier` = 'master_admin' AND (`modules` IS NULL OR `modules` = '[]' OR `modules` = '')",
                $db,
                $allModulesJson
            ));
        }

        // 10. Provision 6-Database Architecture: aries_x_associates and aries_x_assoc_login
        $io->section('10. Provisioning Campus Associates & Assoc Login databases...');
        $databasesToCreate = [
            'aries_diliman_associates',
            'aries_alabang_associates',
            'aries_diliman_assoc_login',
            'aries_alabang_assoc_login',
        ];

        foreach ($databasesToCreate as $dbName) {
            $conn->executeStatement(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbName));
        }

        // Setup associates tables in aries_diliman_associates and aries_alabang_associates
        foreach (['aries_diliman_associates', 'aries_alabang_associates'] as $assocDb) {
            $conn->executeStatement(sprintf("
                CREATE TABLE IF NOT EXISTS `%s`.`associates` (
                    `emp_id` INT UNSIGNED NOT NULL PRIMARY KEY,
                    `last_name` VARCHAR(100) DEFAULT NULL,
                    `first_name` VARCHAR(100) DEFAULT NULL,
                    `middle_name` VARCHAR(100) DEFAULT NULL,
                    `title` VARCHAR(50) DEFAULT NULL,
                    `email` VARCHAR(180) DEFAULT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_associates_email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ", $assocDb));
        }

        // Setup assoc_login tables in aries_diliman_assoc_login and aries_alabang_assoc_login
        foreach (['aries_diliman_assoc_login', 'aries_alabang_assoc_login'] as $loginDb) {
            $conn->executeStatement(sprintf("
                CREATE TABLE IF NOT EXISTS `%s`.`assoc_login` (
                    `emp_id` INT UNSIGNED NOT NULL PRIMARY KEY,
                    `user_name` VARCHAR(100) NOT NULL UNIQUE,
                    `password` VARCHAR(255) NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_assoc_login_username` (`user_name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ", $loginDb));
        }

        $io->success('6-Database Architecture provisioned successfully.');

        $io->success('Campus databases aries_diliman_db and aries_alabang_db setup completed successfully!');
        return Command::SUCCESS;
    }
}
