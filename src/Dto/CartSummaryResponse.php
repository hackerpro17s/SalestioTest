<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO for cart summary response.
 */
final class CartSummaryResponse
{
    public function __construct(
        /**
         * Total checkout price converted to the requested currency.
         */
        public readonly float $checkoutPrice,

        /**
         * ISO 4217 currency code of the checkout price.
         */
        public readonly string $checkoutCurrency,
    ) {
    }

    /**
     * Convert to an array for JSON serialization.
     *
     * @return array{checkoutPrice: float, checkoutCurrency: string}
     */
    public function toArray(): array
    {
        return [
            'checkoutPrice' => round($this->checkoutPrice, 2),
            'checkoutCurrency' => $this->checkoutCurrency,
        ];
    }
}