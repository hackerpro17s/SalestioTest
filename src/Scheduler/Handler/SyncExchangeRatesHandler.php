<?php

declare(strict_types=1);

namespace App\Scheduler\Handler;

use App\ExchangeRate\ExchangeRateSyncService;
use App\Scheduler\Message\SyncExchangeRatesMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncExchangeRatesHandler
{
    public function __construct(
        private readonly ExchangeRateSyncService $exchangeRateSyncService,
    ) {
    }

    public function __invoke(SyncExchangeRatesMessage $message): void
    {
        $this->exchangeRateSyncService->sync();
    }
}
