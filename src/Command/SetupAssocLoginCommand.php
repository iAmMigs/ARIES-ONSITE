<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-assoc-login',
    description: 'Sets up the assoc_login schema and seeds initial associate records idempotently.',
)]
class SetupAssocLoginCommand extends Command
{
    public function __construct(
        private readonly Connection $defaultConnection,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Setting up assoc_login schema and associate records');

        // 1. Ensure the database exists
        $this->defaultConnection->executeStatement(
            'CREATE DATABASE IF NOT EXISTS `assoc_login` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $io->success('Database `assoc_login` verified.');

        // 2. Ensure the table exists in assoc_login matching Temps/assoc_login 1.sql exactly
        $createTableSql = <<<SQL
CREATE TABLE IF NOT EXISTS `assoc_login`.`assoc_login` (
  `employee_id` varchar(15) NOT NULL DEFAULT '',
  `user_name` varchar(20) DEFAULT NULL,
  `pass_word` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`employee_id`),
  KEY `empID` (`employee_id`),
  KEY `username` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;
        $this->defaultConnection->executeStatement($createTableSql);
        $io->success('Table `assoc_login`.`assoc_login` verified.');

        // 3. Seed sample associate records idempotently
        $seedSql = <<<SQL
INSERT INTO `assoc_login`.`assoc_login` (`employee_id`, `user_name`, `pass_word`)
VALUES
  ('123456788', 'testi', '098f6bcd4621d373cade4e832627b4f6'),
  ('123456789', 'test', '098f6bcd4621d373cade4e832627b4f6')
ON DUPLICATE KEY UPDATE
  `user_name` = VALUES(`user_name`),
  `pass_word` = VALUES(`pass_word`);
SQL;
        $this->defaultConnection->executeStatement($seedSql);
        $io->success('Associate records seeded successfully (test: 123456789, testi: 123456788).');

        // 4. Ensure matching AdminUser records exist in aries_db so associates have campus access
        $adminRepo = $this->entityManager->getRepository(AdminUser::class);

        // Diliman Admin linked to 123456789 (test)
        $dilimanAdmin = $adminRepo->find(123456789);
        if (!$dilimanAdmin) {
            $dilimanAdmin = new AdminUser();
            $dilimanAdmin->setEmpNum(123456789);
            $dilimanAdmin->setFirstName('Juan');
            $dilimanAdmin->setLastName('Dela Cruz');
            $dilimanAdmin->setEmail('test@feudiliman.edu.ph');
            $dilimanAdmin->setCampus('feu_diliman');
            $dilimanAdmin->setTier(AdminUser::TIER_MASTER);
            $dilimanAdmin->setCanManageAdmins(true);
            $dilimanAdmin->setIsActive(true);
            $dilimanAdmin->setPassword('');
            $this->entityManager->persist($dilimanAdmin);
            $io->note('Created linked AdminUser for Diliman (123456789).');
        }

        // Alabang Admin linked to 123456788 (testi)
        $alabangAdmin = $adminRepo->find(123456788);
        if (!$alabangAdmin) {
            $alabangAdmin = new AdminUser();
            $alabangAdmin->setEmpNum(123456788);
            $alabangAdmin->setFirstName('Maria');
            $alabangAdmin->setLastName('Santos');
            $alabangAdmin->setEmail('testi@feualabang.edu.ph');
            $alabangAdmin->setCampus('feu_alabang');
            $alabangAdmin->setTier(AdminUser::TIER_MASTER);
            $alabangAdmin->setCanManageAdmins(true);
            $alabangAdmin->setIsActive(true);
            $alabangAdmin->setPassword('');
            $this->entityManager->persist($alabangAdmin);
            $io->note('Created linked AdminUser for Alabang (123456788).');
        }

        $this->entityManager->flush();
        $io->success('ARIES linked AdminUser records verified.');

        return Command::SUCCESS;
    }
}
