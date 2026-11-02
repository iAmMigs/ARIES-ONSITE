<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\AdminUser;
use App\Service\CampusDatabaseSwitcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admins',
    description: 'Seeds master and dummy administrators for FEU Diliman and FEU Alabang across assoc_login and campus databases.',
)]
class CreateAdminsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CampusDatabaseSwitcher $databaseSwitcher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->em->getConnection();

        $allModules = array_keys(AdminUser::AVAILABLE_MODULES);

        $adminList = [
            // FEU Diliman Admins
            [
                'emp_num'  => 1,
                'email'    => 'admin@feudiliman.edu.ph',
                'username' => 'admindiliman',
                'password' => 'admin123',
                'first'    => 'Admin',
                'last'     => 'Diliman',
                'campus'   => 'feu_diliman',
                'tier'     => AdminUser::TIER_MASTER,
                'modules'  => $allModules,
            ],
            [
                'emp_num'  => 104829,
                'email'    => 'mcastillo@feudiliman.edu.ph',
                'username' => 'mcastillo_dil',
                'password' => 'admin123',
                'first'    => 'MARCO',
                'last'     => 'CASTILLO',
                'campus'   => 'feu_diliman',
                'tier'     => AdminUser::TIER_STAFF,
                'modules'  => [
                    AdminUser::MODULE_DASHBOARD,
                    AdminUser::MODULE_REGISTRATIONS,
                    AdminUser::MODULE_DOCUMENTS,
                    AdminUser::MODULE_SCHOOL_YEAR,
                    AdminUser::MODULE_ADMIN_ACCESS,
                ],
            ],
            [
                'emp_num'  => 107394,
                'email'    => 'avillanueva@feudiliman.edu.ph',
                'username' => 'avillanueva_dil',
                'password' => 'admin123',
                'first'    => 'ANGELA',
                'last'     => 'VILLANUEVA',
                'campus'   => 'feu_diliman',
                'tier'     => AdminUser::TIER_STAFF,
                'modules'  => [
                    AdminUser::MODULE_DASHBOARD,
                    AdminUser::MODULE_REGISTRATIONS,
                    AdminUser::MODULE_DOCUMENTS,
                ],
            ],

            // FEU Alabang Admins
            [
                'emp_num'  => 2,
                'email'    => 'admin@feualabang.edu.ph',
                'username' => 'adminalabang',
                'password' => 'admin123',
                'first'    => 'Admin',
                'last'     => 'Alabang',
                'campus'   => 'feu_alabang',
                'tier'     => AdminUser::TIER_MASTER,
                'modules'  => $allModules,
            ],
            [
                'emp_num'  => 205183,
                'email'    => 'rnavarro@feualabang.edu.ph',
                'username' => 'rnavarro_ala',
                'password' => 'admin123',
                'first'    => 'RAFAEL',
                'last'     => 'NAVARRO',
                'campus'   => 'feu_alabang',
                'tier'     => AdminUser::TIER_STAFF,
                'modules'  => [
                    AdminUser::MODULE_DASHBOARD,
                    AdminUser::MODULE_REGISTRATIONS,
                    AdminUser::MODULE_DOCUMENTS,
                    AdminUser::MODULE_SCHOOL_YEAR,
                    AdminUser::MODULE_ADMIN_ACCESS,
                ],
            ],
            [
                'emp_num'  => 208641,
                'email'    => 'csoriano@feualabang.edu.ph',
                'username' => 'csoriano_ala',
                'password' => 'admin123',
                'first'    => 'CLARISSE',
                'last'     => 'SORIANO',
                'campus'   => 'feu_alabang',
                'tier'     => AdminUser::TIER_STAFF,
                'modules'  => [
                    AdminUser::MODULE_DASHBOARD,
                    AdminUser::MODULE_REGISTRATIONS,
                    AdminUser::MODULE_DOCUMENTS,
                ],
            ],
        ];

        $io->title('Seeding Master and Dummy Administrators');

        // Ensure columns in assoc_login
        try {
            $conn->executeStatement("ALTER TABLE `aries_assoc_login`.`assoc_login` ADD COLUMN IF NOT EXISTS `first_name` VARCHAR(100) DEFAULT NULL");
            $conn->executeStatement("ALTER TABLE `aries_assoc_login`.`assoc_login` ADD COLUMN IF NOT EXISTS `last_name` VARCHAR(100) DEFAULT NULL");
            $conn->executeStatement("ALTER TABLE `aries_assoc_login`.`assoc_login` ADD COLUMN IF NOT EXISTS `email` VARCHAR(180) DEFAULT NULL");
        } catch (\Throwable) {}

        foreach ($adminList as $adminData) {
            $empNum = (int) $adminData['emp_num'];
            $username = (string) $adminData['username'];
            $md5Password = md5((string) $adminData['password']);
            $campus = (string) $adminData['campus'];

            // 1. Sync central assoc_login table in aries_assoc_login
            $conn->executeStatement("
                INSERT INTO `aries_assoc_login`.`assoc_login` 
                    (employee_id, user_name, pass_word, first_name, last_name, email)
                VALUES 
                    (:emp, :username, :pass, :first, :last, :email)
                ON DUPLICATE KEY UPDATE 
                    user_name = VALUES(user_name),
                    pass_word = VALUES(pass_word),
                    first_name = VALUES(first_name),
                    last_name = VALUES(last_name),
                    email = VALUES(email)
            ", [
                'emp'      => (string) $empNum,
                'username' => $username,
                'pass'     => $md5Password,
                'first'    => $adminData['first'],
                'last'     => $adminData['last'],
                'email'    => $adminData['email'],
            ]);

            // 2. Sync campus-specific database admin_users table
            $this->databaseSwitcher->switchToCampus($campus);
            $repo = $this->em->getRepository(AdminUser::class);
            $existingAdmin = $repo->find($empNum);

            if (!$existingAdmin) {
                $user = new AdminUser();
                $user->setEmpNum($empNum);
                $user->setEmail($adminData['email']);
                $user->setFirstName($adminData['first']);
                $user->setLastName($adminData['last']);
                $user->setCampus($campus);
                $user->setTier($adminData['tier']);
                $user->setModules($adminData['modules']);
                $user->setIsActive(true);
                $user->setRoles(['ROLE_ADMIN']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $adminData['password']));

                $this->em->persist($user);
                $io->success(sprintf(
                    'Created %s (%s) with %d module(s) in %s.',
                    $user->getFullName(),
                    $adminData['email'],
                    count($adminData['modules']),
                    $this->databaseSwitcher->getCurrentDatabase()
                ));
            } else {
                $existingAdmin->setModules($adminData['modules']);
                $existingAdmin->setTier($adminData['tier']);
                $existingAdmin->setCampus($campus);
                $existingAdmin->setIsActive(true);
                $existingAdmin->setPassword($this->passwordHasher->hashPassword($existingAdmin, $adminData['password']));
                $io->text(sprintf(
                    'Updated %s (%s) in %s.',
                    $existingAdmin->getFullName(),
                    $adminData['email'],
                    $this->databaseSwitcher->getCurrentDatabase()
                ));
            }

            $this->em->flush();
        }

        $io->success('All administrators have been successfully processed.');
        return Command::SUCCESS;
    }
}
