<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ApplicantBedRepository;
use App\Service\ApplicantDeletionService;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-all-applicants',
    description: 'Deletes all applicant records and their associated files across campuses.'
)]
class ClearAllApplicantsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CampusDatabaseSwitcher $dbSwitcher,
        private readonly ApplicantBedRepository $applicantRepository,
        private readonly ApplicantDeletionService $deletionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'campus',
            'c',
            InputOption::VALUE_OPTIONAL,
            'Campus to clear (all, diliman, alabang)',
            'all'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $campusOption = strtolower((string)$input->getOption('campus'));

        $campuses = match ($campusOption) {
            'diliman', 'fdil' => [CampusDatabaseSwitcher::CAMPUS_DILIMAN],
            'alabang', 'falab' => [CampusDatabaseSwitcher::CAMPUS_ALABANG],
            default => [CampusDatabaseSwitcher::CAMPUS_DILIMAN, CampusDatabaseSwitcher::CAMPUS_ALABANG],
        };

        $totalDeleted = 0;

        foreach ($campuses as $campus) {
            $io->section(sprintf('Processing campus: %s', $campus));
            $this->dbSwitcher->switchToCampus($campus);
            $this->em->clear();

            $applicants = $this->applicantRepository->findAll();
            $count = count($applicants);

            $io->writeln(sprintf('Found %d applicant(s) in %s.', $count, $campus));

            foreach ($applicants as $applicant) {
                $studentNumber = $applicant->getStudentNumber() ?? 'Unknown';
                $name = $applicant->getFullName();
                $io->writeln(sprintf('  Deleting applicant %s (%s)...', $studentNumber, $name));

                $this->deletionService->deleteApplicant($applicant);
                $totalDeleted++;
            }

            $this->em->clear();
        }

        $io->success(sprintf('Successfully cleared out %d applicant(s) total.', $totalDeleted));

        return Command::SUCCESS;
    }
}
