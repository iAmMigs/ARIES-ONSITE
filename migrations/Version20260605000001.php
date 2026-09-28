<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260605000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recreate bed_requirements triggers and ensure audit_bed_requirements has is_deleted column';
    }

    public function up(Schema $schema): void
    {
        // Ensure audit_bed_requirements has the correct is_deleted column
        $table = $schema->getTable('audit_bed_requirements');
        
        // If it has IsDeleted (from a previous broken migration), rename it to is_deleted
        if ($table->hasColumn('IsDeleted') && !$table->hasColumn('is_deleted')) {
            $this->addSql('ALTER TABLE audit_bed_requirements CHANGE IsDeleted is_deleted TINYINT(1) DEFAULT 0 NOT NULL');
        } 
        // If it lacks both, just add is_deleted
        elseif (!$table->hasColumn('is_deleted') && !$table->hasColumn('IsDeleted')) {
            $this->addSql('ALTER TABLE audit_bed_requirements ADD is_deleted TINYINT(1) DEFAULT 0 NOT NULL');
        }

        // Drop the old triggers that might have been referencing wrong columns
        $this->addSql("DROP TRIGGER IF EXISTS bed_requirements_after_insert");
        $this->addSql("DROP TRIGGER IF EXISTS bed_requirements_after_update");
        $this->addSql("DROP TRIGGER IF EXISTS bed_requirements_after_delete");

        // Recreate the triggers safely using is_deleted
        $auditCols = "audit_action, audit_date_time, emp_num, remarks";
        $auditValsInsert = "'INSERT', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";
        $auditValsUpdate = "'UPDATE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";
        $auditValsDelete = "'DELETE', NOW(), @app_user_emp_num, IF(@app_user_emp_num IS NULL, 'BACKDOOR', NULL)";

        $targetCols = "student_number, Slug, Requirement, StoredFileName, Status, DateSubmitted, is_deleted";
        $newCols = "NEW.student_number, NEW.Slug, NEW.Requirement, NEW.StoredFileName, NEW.Status, NEW.DateSubmitted, NEW.is_deleted";
        $oldCols = "OLD.student_number, OLD.Slug, OLD.Requirement, OLD.StoredFileName, OLD.Status, OLD.DateSubmitted, OLD.is_deleted";

        $this->addSql("
            CREATE TRIGGER bed_requirements_after_insert AFTER INSERT ON bed_requirements FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_requirements ({$auditCols}, {$targetCols})
                VALUES ({$auditValsInsert}, {$newCols});
            END
        ");
        
        $this->addSql("
            CREATE TRIGGER bed_requirements_after_update AFTER UPDATE ON bed_requirements FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_requirements ({$auditCols}, {$targetCols})
                VALUES ({$auditValsUpdate}, {$newCols});
            END
        ");

        $this->addSql("
            CREATE TRIGGER bed_requirements_after_delete AFTER DELETE ON bed_requirements FOR EACH ROW
            BEGIN
                INSERT INTO audit_bed_requirements ({$auditCols}, {$targetCols})
                VALUES ({$auditValsDelete}, {$oldCols});
            END
        ");
    }

    public function down(Schema $schema): void
    {
        // No down migration required for this fix
    }
}
