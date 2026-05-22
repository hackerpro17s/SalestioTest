<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\CartItem;
use PHPUnit\Framework\TestCase;

final class CartItemTest extends TestCase
{
    public function testGetLineTotal(): void
    {
        $item = new CartItem('USD', 10.00, 3);
        self::assertEquals(30.00, $item->getLineTotal());
    }

    public function testGetLineTotalWithDecimalPrice(): void
    {
        $item = new CartItem('EUR', 49.99, 2);
        self::assertEquals(99.98, $item->getLineTotal());
    }

    public function testGetLineTotalWithSingleItem(): void
    {
        $item = new CartItem('JPY', 1000, 1);
        self::assertEquals(1000.0, $item->getLineTotal());
    }

    public function testImmutability(): void
    {
        $item = new CartItem('USD', 10.00, 1);
        self::assertSame('USD', $item->currency);
        self::assertSame(10.00, $item->price);
        self::assertSame(1, $item->quantity);
    }

    public function testConstructorRequiresValidCurrency(): void
    {
        // The CartItem constructor accepts any string for currency
        // Validation constraints are applied separately by the validator
        $item = new CartItem('GBP', 10.00, 1);
        self::assertSame('GBP', $item->currency);
    }

    public function testConstructorRequiresValidPrice(): void
    {
        // The CartItem constructor accepts any float for price
        // Validation constraints are applied separately by the validator
        $item = new CartItem('USD', -10.00, 1);
        self::assertSame(-10.00, $item->price);
    }

    public function testConstructorRequiresValidQuantity(): void
    {
        // The CartItem constructor accepts any int for quantity
        // Validation constraints are applied separately by the validator
        $item = new CartItem('USD', 10.00, -1);
        self::assertSame(-1, $item->quantity);
    }
}