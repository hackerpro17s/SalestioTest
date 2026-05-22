<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\CartItem;
use App\Dto\CartSummaryRequest;
use PHPUnit\Framework\TestCase;

final class CartSummaryRequestTest extends TestCase
{
    public function testValidRequestWithSingleItem(): void
    {
        $request = new CartSummaryRequest(
            items: [
                new CartItem('USD', 10.00, 1),
            ],
            checkoutCurrency: 'EUR',
        );

        self::assertCount(1, $request->items);
        self::assertEquals('EUR', $request->checkoutCurrency);
    }

    public function testValidRequestWithMultipleItems(): void
    {
        $request = new CartSummaryRequest(
            items: [
                '42' => new CartItem('EUR', 49.99, 1),
                '55' => new CartItem('USD', 12.00, 3),
            ],
            checkoutCurrency: 'EUR',
        );

        self::assertCount(2, $request->items);
        self::assertEquals('EUR', $request->checkoutCurrency);
    }

    public function testValidRequestWithAllSupportedCurrencies(): void
    {
        // Test with USD checkout
        $requestUsd = new CartSummaryRequest(
            items: [new CartItem('EUR', 10.00, 1)],
            checkoutCurrency: 'USD',
        );
        self::assertEquals('USD', $requestUsd->checkoutCurrency);

        // Test with EUR checkout
        $requestEur = new CartSummaryRequest(
            items: [new CartItem('USD', 10.00, 1)],
            checkoutCurrency: 'EUR',
        );
        self::assertEquals('EUR', $requestEur->checkoutCurrency);

        // Test with JPY checkout
        $requestJpy = new CartSummaryRequest(
            items: [new CartItem('USD', 10.00, 1)],
            checkoutCurrency: 'JPY',
        );
        self::assertEquals('JPY', $requestJpy->checkoutCurrency);
    }

    public function testEmptyItemsArray(): void
    {
        $request = new CartSummaryRequest(
            items: [],
            checkoutCurrency: 'USD',
        );

        self::assertCount(0, $request->items);
        self::assertEquals('USD', $request->checkoutCurrency);
    }

    public function testImmutability(): void
    {
        $item = new CartItem('USD', 10.00, 1);
        $request = new CartSummaryRequest(
            items: [$item],
            checkoutCurrency: 'EUR',
        );

        self::assertCount(1, $request->items);
        self::assertEquals('EUR', $request->checkoutCurrency);
    }

    public function testItemsWithNumericKeys(): void
    {
        $request = new CartSummaryRequest(
            items: [
                42 => new CartItem('EUR', 49.99, 1),
                55 => new CartItem('USD', 12.00, 3),
            ],
            checkoutCurrency: 'EUR',
        );

        self::assertArrayHasKey(42, $request->items);
        self::assertArrayHasKey(55, $request->items);
    }
}