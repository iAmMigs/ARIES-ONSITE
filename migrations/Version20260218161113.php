<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds Database Triggers for comprehensive Audit Trails (Backdoor protection)';
    }

    public function up(Schema $schema): void
    {
        // --- 1. AUDIT TRIGGERS FOR BED_APPLICANTS ---
        $this->createTriggers('bed_applicants', 'audit_bed_applicants', [
            'student_number', 'campus', 'created_at', 'education_type', 'grade_level', 
            'track_strand', 'lrn', 'admission_status', 'school_year_of_entry', 'enrollment_step', 
            'admission_type', 'examination_score', 'last_name', 'first_name', 'middle_name', 
            'extension_name', 'birth_date', 'birth_place', 'birth_country', 'gender', 
            'religion', 'citizenship', 'indigenous_group', 'mobile_number', 'land_line_number', 
            'personal_email', 'current_region', 'current_province', 'current_city', 
            'current_barangay', 'current_address', 'current_zip', 'permanent_region', 
            'permanent_province', 'permanent_city', 'permanent_barangay', 'permanent_address', 
            'permanent_zip', 'visa_type', 'passport_number', 'photo_slug', 'admission_date'
        ]);

        // --- 2. AUDIT TRIGGERS FOR BED_GUARDIANS ---
        // Mapping: guardian_id -> original_guardian_id
        $this->createTriggers('bed_guardians', 'audit_bed_guardians', [
            'student_number', 'Relationship', 'ParentName', 'Occupation', 
            'ContactNo', 'IsDeceased', 'IsOFW'
        ], ['guardian_id' => 'original_guardian_id']);

        // --- 3. AUDIT TRIGGERS FOR BED_SIBLINGS ---
        $this->createTriggers('bed_siblings', 'audit_bed_siblings', [
            'student_number', 'SiblingName', 'School', 'IsFeuStudent', 'FeuStudentNo'
        ]);

        // --- 4. AUDIT TRIGGERS FOR BED_SCHOOLS ---
        $this->createTriggers('bed_schools', 'audit_bed_schools', [
            'student_number', 'Level', 'School', 'YearEnd'
        ]);

        // --- 5. AUDIT TRIGGERS FOR BED_REQUIREMENTS ---
        $this->createTriggers('bed_requirements', 'audit_bed_requirements', [
            'student_number', 'Slug', 'Requirement', 'StoredFileName', 'Status', 'IsRequired', 'DateSubmitted'
        ]);
    }

    public function down(Schema $schema): void
    {
        $tables = ['bed_applicants', 'bed_guardians', 'bed_siblings', 'bed_schools', 'bed_requirements'];
        foreach ($tables as $table) {
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_audit_insert");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_audit_update");
            $this->addSql("DROP TRIGGER IF EXISTS {$table}_audit_delete");
        }
    }

    /**
     * Helper to generate Insert, Update, and Delete triggers
     */
    private function createTriggers(string $sourceTable, string $auditTable, array $columns, array $mappingOverrides = []): void
    {
        // Prepare Column Lists
        $targetCols = [];
        $newValues = [];
        $oldValues = [];

        // Handle specific ID mappings (e.g. guardian_id -> original_guardian_id)
        foreach ($mappingOverrides as $sourceCol => $targetCol) {
            $targetCols[] = "`$targetCol`";
            $newValues[] = "NEW.`$sourceCol`";
            $oldValues[] = "OLD.`$sourceCol`";
        }

        // Handle standard columns
        foreach ($columns as $col) {
            $targetCols[] = "`$col`";
            $newValues[] = "NEW.`$col`";
            $oldValues[] = "OLD.`$col`";
        }

        $targetColsStr = implode(', ', $targetCols);
        $newValuesStr = implode(', ', $newValues);
        $oldValuesStr = implode(', ', $oldValues);

        // Common Audit Columns
        $auditCols = "audit_action, emp_num, audit_datetime, host, remarks";
        
        // Logic: Try to get App User variable (@app_user), fallback to DB USER()
        $userSql = "COALESCE(@app_user, USER())"; 
        $hostSql = "COALESCE(@app_ip, 'Backdoor/Local')";

        // --- INSERT TRIGGER ---
        $this->addSql("DROP TRIGGER IF EXISTS {$sourceTable}_audit_insert");
        $this->addSql("
            CREATE TRIGGER {$sourceTable}_audit_insert
            AFTER INSERT ON `$sourceTable`
            FOR EACH ROW
            BEGIN
                INSERT INTO `$auditTable` ($targetColsStr, $auditCols)
                VALUES ($newValuesStr, 'INSERT', $userSql, NOW(), $hostSql, 'Record Created');
            END
        ");

        // --- UPDATE TRIGGER ---
        $this->addSql("DROP TRIGGER IF EXISTS {$sourceTable}_audit_update");
        $this->addSql("
            CREATE TRIGGER {$sourceTable}_audit_update
            AFTER UPDATE ON `$sourceTable`
            FOR EACH ROW
            BEGIN
                INSERT INTO `$auditTable` ($targetColsStr, $auditCols)
                VALUES ($newValuesStr, 'UPDATE', $userSql, NOW(), $hostSql, 'Record Updated');
            END
        ");

        // --- DELETE TRIGGER ---
        $this->addSql("DROP TRIGGER IF EXISTS {$sourceTable}_audit_delete");
        $this->addSql("
            CREATE TRIGGER {$sourceTable}_audit_delete
            BEFORE DELETE ON `$sourceTable`
            FOR EACH ROW
            BEGIN
                INSERT INTO `$auditTable` ($targetColsStr, $auditCols)
                VALUES ($oldValuesStr, 'DELETE', $userSql, NOW(), $hostSql, 'Record Deleted');
            END
        ");
    }
}