<?php

namespace App\Command;

use App\Entity\ApplicantBed;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-alabang',
    description: 'Clears all applicant records for FEU Alabang',
)]
class ClearAlabangApplicantsCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->warning('EXPERIMENTAL PURPOSES ONLY: This command is intended for clearing dummy accounts.');

        if (!$io->confirm('Are you sure you want to permanently delete all FEU Alabang applicants?', false)) {
            $io->note('Operation cancelled.');
            return Command::SUCCESS;
        }

        $repository = $this->em->getRepository(ApplicantBed::class);
        $applicants = $repository->findBy(['campus' => ApplicantBed::CAMPUS_ALABANG]);

        $count = count($applicants);
        foreach ($applicants as $applicant) {
            $this->em->remove($applicant);
        }

        $this->em->flush();

        $io->success("Successfully cleared $count applicants from FEU Alabang.");
        return Command::SUCCESS;
    }
}