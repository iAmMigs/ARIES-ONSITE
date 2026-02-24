<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration
 */
final class Version20260220004749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds passport/visa columns and recreates audit triggers with USER() host logic';
    }

    public function up(Schema $schema): void
    {
        // 1. Execute the Doctrine-generated column additions
        $this->addSql('ALTER TABLE audit_bed_applicants ADD passport_number VARCHAR(50) DEFAULT NULL, ADD visa_type VARCHAR(50) DEFAULT NULL, ADD visa_status VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE bed_applicants ADD passport_number VARCHAR(50) DEFAULT NULL, ADD visa_type VARCHAR(50) DEFAULT NULL, ADD visa_status VARCHAR(50) DEFAULT NULL');

        // 2. Define tables mapping to recreate the triggers
        $tables = [
            'bed_applicants' => [
                'target' => 'student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, passport_number, visa_type, visa_status, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date',
                'source' => 'student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, passport_number, visa_type, visa_status, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date'
            ],
            'bed_guardians' => [
                'target' => 'original_guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW',
                'source' => 'guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW'
            ],
            'bed_siblings' => [
                'target' => 'student_number, SiblingName, School, IsFeuStudent, FeuStudentNo',
                'source' => 'student_number, SiblingName, School, IsFeuStudent, FeuStudentNo'
            ],
            'bed_schools' => [
                'target' => 'student_number, Level, School, YearEnd',
                'source' => 'student_number, Level, School, YearEnd'
            ],
            'bed_requirements' => [
                'target' => 'student_number, Slug, Requirement, StoredFileName, Status, IsRequired, DateSubmitted',
                'source' => 'student_number, Slug, Requirement, StoredFileName, Status, IsRequired, DateSubmitted'
            ]
        ];

        // 3. Loop through to generate Triggers
        foreach ($tables as $table => $cols) {
            $targetCols = $cols['target'];
            $newCols = implode(', ', array_map(fn($c) => 'NEW.' . trim($c), explode(',', $cols['source'])));
            $oldCols = implode(', ', array_map(fn($c) => 'OLD.' . trim($c), explode(',', $cols['source'])));

            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_insert");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_update");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_delete");

            $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
            $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
            $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
            $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

            $this->addSql("CREATE TRIGGER {$table}_after_insert AFTER INSERT ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$targetCols}) VALUES ({$auditValsInsert}, {$newCols}); END");
            $this->addSql("CREATE TRIGGER {$table}_after_update AFTER UPDATE ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$targetCols}) VALUES ({$auditValsUpdate}, {$newCols}); END");
            $this->addSql("CREATE TRIGGER {$table}_after_delete AFTER DELETE ON {$table} FOR EACH ROW BEGIN INSERT INTO audit_{$table} ({$auditCols}, {$targetCols}) VALUES ({$auditValsDelete}, {$oldCols}); END");
        }
    }

    public function down(Schema $schema): void
    {
        // Drop existing triggers
        $tables = ['bed_applicants', 'bed_guardians', 'bed_siblings', 'bed_schools', 'bed_requirements'];
        foreach ($tables as $table) {
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_insert");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_update");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_delete");
        }

        // Drop the columns as intended
        $this->addSql('ALTER TABLE audit_bed_applicants DROP passport_number, DROP visa_type, DROP visa_status');
        $this->addSql('ALTER TABLE bed_applicants DROP passport_number, DROP visa_type, DROP visa_status');
    }
}
