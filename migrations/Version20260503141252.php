<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503141252 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // --- SYNCHRONIZE AUDIT TABLES ---
        
        // Guardians
        $this->addSql('ALTER TABLE audit_bed_guardians ADD guardian_type VARCHAR(20) DEFAULT NULL, ADD ofw_country VARCHAR(100) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD address TEXT DEFAULT NULL');
        
        // Schools
        $this->addSql('ALTER TABLE audit_bed_schools ADD is_international TINYINT DEFAULT 0 NOT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD region VARCHAR(100) DEFAULT NULL, ADD province VARCHAR(100) DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL');
        
        // Requirements
        $this->addSql('ALTER TABLE audit_bed_requirements ADD is_deleted TINYINT DEFAULT 0 NOT NULL, DROP IsRequired');

        // --- UPDATE TRIGGERS ---
        
        $tables = [
            'bed_applicants' => [
                'target' => 'student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date, passport_number, visa_type, visa_status, school_type, examination_date, documents_agreed_date',
                'source' => 'student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date, passport_number, visa_type, visa_status, school_type, examination_date, documents_agreed_date'
            ],
            'bed_guardians' => [
                'target' => 'original_guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW, guardian_type, ofw_country, email, address',
                'source' => 'guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW, guardian_type, OfwCountry, Email, Address'
            ],
            'bed_siblings' => [
                'target' => 'student_number, SiblingName, School, IsFeuStudent, FeuStudentNo',
                'source' => 'student_number, SiblingName, School, IsFeuStudent, FeuStudentNo'
            ],
            'bed_schools' => [
                'target' => 'student_number, Level, School, SchoolYear, SchoolType, is_international, country, region, province, city',
                'source' => 'student_number, Level, School, SchoolYear, SchoolType, is_international, country, region, province, city'
            ],
            'bed_requirements' => [
                'target' => 'student_number, Slug, Requirement, StoredFileName, Status, DateSubmitted, is_deleted',
                'source' => 'student_number, Slug, Requirement, StoredFileName, Status, DateSubmitted, IsDeleted'
            ]
        ];

        foreach ($tables as $table => $cols) {
            $targetCols = $cols['target'];
            $newCols = implode(', ', array_map(fn($c) => 'NEW.' . trim($c), explode(',', $cols['source'])));
            $oldCols = implode(', ', array_map(fn($c) => 'OLD.' . trim($c), explode(',', $cols['source'])));

            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_insert");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_update");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_delete");

            $auditCols = "audit_action, audit_date_time, emp_num, remarks";
            $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";
            $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";
            $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";

            $this->addSql("
                CREATE TRIGGER {$table}_after_insert AFTER INSERT ON {$table} FOR EACH ROW
                BEGIN
                    INSERT INTO audit_{$table} ({$auditCols}, {$targetCols})
                    VALUES ({$auditValsInsert}, {$newCols});
                END
            ");
            
            $this->addSql("
                CREATE TRIGGER {$table}_after_update AFTER UPDATE ON {$table} FOR EACH ROW
                BEGIN
                    INSERT INTO audit_{$table} ({$auditCols}, {$targetCols})
                    VALUES ({$auditValsUpdate}, {$newCols});
                END
            ");

            $this->addSql("
                CREATE TRIGGER {$table}_after_delete AFTER DELETE ON {$table} FOR EACH ROW
                BEGIN
                    INSERT INTO audit_{$table} ({$auditCols}, {$targetCols})
                    VALUES ({$auditValsDelete}, {$oldCols});
                END
            ");
        }
    }

    public function down(Schema $schema): void
    {
        $tables = ['bed_applicants', 'bed_guardians', 'bed_siblings', 'bed_schools', 'bed_requirements'];
        foreach ($tables as $table) {
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_insert");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_update");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_after_delete");
        }
    }
}
