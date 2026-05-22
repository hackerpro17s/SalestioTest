<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for cart summary request.
 *
 * @see https://openapi.example.com/#/cart/summarizeCart
 */
final class CartSummaryRequest
{
    /**
     * @param array<string, CartItem> $items Map of product ID => cart item
     */
    public function __construct(
        /**
         * Cart items keyed by product ID.
         *
         * @var array<int, CartItem>
         */
        #[Assert\NotBlank(message: 'Cart items are required')]
        public readonly array $items,

        /**
         * ISO 4217 currency code to convert the total to (e.g. EUR, USD, JPY).
         */
        #[Assert\NotBlank(message: 'Checkout currency is required')]
        #[Assert\Choice(
            choices: ['USD', 'EUR', 'JPY'],
            message: 'Checkout currency must be one of: USD, EUR, JPY'
        )]
        public readonly string $checkoutCurrency,
    ) {
    }
}