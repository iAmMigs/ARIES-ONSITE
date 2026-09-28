<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to drop civil_status from bed_applicants and audit_bed_applicants and recreate triggers without civil_status.
 */
final class Version20260929000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop civil_status from bed_applicants and audit_bed_applicants and recreate triggers without civil_status';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop existing triggers on bed_applicants
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_delete");

        // 2. Drop civil_status column from bed_applicants if it exists
        if ($schema->hasTable('bed_applicants')) {
            $table = $schema->getTable('bed_applicants');
            if ($table->hasColumn('civil_status')) {
                $this->addSql("ALTER TABLE bed_applicants DROP COLUMN civil_status");
            }
        }

        // 3. Drop civil_status column from audit_bed_applicants if it exists
        if ($schema->hasTable('audit_bed_applicants')) {
            $table = $schema->getTable('audit_bed_applicants');
            if ($table->hasColumn('civil_status')) {
                $this->addSql("ALTER TABLE audit_bed_applicants DROP COLUMN civil_status");
            }
        }

        // 4. Recreate triggers for bed_applicants without civil_status
        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

        $appCols = "student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date, passport_number, visa_type, visa_status, school_type, examination_date, documents_agreed_date, preferred_name, suffix, country_of_birth, country_of_residence, permanent_country, last_grade_completed, general_average";
        $appNew = "NEW.student_number, NEW.campus, NEW.created_at, NEW.education_type, NEW.grade_level, NEW.track_strand, NEW.lrn, NEW.admission_status, NEW.school_year_of_entry, NEW.admission_type, NEW.examination_score, NEW.last_name, NEW.first_name, NEW.middle_name, NEW.extension_name, NEW.birth_date, NEW.birth_place, NEW.gender, NEW.religion, NEW.citizenship, NEW.indigenous_group, NEW.mobile_number, NEW.land_line_number, NEW.personal_email, NEW.current_region, NEW.current_province, NEW.current_city, NEW.current_barangay, NEW.current_address, NEW.current_zip, NEW.permanent_region, NEW.permanent_province, NEW.permanent_city, NEW.permanent_barangay, NEW.permanent_address, NEW.permanent_zip, NEW.photo_slug, NEW.admission_date, NEW.passport_number, NEW.visa_type, NEW.visa_status, NEW.school_type, NEW.examination_date, NEW.documents_agreed_date, NEW.preferred_name, NEW.suffix, NEW.country_of_birth, NEW.country_of_residence, NEW.permanent_country, NEW.last_grade_completed, NEW.general_average";
        $appOld = "OLD.student_number, OLD.campus, OLD.created_at, OLD.education_type, OLD.grade_level, OLD.track_strand, OLD.lrn, OLD.admission_status, OLD.school_year_of_entry, OLD.admission_type, OLD.examination_score, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.extension_name, OLD.birth_date, OLD.birth_place, OLD.gender, OLD.religion, OLD.citizenship, OLD.indigenous_group, OLD.mobile_number, OLD.land_line_number, OLD.personal_email, OLD.current_region, OLD.current_province, OLD.current_city, OLD.current_barangay, OLD.current_address, OLD.current_zip, OLD.permanent_region, OLD.permanent_province, OLD.permanent_city, OLD.permanent_barangay, OLD.permanent_address, OLD.permanent_zip, OLD.photo_slug, OLD.admission_date, OLD.passport_number, OLD.visa_type, OLD.visa_status, OLD.school_type, OLD.examination_date, OLD.documents_agreed_date, OLD.preferred_name, OLD.suffix, OLD.country_of_birth, OLD.country_of_residence, OLD.permanent_country, OLD.last_grade_completed, OLD.general_average";

        $this->addSql("CREATE TRIGGER bed_applicants_after_insert AFTER INSERT ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsInsert}, {$appNew});
            END");
        $this->addSql("CREATE TRIGGER bed_applicants_after_update AFTER UPDATE ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsUpdate}, {$appNew});
            END");
        $this->addSql("CREATE TRIGGER bed_applicants_after_delete AFTER DELETE ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsDelete}, {$appOld});
            END");
    }

    public function down(Schema $schema): void
    {
        // 1. Drop triggers
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_delete");

        // 2. Add civil_status column back
        if ($schema->hasTable('bed_applicants')) {
            $this->addSql("ALTER TABLE bed_applicants ADD civil_status VARCHAR(50) DEFAULT NULL");
        }
        if ($schema->hasTable('audit_bed_applicants')) {
            $this->addSql("ALTER TABLE audit_bed_applicants ADD civil_status VARCHAR(50) DEFAULT NULL");
        }

        // 3. Recreate triggers with civil_status
        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

        $appCols = "student_number, campus, created_at, education_type, grade_level, track_strand, lrn, admission_status, school_year_of_entry, admission_type, examination_score, last_name, first_name, middle_name, extension_name, birth_date, birth_place, gender, religion, citizenship, indigenous_group, mobile_number, land_line_number, personal_email, current_region, current_province, current_city, current_barangay, current_address, current_zip, permanent_region, permanent_province, permanent_city, permanent_barangay, permanent_address, permanent_zip, photo_slug, admission_date, passport_number, visa_type, visa_status, school_type, examination_date, documents_agreed_date, preferred_name, suffix, country_of_birth, civil_status, country_of_residence, permanent_country, last_grade_completed, general_average";
        $appNew = "NEW.student_number, NEW.campus, NEW.created_at, NEW.education_type, NEW.grade_level, NEW.track_strand, NEW.lrn, NEW.admission_status, NEW.school_year_of_entry, NEW.admission_type, NEW.examination_score, NEW.last_name, NEW.first_name, NEW.middle_name, NEW.extension_name, NEW.birth_date, NEW.birth_place, NEW.gender, NEW.religion, NEW.citizenship, NEW.indigenous_group, NEW.mobile_number, NEW.land_line_number, NEW.personal_email, NEW.current_region, NEW.current_province, NEW.current_city, NEW.current_barangay, NEW.current_address, NEW.current_zip, NEW.permanent_region, NEW.permanent_province, NEW.permanent_city, NEW.permanent_barangay, NEW.permanent_address, NEW.permanent_zip, NEW.photo_slug, NEW.admission_date, NEW.passport_number, NEW.visa_type, NEW.visa_status, NEW.school_type, NEW.examination_date, NEW.documents_agreed_date, NEW.preferred_name, NEW.suffix, NEW.country_of_birth, NEW.civil_status, NEW.country_of_residence, NEW.permanent_country, NEW.last_grade_completed, NEW.general_average";
        $appOld = "OLD.student_number, OLD.campus, OLD.created_at, OLD.education_type, OLD.grade_level, OLD.track_strand, OLD.lrn, OLD.admission_status, OLD.school_year_of_entry, OLD.admission_type, OLD.examination_score, OLD.last_name, OLD.first_name, OLD.middle_name, OLD.extension_name, OLD.birth_date, OLD.birth_place, OLD.gender, OLD.religion, OLD.citizenship, OLD.indigenous_group, OLD.mobile_number, OLD.land_line_number, OLD.personal_email, OLD.current_region, OLD.current_province, OLD.current_city, OLD.current_barangay, OLD.current_address, OLD.current_zip, OLD.permanent_region, OLD.permanent_province, OLD.permanent_city, OLD.permanent_barangay, OLD.permanent_address, OLD.permanent_zip, OLD.photo_slug, OLD.admission_date, OLD.passport_number, OLD.visa_type, OLD.visa_status, OLD.school_type, OLD.examination_date, OLD.documents_agreed_date, OLD.preferred_name, OLD.suffix, OLD.country_of_birth, OLD.civil_status, OLD.country_of_residence, OLD.permanent_country, OLD.last_grade_completed, OLD.general_average";

        $this->addSql("CREATE TRIGGER bed_applicants_after_insert AFTER INSERT ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsInsert}, {$appNew});
            END");
        $this->addSql("CREATE TRIGGER bed_applicants_after_update AFTER UPDATE ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsUpdate}, {$appNew});
            END");
        $this->addSql("CREATE TRIGGER bed_applicants_after_delete AFTER DELETE ON bed_applicants FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_applicants ({$auditCols}, {$appCols}) VALUES ({$auditValsDelete}, {$appOld});
            END");
    }
}
