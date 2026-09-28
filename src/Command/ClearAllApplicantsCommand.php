<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicantBed;
use App\Service\ApplicantDeletionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:clear-all-applicants',
    description: 'Clears all applicant records, uploaded requirement/photo files, and applicant audit logs across all campuses',
)]
class ClearAllApplicantsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApplicantDeletionService $deletionService,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt');
        $this->addOption('keep-audits', null, InputOption::VALUE_NONE, 'Keep audit logs instead of truncating them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $repository = $this->em->getRepository(ApplicantBed::class);
        $applicants = $repository->findAll();
        $applicantCount = count($applicants);

        if (!$input->getOption('force')) {
            $io->warning("This will permanently delete all $applicantCount applicant(s), all uploaded files, and all applicant audit log entries!");
            if (!$io->confirm('Do you want to proceed?', false)) {
                $io->note('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $io->section("Deleting $applicantCount applicant record(s)...");
        foreach ($applicants as $applicant) {
            $this->deletionService->deleteApplicant($applicant);
        }

        // Clean up any remaining/orphaned files in public/uploads subdirectories
        $uploadsDir = $this->projectDir . '/public/uploads';
        $fs = new Filesystem();
        if ($fs->exists($uploadsDir)) {
            $rdi = new \RecursiveDirectoryIterator($uploadsDir, \RecursiveDirectoryIterator::SKIP_DOTS);
            $rii = new \RecursiveIteratorIterator($rdi, \RecursiveIteratorIterator::CHILD_FIRST);
            $removedFilesCount = 0;
            foreach ($rii as $file) {
                if ($file->isFile()) {
                    $fs->remove($file->getPathname());
                    $removedFilesCount++;
                }
            }
            $io->text("Cleaned up $removedFilesCount uploaded file(s) from storage.");
        }

        if (!$input->getOption('keep-audits')) {
            $io->section("Clearing applicant audit tables...");
            $conn = $this->em->getConnection();
            $auditTables = [
                'audit_bed_applicants',
                'audit_bed_guardians',
                'audit_bed_passports',
                'audit_bed_requirements',
                'audit_bed_schools',
                'audit_bed_siblings',
            ];

            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($auditTables as $table) {
                $conn->executeStatement("TRUNCATE TABLE `$table`");
                $io->text("Truncated table: $table");
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $io->success("Successfully cleared $applicantCount applicant(s), storage files, and audit logs.");
        return Command::SUCCESS;
    }
}
