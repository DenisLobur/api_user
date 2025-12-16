<?php

namespace App\Command;

use App\Entity\ApiToken;
use App\Repository\UserRepository;
use App\Repository\ApiTokenRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-api-token',
    description: 'Create an API token for a user',
)]
class CreateApiTokenCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private ApiTokenRepository $apiTokenRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Token validity in days', 30);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $days = (int) $input->getOption('days');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found.', $email));
            return Command::FAILURE;
        }

        $apiToken = new ApiToken();
        $apiToken->setUser($user);
        $apiToken->setExpiresAt(new \DateTimeImmutable("+{$days} days"));

        $this->apiTokenRepository->add($apiToken, true);

        $io->success([
            'API Token created successfully!',
            sprintf('User: %s', $email),
            sprintf('Token: %s', $apiToken->getToken()),
            sprintf('Expires: %s', $apiToken->getExpiresAt()->format('Y-m-d H:i:s')),
        ]);

        return Command::SUCCESS;
    }
}

