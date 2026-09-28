<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260927000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add permanent_country to bed_guardians and audit_bed_guardians, and update triggers.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bed_guardians ADD permanent_country VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_bed_guardians ADD permanent_country VARCHAR(100) DEFAULT NULL');

        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_delete");

        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

        $gdCols = "original_guardian_id, student_number, Relationship, ParentName, Occupation, ContactNo, IsDeceased, IsOFW, guardian_type, ofw_country, email, address, nationality, permanent_country";
        $gdNew = "NEW.guardian_id, NEW.student_number, NEW.Relationship, NEW.ParentName, NEW.Occupation, NEW.ContactNo, NEW.IsDeceased, NEW.IsOFW, NEW.guardian_type, NEW.OfwCountry, NEW.Email, NEW.Address, NEW.nationality, NEW.permanent_country";
        $gdOld = "OLD.guardian_id, OLD.student_number, OLD.Relationship, OLD.ParentName, OLD.Occupation, OLD.ContactNo, OLD.IsDeceased, OLD.IsOFW, OLD.guardian_type, OLD.OfwCountry, OLD.Email, OLD.Address, OLD.nationality, OLD.permanent_country";

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
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_guardians_after_delete");

        $auditCols = "audit_action, audit_date_time, emp_num, remarks, host";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), USER()";

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

        $this->addSql('ALTER TABLE bed_guardians DROP permanent_country');
        $this->addSql('ALTER TABLE audit_bed_guardians DROP permanent_country');
    }
}
