<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\CartSummarizerInterface;
use App\Dto\CartSummaryRequest;
use App\Dto\CartSummaryResponse;
use App\Dto\CartItem;
use App\Repository\ExchangeRateRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Calculates cart totals and converts currencies using stored exchange rates.
 */
final class CartSummarizer implements CartSummarizerInterface
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository,
        #[Autowire(param: 'app.supported_currencies')]
        private readonly array $supportedCurrencies,
    ) {
    }

    public function summarize(CartSummaryRequest $request): CartSummaryResponse
    {
        $checkoutCurrency = strtoupper($request->checkoutCurrency);
        $totalInCheckoutCurrency = 0.0;

        foreach ($request->items as $item) {
            if (!$item instanceof CartItem) {
                throw new \InvalidArgumentException('Each item must be a CartItem instance.');
            }

            $lineTotal = $item->getLineTotal();
            $itemCurrency = strtoupper($item->currency);

            // If item is already in checkout currency, add directly
            if ($itemCurrency === $checkoutCurrency) {
                $totalInCheckoutCurrency += $lineTotal;
                continue;
            }

            // Convert item currency to checkout currency via USD base
            $convertedAmount = $this->convertCurrency(
                $lineTotal,
                $itemCurrency,
                $checkoutCurrency,
            );

            $totalInCheckoutCurrency += $convertedAmount;
        }

        return new CartSummaryResponse(
            $totalInCheckoutCurrency,
            $checkoutCurrency,
        );
    }

    /**
     * Convert an amount from one currency to another using stored exchange rates.
     *
     * Exchange rates are stored with USD as the base currency.
     * Conversion formula:
     *   1. Convert source currency to USD
     *   2. Convert USD to target currency
     */
    private function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        // Validate currencies are supported
        $this->validateCurrency($fromCurrency);
        $this->validateCurrency($toCurrency);

        // Get exchange rates (USD is base, so USD rate = 1)
        $fromRate = $this->getRateForCurrency($fromCurrency);
        $toRate = $this->getRateForCurrency($toCurrency);

        // Convert: amount * (toRate / fromRate)
        // Example: Convert 36 USD to EUR
        //   fromRate (USD) = 1, toRate (EUR) = 0.89475
        //   36 * (0.89475 / 1) = 32.211 EUR
        return $amount * ($toRate / $fromRate);
    }

    private function validateCurrency(string $currency): void
    {
        if (!in_array($currency, $this->supportedCurrencies, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Currency "%s" is not supported. Supported currencies: %s',
                $currency,
                implode(', ', $this->supportedCurrencies),
            ));
        }
    }

    private function getRateForCurrency(string $currency): string
    {
        // USD is the base currency, so its rate is always 1
        if ('USD' === $currency) {
            return '1';
        }

        $exchangeRate = $this->exchangeRateRepository->findOneByCurrency($currency);

        if (null === $exchangeRate) {
            throw new \RuntimeException(sprintf(
                'Exchange rate for currency "%s" not found. Please run the exchange rates sync.',
                $currency,
            ));
        }

        return $exchangeRate->getRate();
    }
}