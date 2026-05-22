<?php

declare(strict_types=1);

namespace App\ExchangeRate\Dto;

/**
 * Rates relative to a single base currency (USD on the Open Exchange Rates free plan).
 *
 * @param array<string, string> $rates currency code => rate
 */
final readonly class ExchangeRatesSnapshot
{
    public function __construct(
        public string $baseCurrency,
        public array $rates,
        public \DateTimeImmutable $fetchedAt,
    ) {
    }
}
