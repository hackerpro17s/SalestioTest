<?php

declare(strict_types=1);

namespace App\ExchangeRate;

use App\Contract\ExchangeRateProviderInterface;
use App\ExchangeRate\Dto\ExchangeRatesSnapshot;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenExchangeRatesClient implements ExchangeRateProviderInterface
{
    private const string API_URL = 'https://openexchangerates.org/api/latest.json';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENEXCHANGERATES_APP_ID')]
        private readonly string $appId,
    ) {
    }

    public function fetchLatestRates(array $currencies): ExchangeRatesSnapshot
    {
        if ('' === $this->appId) {
            throw new \RuntimeException('OPENEXCHANGERATES_APP_ID is not configured.');
        }

        $symbols = array_values(array_filter(
            array_map(strtoupper(...), $currencies),
            static fn (string $code): bool => 'USD' !== $code,
        ));

        $query = ['app_id' => $this->appId];
        if ([] !== $symbols) {
            $query['symbols'] = implode(',', $symbols);
        }

        $response = $this->httpClient->request('GET', self::API_URL, [
            'query' => $query,
        ]);

        $payload = $response->toArray();
        $base = strtoupper((string) ($payload['base'] ?? 'USD'));
        $rates = $payload['rates'] ?? [];

        if (!is_array($rates)) {
            throw new \RuntimeException('Open Exchange Rates response does not contain a rates object.');
        }

        $normalized = [];
        foreach ($rates as $currency => $rate) {
            $normalized[strtoupper((string) $currency)] = (string) $rate;
        }

        $fetchedAt = isset($payload['timestamp'])
            ? (new \DateTimeImmutable())->setTimestamp((int) $payload['timestamp'])
            : new \DateTimeImmutable();

        return new ExchangeRatesSnapshot($base, $normalized, $fetchedAt);
    }
}
