<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\UserEntity;
use App\Repository\UserRepository;
use App\Security\UserRole;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create_admin_user',
    description: 'Creates an admin user (ROLE_ADMIN)'
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }
    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED, 'Phone (unique)')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name')
            ->addArgument('email', InputArgument::REQUIRED, 'Email (unique)')
            ->addArgument('password', InputArgument::REQUIRED, 'Plain password');
    }
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $phone = $input->getArgument('phone');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $email = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        // Проверки существования по phone / email
        $existsByPhone = $this->userRepository->findOneBy(['phone' => $phone]);
        if ($existsByPhone !== null) {
            $io->error(sprintf('User with phone "%s" already exists.', $phone));
            return Command::FAILURE;
        }

        $existsByEmail = $this->userRepository->findOneBy(['email' => $email]);
        if ($existsByEmail !== null) {
            $io->error(sprintf('User with email "%s" already exists.', $email));
            return Command::FAILURE;
        }

        // Создаем сущность (конструктор в твоем классе принимает пароль и роли, но лучше положим хеш позже)
        $user = new UserEntity(
            $phone,
            $firstName,
            $lastName,
            $email,
            '', // временно пустой пароль, заполним ниже
            [UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]
        );

        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);
        $user->setRoles([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);

        // Сохраняем через репозиторий
        $this->userRepository->save($user, true);

        $io->success(sprintf('Admin user created: %s (%s)', $phone, $email));

        return Command::SUCCESS;
    }
}
