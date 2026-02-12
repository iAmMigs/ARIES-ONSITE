<?php

namespace App\Command;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admins',
    description: 'Creates the default admin accounts for FEU Diliman and Alabang',
)]
class CreateAdminsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $admins = [
            [
                'email' => 'admin@feudiliman.edu.ph',
                'password' => 'admin123',
                'first' => 'Admin',
                'last' => 'Diliman',
                'campus' => 'feu_diliman' // UPDATED: Matches SecurityController match case
            ],
            [
                'email' => 'admin@feualabang.edu.ph',
                'password' => 'admin123',
                'first' => 'Admin',
                'last' => 'Alabang',
                'campus' => 'feu_alabang' // UPDATED: Matches SecurityController match case
            ]
        ];

        foreach ($admins as $adminData) {
            // 1. Check if user exists
            $existingUser = $this->entityManager->getRepository(AdminUser::class)->findOneBy(['email' => $adminData['email']]);

            // Optional: Update existing user if they lack campus data
            if ($existingUser) {
                if (!$existingUser->getCampus()) {
                    $existingUser->setCampus($adminData['campus']);
                    $io->note(sprintf('Updated campus for existing admin: %s', $adminData['email']));
                } else {
                    $io->warning(sprintf('AdminUser %s already exists. Skipping.', $adminData['email']));
                }
                continue;
            }

            // 2. Create new AdminUser
            $user = new AdminUser();
            $user->setEmail($adminData['email']);
            $user->setFirstName($adminData['first']);
            $user->setLastName($adminData['last']);
            $user->setCampus($adminData['campus']); // ADDED: Save the campus
            $user->setRoles(['ROLE_ADMIN']);
            
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $adminData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $io->success(sprintf('Created admin: %s (%s)', $adminData['email'], $user->getFullName()));
        }

        $this->entityManager->flush();

        $io->success('All admin accounts have been processed.');

        return Command::SUCCESS;
    }
}