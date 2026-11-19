<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add address_line to bed_schools and audit_bed_schools, and recreate audit triggers';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop existing triggers on bed_schools
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_delete");

        // 2. Add address_line column to bed_schools if not present
        if ($schema->hasTable('bed_schools')) {
            $table = $schema->getTable('bed_schools');
            if (!$table->hasColumn('address_line')) {
                $this->addSql("ALTER TABLE bed_schools ADD address_line VARCHAR(255) DEFAULT NULL");
            }
        }

        // 3. Add address_line column to audit_bed_schools if not present
        if ($schema->hasTable('audit_bed_schools')) {
            $table = $schema->getTable('audit_bed_schools');
            if (!$table->hasColumn('address_line')) {
                $this->addSql("ALTER TABLE audit_bed_schools ADD address_line VARCHAR(255) DEFAULT NULL");
            }
        }

        // 4. Recreate triggers on bed_schools including address_line
        $auditCols = "audit_action, audit_date_time, emp_num, remarks, student_number, Level, School, SchoolYear, SchoolType, is_international, country, region, province, city, address_line";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), NEW.student_number, NEW.Level, NEW.School, NEW.SchoolYear, NEW.SchoolType, NEW.is_international, NEW.country, NEW.region, NEW.province, NEW.city, NEW.address_line";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), NEW.student_number, NEW.Level, NEW.School, NEW.SchoolYear, NEW.SchoolType, NEW.is_international, NEW.country, NEW.region, NEW.province, NEW.city, NEW.address_line";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), OLD.student_number, OLD.Level, OLD.School, OLD.SchoolYear, OLD.SchoolType, OLD.is_international, OLD.country, OLD.region, OLD.province, OLD.city, OLD.address_line";

        $this->addSql("CREATE TRIGGER bed_schools_after_insert AFTER INSERT ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsInsert});
            END");

        $this->addSql("CREATE TRIGGER bed_schools_after_update AFTER UPDATE ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsUpdate});
            END");

        $this->addSql("CREATE TRIGGER bed_schools_after_delete AFTER DELETE ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsDelete});
            END");
    }

    public function down(Schema $schema): void
    {
        // 1. Drop updated triggers
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_schools_after_delete");

        // 2. Drop column from bed_schools if present
        if ($schema->hasTable('bed_schools')) {
            $table = $schema->getTable('bed_schools');
            if ($table->hasColumn('address_line')) {
                $this->addSql("ALTER TABLE bed_schools DROP COLUMN address_line");
            }
        }

        // 3. Drop column from audit_bed_schools if present
        if ($schema->hasTable('audit_bed_schools')) {
            $table = $schema->getTable('audit_bed_schools');
            if ($table->hasColumn('address_line')) {
                $this->addSql("ALTER TABLE audit_bed_schools DROP COLUMN address_line");
            }
        }

        // 4. Recreate triggers without address_line
        $auditCols = "audit_action, audit_date_time, emp_num, remarks, student_number, Level, School, SchoolYear, SchoolType, is_international, country, region, province, city";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), NEW.student_number, NEW.Level, NEW.School, NEW.SchoolYear, NEW.SchoolType, NEW.is_international, NEW.country, NEW.region, NEW.province, NEW.city";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), NEW.student_number, NEW.Level, NEW.School, NEW.SchoolYear, NEW.SchoolType, NEW.is_international, NEW.country, NEW.region, NEW.province, NEW.city";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL), OLD.student_number, OLD.Level, OLD.School, OLD.SchoolYear, OLD.SchoolType, OLD.is_international, OLD.country, OLD.region, OLD.province, OLD.city";

        $this->addSql("CREATE TRIGGER bed_schools_after_insert AFTER INSERT ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsInsert});
            END");

        $this->addSql("CREATE TRIGGER bed_schools_after_update AFTER UPDATE ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsUpdate});
            END");

        $this->addSql("CREATE TRIGGER bed_schools_after_delete AFTER DELETE ON bed_schools FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_schools ({$auditCols}) VALUES ({$auditValsDelete});
            END");
    }
}
