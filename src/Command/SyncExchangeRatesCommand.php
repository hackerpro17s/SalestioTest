<?php

declare(strict_types=1);

namespace App\Command;

use App\ExchangeRate\ExchangeRateSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:exchange-rates:sync',
    description: 'Fetch exchange rates from Open Exchange Rates and persist them locally',
)]
final class SyncExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateSyncService $exchangeRateSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $count = $this->exchangeRateSyncService->sync();
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Synchronized %d exchange rate(s).', $count));

        return Command::SUCCESS;
    }
}
