<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427173041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds examination_date to audit_bed_applicants and recreates triggers for bed_applicants';
    }

    public function up(Schema $schema): void
    {
        // 1. Add the missing column to audit table
        $this->addSql('ALTER TABLE audit_bed_applicants ADD examination_date DATE DEFAULT NULL');

        // 2. Recreate triggers for bed_applicants
        $table = 'bed_applicants';
        $cols = 'student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, examination_date, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, passport_number, visa_type, visa_status, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date';
        
        $newCols = implode(', ', array_map(fn($c) => 'NEW.' . trim($c), explode(',', $cols)));
        $oldCols = implode(', ', array_map(fn($c) => 'OLD.' . trim($c), explode(',', $cols)));

        $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_delete");

        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

        $this->addSql("CREATE TRIGGER {$table}_after_insert AFTER INSERT ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$cols}) VALUES ({$auditValsInsert}, {$newCols}); END");
        $this->addSql("CREATE TRIGGER {$table}_after_update AFTER UPDATE ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$cols}) VALUES ({$auditValsUpdate}, {$newCols}); END");
        $this->addSql("CREATE TRIGGER {$table}_after_delete AFTER DELETE ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$cols}) VALUES ({$auditValsDelete}, {$oldCols}); END");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_delete");
        $this->addSql('ALTER TABLE audit_bed_applicants DROP examination_date');
    }
}
