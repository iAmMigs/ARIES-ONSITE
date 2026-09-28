<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alters applicant and guardian tables, creates passport and audit tables, recreates audit triggers, and seeds international student documents.';
    }

    public function up(Schema $schema): void
    {
        // 1. Alter bed_applicants and audit_bed_applicants
        $this->addSql('ALTER TABLE bed_applicants 
            ADD preferred_name VARCHAR(100) DEFAULT NULL,
            ADD suffix VARCHAR(20) DEFAULT NULL,
            ADD country_of_birth VARCHAR(100) DEFAULT NULL,
            ADD civil_status VARCHAR(50) DEFAULT NULL,
            ADD country_of_residence VARCHAR(100) DEFAULT NULL,
            ADD permanent_country VARCHAR(100) DEFAULT NULL,
            ADD last_grade_completed VARCHAR(50) DEFAULT NULL,
            ADD general_average DOUBLE PRECISION DEFAULT NULL');

        $this->addSql('ALTER TABLE audit_bed_applicants 
            ADD preferred_name VARCHAR(100) DEFAULT NULL,
            ADD suffix VARCHAR(20) DEFAULT NULL,
            ADD country_of_birth VARCHAR(100) DEFAULT NULL,
            ADD civil_status VARCHAR(50) DEFAULT NULL,
            ADD country_of_residence VARCHAR(100) DEFAULT NULL,
            ADD permanent_country VARCHAR(100) DEFAULT NULL,
            ADD last_grade_completed VARCHAR(50) DEFAULT NULL,
            ADD general_average DOUBLE PRECISION DEFAULT NULL');

        // 2. Alter bed_guardians and audit_bed_guardians
        $this->addSql('ALTER TABLE bed_guardians ADD nationality VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_guardians ADD nationality VARCHAR(100) DEFAULT NULL');

        // 3. Create bed_passports and audit_bed_passports
        $this->addSql('CREATE TABLE bed_passports (
            id INT AUTO_INCREMENT NOT NULL,
            student_number VARCHAR(20) NOT NULL,
            passport_number VARCHAR(50) NOT NULL,
            country_of_issue VARCHAR(100) NOT NULL,
            date_issued DATE NOT NULL,
            expiration_date DATE NOT NULL,
            UNIQUE INDEX UNIQ_PASSPORT_STUDENT (student_number),
            PRIMARY KEY(id),
            CONSTRAINT FK_PASSPORT_STUDENT FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE audit_bed_passports (
            audit_id INT AUTO_INCREMENT NOT NULL,
            audit_action VARCHAR(10) NOT NULL,
            audit_date_time DATETIME NOT NULL,
            emp_num VARCHAR(50) DEFAULT NULL,
            remarks VARCHAR(255) DEFAULT NULL,
            host VARCHAR(255) DEFAULT NULL,
            original_passport_id INT DEFAULT NULL,
            student_number VARCHAR(20) DEFAULT NULL,
            passport_number VARCHAR(50) DEFAULT NULL,
            country_of_issue VARCHAR(100) DEFAULT NULL,
            date_issued DATE DEFAULT NULL,
            expiration_date DATE DEFAULT NULL,
            PRIMARY KEY(audit_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        // 4. Drop old triggers for bed_applicants and bed_guardians
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_applicants_after_delete");
        
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_delete");

        // 5. Create triggers for bed_applicants, bed_guardians, and bed_passports
        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

        // bed_applicants triggers
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

        // bed_guardians triggers
        $gdCols = "original_guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW, guardian_type, ofw_country, email, address, nationality";
        $gdNew = "NEW.guardian_id, NEW.student_number, NEW.Relationship, NEW.ParentName, NEW.Occupation, NEW.ContactNo, NEW.IsDeceased, NEW.IsOFW, NEW.guardian_type, NEW.OfwCountry, NEW.Email, NEW.Address, NEW.nationality";
        $gdOld = "OLD.guardian_id, OLD.student_number, OLD.Relationship, OLD.ParentName, OLD.Occupation, OLD.ContactNo, OLD.IsDeceased, OLD.IsOFW, OLD.guardian_type, OLD.OfwCountry, OLD.Email, OLD.Address, OLD.nationality";

        $this->addSql("CREATE TRIGGER bed_guardians_after_insert AFTER INSERT ON bed_guardians FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_guardians ({$auditCols}, {$gdCols}) VALUES ({$auditValsInsert}, {$gdNew});
            END");
        $this->addSql("CREATE TRIGGER bed_guardians_after_update AFTER UPDATE ON bed_guardians FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_guardians ({$auditCols}, {$gdCols}) VALUES ({$auditValsUpdate}, {$gdNew});
            END");
        $this->addSql("CREATE TRIGGER bed_guardians_after_delete AFTER DELETE ON bed_guardians FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_guardians ({$auditCols}, {$gdCols}) VALUES ({$auditValsDelete}, {$gdOld});
            END");

        // bed_passports triggers
        $passCols = "original_passport_id, student_number, passport_number, country_of_issue, date_issued, expiration_date";
        $passNew = "NEW.id, NEW.student_number, NEW.passport_number, NEW.country_of_issue, NEW.date_issued, NEW.expiration_date";
        $passOld = "OLD.id, OLD.student_number, OLD.passport_number, OLD.country_of_issue, OLD.date_issued, OLD.expiration_date";

        $this->addSql("CREATE TRIGGER bed_passports_after_insert AFTER INSERT ON bed_passports FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_passports ({$auditCols}, {$passCols}) VALUES ({$auditValsInsert}, {$passNew});
            END");
        $this->addSql("CREATE TRIGGER bed_passports_after_update AFTER UPDATE ON bed_passports FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_passports ({$auditCols}, {$passCols}) VALUES ({$auditValsUpdate}, {$passNew});
            END");
        $this->addSql("CREATE TRIGGER bed_passports_after_delete AFTER DELETE ON bed_passports FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_passports ({$auditCols}, {$passCols}) VALUES ({$auditValsDelete}, {$passOld});
            END");

        // 6. Seed document requirements for International Students (FOREIGN nationality type) at FEU Diliman
        $this->addSql("INSERT INTO document_setup (document_name, slug, folder_name, student_type, nationality_type, grade_levels, campus, allowed_file_types) VALUES 
            ('Passport (Bio Page)', 'passport_bio', 'passport_bio', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Valid Visa', 'valid_visa', 'valid_visa', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Recent 2x2 ID Photo (White Background)', 'photo_2x2', 'photo_2x2', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'jpg,jpeg,png'),
            ('Alien Certificate of Registration (ACR I-Card)', 'acr_icard', 'acr_icard', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Special Study Permit (SSP)', 'special_study_permit', 'special_study_permit', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Philippine Birth Certificate', 'ph_birth_certificate', 'ph_birth_certificate', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('English Translations', 'english_translations', 'english_translations', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Parent/Guardian Passport or Valid Government ID', 'parent_passport_id', 'parent_passport_id', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Parent/Guardian Proof of Residency', 'proof_of_residency', 'proof_of_residency', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png'),
            ('Medical Record/Vaccination Record', 'medical_record', 'medical_record', 'Both', 'FOREIGN', '[\"All\"]', 'FDIL', 'pdf,jpg,jpeg,png')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TRIGGER IF EXISTS bed_passports_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_passports_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_passports_after_delete");

        $this->addSql('DROP TABLE bed_passports');
        $this->addSql('DROP TABLE audit_bed_passports');

        $this->addSql('ALTER TABLE bed_guardians DROP nationality');
        $this->addSql('ALTER TABLE audit_bed_guardians DROP nationality');

        $this->addSql('ALTER TABLE bed_applicants 
            DROP preferred_name,
            DROP suffix,
            DROP country_of_birth,
            DROP civil_status,
            DROP country_of_residence,
            DROP permanent_country,
            DROP last_grade_completed,
            DROP general_average');

        $this->addSql('ALTER TABLE audit_bed_applicants 
            DROP preferred_name,
            DROP suffix,
            DROP country_of_birth,
            DROP civil_status,
            DROP country_of_residence,
            DROP permanent_country,
            DROP last_grade_completed,
            DROP general_average');

        $this->addSql("DELETE FROM document_setup WHERE slug IN ('passport_bio', 'valid_visa', 'photo_2x2', 'acr_icard', 'special_study_permit', 'ph_birth_certificate', 'english_translations', 'parent_passport_id', 'proof_of_residency', 'medical_record') AND campus = 'FDIL'");
    }
}
