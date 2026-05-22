<?php

declare(strict_types=1);

namespace App\Contract;

use App\Dto\CartSummaryRequest;
use App\Dto\CartSummaryResponse;

/**
 * Summarizes a cart and converts line amounts using stored exchange rates.
 */
interface CartSummarizerInterface
{
    /**
     * Calculate the cart total and convert to the requested currency.
     */
    public function summarize(CartSummaryRequest $request): CartSummaryResponse;
}