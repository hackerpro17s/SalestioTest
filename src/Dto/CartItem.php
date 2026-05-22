<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for a single cart item.
 */
final class CartItem
{
    public function __construct(
        /**
         * ISO 4217 currency code for this item's price (e.g. EUR, USD, JPY).
         */
        #[Assert\NotBlank(message: 'Item currency is required')]
        #[Assert\Choice(
            choices: ['USD', 'EUR', 'JPY'],
            message: 'Item currency must be one of: USD, EUR, JPY'
        )]
        public readonly string $currency,

        /**
         * Unit price of the item in the given currency.
         */
        #[Assert\NotBlank(message: 'Item price is required')]
        #[Assert\Positive(message: 'Item price must be a positive number')]
        #[Assert\Type(type: 'numeric', message: 'Item price must be a number')]
        public readonly float $price,

        /**
         * Quantity of this item in the cart.
         */
        #[Assert\NotBlank(message: 'Item quantity is required')]
        #[Assert\Positive(message: 'Item quantity must be a positive integer')]
        #[Assert\Type(type: 'integer', message: 'Item quantity must be an integer')]
        public readonly int $quantity,
    ) {
    }

    /**
     * Calculate the total for this line item (price × quantity).
     */
    public function getLineTotal(): float
    {
        return $this->price * $this->quantity;
    }
}