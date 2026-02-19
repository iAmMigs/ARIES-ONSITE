<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260219_RemainingAuditTriggers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replaces old broken triggers for siblings, schools, and requirements with dynamic audit triggers using audit_date_time.';
    }

    public function up(Schema $schema): void
    {
        // Drop the old triggers (if named standardly)
        $this->addSql('DROP TRIGGER IF EXISTS after_sibling_update_audit');
        $this->addSql('DROP TRIGGER IF EXISTS after_school_update_audit');
        $this->addSql('DROP TRIGGER IF EXISTS after_requirement_update_audit');

        // 1. SIBLINGS TRIGGER
        $this->addSql("
            CREATE TRIGGER after_sibling_update_audit
            AFTER UPDATE ON bed_siblings
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_siblings (
                    emp_num, audit_date_time, host, audit_action, remarks,
                    student_number, SiblingName, School, IsFeuStudent, FeuStudentNo
                ) VALUES (
                    @app_user_emp_num, NOW(), CURRENT_USER(), 'UPDATE', IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL),
                    NEW.student_number, NEW.SiblingName, NEW.School, NEW.IsFeuStudent, NEW.FeuStudentNo
                );
            END;
        ");

        // 2. SCHOOLS TRIGGER
        $this->addSql("
            CREATE TRIGGER after_school_update_audit
            AFTER UPDATE ON bed_schools
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools (
                    emp_num, audit_date_time, host, audit_action, remarks,
                    student_number, Level, School, YearEnd
                ) VALUES (
                    @app_user_emp_num, NOW(), CURRENT_USER(), 'UPDATE', IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL),
                    NEW.student_number, NEW.Level, NEW.School, NEW.YearEnd
                );
            END;
        ");

        // 3. REQUIREMENTS TRIGGER
        $this->addSql("
            CREATE TRIGGER after_requirement_update_audit
            AFTER UPDATE ON bed_requirements
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_requirements (
                    emp_num, audit_date_time, host, audit_action, remarks,
                    student_number, Slug, Requirement, StoredFileName, Status, IsRequired, DateSubmitted
                ) VALUES (
                    @app_user_emp_num, NOW(), CURRENT_USER(), 'UPDATE', IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL),
                    NEW.student_number, NEW.Slug, NEW.Requirement, NEW.StoredFileName, NEW.Status, NEW.IsRequired, NEW.DateSubmitted
                );
            END;
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS after_sibling_update_audit');
        $this->addSql('DROP TRIGGER IF EXISTS after_school_update_audit');
        $this->addSql('DROP TRIGGER IF EXISTS after_requirement_update_audit');
    }
}