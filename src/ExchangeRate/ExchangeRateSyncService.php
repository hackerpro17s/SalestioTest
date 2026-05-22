<?php

declare(strict_types=1);

namespace App\ExchangeRate;

use App\Contract\ExchangeRateProviderInterface;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ExchangeRateSyncService
{
    public function __construct(
        private readonly ExchangeRateProviderInterface $rateProvider,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.supported_currencies')]
        private readonly array $supportedCurrencies,
    ) {
    }

    public function sync(): int
    {
        $currencies = array_map(strtoupper(...), $this->supportedCurrencies);
        $snapshot = $this->rateProvider->fetchLatestRates($currencies);
        $persisted = 0;

        foreach ($currencies as $currency) {
            if ('USD' === $currency && 'USD' === $snapshot->baseCurrency) {
                $rate = '1';
            } elseif (isset($snapshot->rates[$currency])) {
                $rate = $snapshot->rates[$currency];
            } else {
                throw new \RuntimeException(sprintf(
                    'Currency "%s" was not returned by Open Exchange Rates.',
                    $currency,
                ));
            }

            $this->exchangeRateRepository->upsert(
                $currency,
                $rate,
                $snapshot->baseCurrency,
                $snapshot->fetchedAt,
            );
            ++$persisted;
        }

        $this->entityManager->flush();

        return $persisted;
    }
}
