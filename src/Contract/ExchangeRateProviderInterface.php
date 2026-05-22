<?php

declare(strict_types=1);

namespace App\Contract;

use App\ExchangeRate\Dto\ExchangeRatesSnapshot;

interface ExchangeRateProviderInterface
{
    /**
     * @param list<string> $currencies ISO 4217 codes to fetch (excluding base when it is USD)
     */
    public function fetchLatestRates(array $currencies): ExchangeRatesSnapshot;
}
