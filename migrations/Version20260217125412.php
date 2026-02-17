<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Refactors database to use student_number as Primary Key dynamically
 */
final class Version20260217125412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactors database to use student_number as Primary Key, handling dynamic FK names';
    }

    public function up(Schema $schema): void
    {
        // 1. DYNAMICALLY FIND AND DROP FOREIGN KEYS
        // We cannot rely on hardcoded names like FK_9F5A3C4E8F48FD6B because they vary by environment.
        
        $tablesWithFk = [
            'bed_guardians', 
            'bed_requirements', 
            'bed_schools', 
            'bed_siblings'
        ];

        // Use Schema Manager to inspect the database
        $sm = $this->connection->createSchemaManager();

        foreach ($tablesWithFk as $tableName) {
            // Drop Foreign Keys pointing to bed_applicants
            $foreignKeys = $sm->listTableForeignKeys($tableName);
            foreach ($foreignKeys as $fk) {
                if ($fk->getForeignTableName() === 'bed_applicants') {
                    $this->addSql('ALTER TABLE ' . $tableName . ' DROP FOREIGN KEY ' . $fk->getName());
                }
            }

            // Drop Indexes related to the old 'ad_con' column
            // We usually want to drop the index that doctrine created for the FK
            $indexes = $sm->listTableIndexes($tableName);
            foreach ($indexes as $index) {
                // If index contains 'ad_con' and is not the Primary Key, drop it
                if (in_array('ad_con', $index->getColumns()) && !$index->isPrimary()) {
                    $this->addSql('DROP INDEX ' . $index->getName() . ' ON ' . $tableName);
                }
            }
        }

        // 2. Drop unique index on student_number in applicants (if it exists)
        // We check blindly or use a try-catch in SQL, but usually dropping by name is safe if we know it was created by doctrine
        // If this fails, it's likely already gone, but we'll include the standard name.
        $applicantsIndexes = $sm->listTableIndexes('bed_applicants');
        foreach ($applicantsIndexes as $index) {
            if (in_array('student_number', $index->getColumns()) && $index->isUnique()) {
                $this->addSql('DROP INDEX ' . $index->getName() . ' ON bed_applicants');
            }
        }

        // 3. MODIFY PARENT TABLE (bed_applicants)
        $this->addSql('ALTER TABLE bed_applicants DROP ad_con, CHANGE student_number student_number VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (student_number)');

        // 4. MODIFY CHILD TABLES (Rename column and update PKs)
        $this->addSql('ALTER TABLE bed_guardians CHANGE ad_con student_number VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (student_number, Relationship)');
        $this->addSql('ALTER TABLE bed_requirements CHANGE ad_con student_number VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (student_number, Slug)');
        $this->addSql('ALTER TABLE bed_schools CHANGE ad_con student_number VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (student_number, Level)');
        $this->addSql('ALTER TABLE bed_siblings CHANGE ad_con student_number VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (student_number, SiblingName)');

        // 5. RE-ESTABLISH FOREIGN KEYS (With standard naming)
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT FK_9F5A3C4E18A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_requirements ADD CONSTRAINT FK_93039D6A18A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_schools ADD CONSTRAINT FK_350844818A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_siblings ADD CONSTRAINT FK_29000C3218A6C7D4 FOREIGN KEY (student_number) REFERENCES bed_applicants (student_number) ON DELETE CASCADE');

        // 6. RE-CREATE INDEXES
        $this->addSql('CREATE INDEX IDX_9F5A3C4E18A6C7D4 ON bed_guardians (student_number)');
        $this->addSql('CREATE INDEX IDX_93039D6A18A6C7D4 ON bed_requirements (student_number)');
        $this->addSql('CREATE INDEX IDX_350844818A6C7D4 ON bed_schools (student_number)');
        $this->addSql('CREATE INDEX IDX_29000C3218A6C7D4 ON bed_siblings (student_number)');
    }

    public function down(Schema $schema): void
    {
        // 1. Drop new Foreign Keys
        $this->addSql('ALTER TABLE bed_guardians DROP FOREIGN KEY FK_9F5A3C4E18A6C7D4');
        $this->addSql('ALTER TABLE bed_requirements DROP FOREIGN KEY FK_93039D6A18A6C7D4');
        $this->addSql('ALTER TABLE bed_schools DROP FOREIGN KEY FK_350844818A6C7D4');
        $this->addSql('ALTER TABLE bed_siblings DROP FOREIGN KEY FK_29000C3218A6C7D4');

        // 2. Drop new indexes
        $this->addSql('DROP INDEX IDX_9F5A3C4E18A6C7D4 ON bed_guardians');
        $this->addSql('DROP INDEX IDX_93039D6A18A6C7D4 ON bed_requirements');
        $this->addSql('DROP INDEX IDX_350844818A6C7D4 ON bed_schools');
        $this->addSql('DROP INDEX IDX_29000C3218A6C7D4 ON bed_siblings');

        // 3. Revert Parent Table
        $this->addSql('ALTER TABLE bed_applicants ADD ad_con VARCHAR(20) NOT NULL, CHANGE student_number student_number VARCHAR(20) DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (ad_con)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F3E3873618A6C7D4 ON bed_applicants (student_number)');

        // 4. Revert Child Tables
        $this->addSql('ALTER TABLE bed_guardians CHANGE student_number ad_con VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (ad_con, Relationship)');
        $this->addSql('ALTER TABLE bed_requirements CHANGE student_number ad_con VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (ad_con, Slug)');
        $this->addSql('ALTER TABLE bed_schools CHANGE student_number ad_con VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (ad_con, Level)');
        $this->addSql('ALTER TABLE bed_siblings CHANGE student_number ad_con VARCHAR(20) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (ad_con, SiblingName)');

        // 5. Restore old Foreign Keys (Using the specific names generated by previous migrations)
        $this->addSql('ALTER TABLE bed_guardians ADD CONSTRAINT FK_9F5A3C4E8F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_requirements ADD CONSTRAINT FK_93039D6A8F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_schools ADD CONSTRAINT FK_35084488F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bed_siblings ADD CONSTRAINT FK_29000C328F48FD6B FOREIGN KEY (ad_con) REFERENCES bed_applicants (ad_con) ON DELETE CASCADE');

        // 6. Restore old Indexes
        $this->addSql('CREATE INDEX IDX_9F5A3C4E8F48FD6B ON bed_guardians (ad_con)');
        $this->addSql('CREATE INDEX IDX_93039D6A8F48FD6B ON bed_requirements (ad_con)');
        $this->addSql('CREATE INDEX IDX_35084488F48FD6B ON bed_schools (ad_con)');
        $this->addSql('CREATE INDEX IDX_29000C328F48FD6B ON bed_siblings (ad_con)');
    }
}