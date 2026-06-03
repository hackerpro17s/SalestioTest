<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\CartItem;
use App\Dto\CartSummaryRequest;
use App\Dto\CartSummaryResponse;
use App\Entity\ExchangeRate;
use App\Repository\ExchangeRateRepository;
use App\Service\CartSummarizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CartSummarizerTest extends TestCase
{
    private ExchangeRateRepository|MockObject $exchangeRateRepository;
    private CartSummarizer $cartSummarizer;

    protected function setUp(): void
    {
        $this->exchangeRateRepository = $this->createMock(ExchangeRateRepository::class);
        $this->cartSummarizer = new CartSummarizer(
            $this->exchangeRateRepository,
            ['USD', 'EUR', 'JPY'],
        );
    }

    public function testSummarizeWithSameCurrency(): void
    {
        $this->exchangeRateRepository
            ->expects($this->never())
            ->method('findOneByCurrency');

        $request = new CartSummaryRequest(
            items: [
                new CartItem('EUR', 10.00, 2),
                new CartItem('EUR', 20.00, 1),
            ],
            checkoutCurrency: 'EUR',
        );

        $response = $this->cartSummarizer->summarize($request);

        self::assertEquals(40.00, $response->checkoutPrice);
        self::assertEquals('EUR', $response->checkoutCurrency);
    }

    public function testSummarizeWithCurrencyConversion(): void
    {
        // Set up EUR exchange rate (1 USD = 0.89475 EUR)
        $eurRate = $this->createMock(ExchangeRate::class);
        $eurRate->expects($this->once())->method('getRate')->willReturn('0.89475');

        $this->exchangeRateRepository
            ->expects(self::once())
            ->method('findOneByCurrency')
            ->with('EUR')
            ->willReturn($eurRate);

        // 36 USD should convert to ~32.21 EUR
        $request = new CartSummaryRequest(
            items: [
                new CartItem('USD', 12.00, 3),
            ],
            checkoutCurrency: 'EUR',
        );

        $response = $this->cartSummarizer->summarize($request);

        self::assertEqualsWithDelta(32.21, $response->checkoutPrice, 0.01);
        self::assertEquals('EUR', $response->checkoutCurrency);
    }

    public function testSummarizeWithMixedCurrencies(): void
    {
        // Example from LLM_HISTORY.md:
        // Item 42: 49.99 EUR × 1 = 49.99 EUR (no conversion needed)
        // Item 55: 12 USD × 3 = 36 USD → convert to EUR
        // Expected total: 82.18 EUR

        // Set up EUR exchange rate (1 USD = 0.89475 EUR)
        $eurRate = $this->createMock(ExchangeRate::class);
        $eurRate->expects($this->once())->method('getRate')->willReturn('0.89475');

        $this->exchangeRateRepository
            ->expects(self::once())
            ->method('findOneByCurrency')
            ->with('EUR')
            ->willReturn($eurRate);

        $request = new CartSummaryRequest(
            items: [
                '42' => new CartItem('EUR', 49.99, 1),
                '55' => new CartItem('USD', 12.00, 3),
            ],
            checkoutCurrency: 'EUR',
        );

        $response = $this->cartSummarizer->summarize($request);

        // 49.99 EUR + (36 USD * 0.89475) = 49.99 + 32.211 = 82.201 ≈ 82.20
        self::assertEqualsWithDelta(82.20, $response->checkoutPrice, 0.01);
        self::assertEquals('EUR', $response->checkoutCurrency);
    }

    public function testSummarizeConvertsToUsd(): void
    {
        // Set up EUR exchange rate (1 USD = 0.89475 EUR)
        $eurRate = $this->createMock(ExchangeRate::class);
        $eurRate->expects($this->once())->method('getRate')->willReturn('0.89475');

        $this->exchangeRateRepository
            ->expects(self::once())
            ->method('findOneByCurrency')
            ->with('EUR')
            ->willReturn($eurRate);

        // Convert 89.475 EUR to USD (should be 100 USD)
        $request = new CartSummaryRequest(
            items: [
                new CartItem('EUR', 89.475, 1),
            ],
            checkoutCurrency: 'USD',
        );

        $response = $this->cartSummarizer->summarize($request);

        self::assertEqualsWithDelta(100.00, $response->checkoutPrice, 0.01);
        self::assertEquals('USD', $response->checkoutCurrency);
    }

    public function testSummarizeWithJpy(): void
    {
        // Set up JPY exchange rate (1 USD = 150 JPY)
        $jpyRate = $this->createMock(ExchangeRate::class);
        $jpyRate->expects($this->once())->method('getRate')->willReturn('150');

        $this->exchangeRateRepository
            ->expects(self::once())
            ->method('findOneByCurrency')
            ->with('JPY')
            ->willReturn($jpyRate);

        // Convert 100 USD to JPY (should be 15000 JPY)
        $request = new CartSummaryRequest(
            items: [
                new CartItem('USD', 100.00, 1),
            ],
            checkoutCurrency: 'JPY',
        );

        $response = $this->cartSummarizer->summarize($request);

        self::assertEquals(15000.00, $response->checkoutPrice);
        self::assertEquals('JPY', $response->checkoutCurrency);
    }

    public function testSummarizeThrowsExceptionForMissingRate(): void
    {
        $this->exchangeRateRepository
            ->expects(self::once())
            ->method('findOneByCurrency')
            ->with('EUR')
            ->willReturn(null);

        $request = new CartSummaryRequest(
            items: [
                new CartItem('EUR', 10.00, 1),
            ],
            checkoutCurrency: 'USD',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Exchange rate for currency "EUR" not found');

        $this->cartSummarizer->summarize($request);
    }

    public function testSummarizeThrowsExceptionForUnsupportedCurrency(): void
    {
        $this->exchangeRateRepository
            ->expects($this->never())
            ->method('findOneByCurrency');

        $request = new CartSummaryRequest(
            items: [
                new CartItem('GBP', 10.00, 1),
            ],
            checkoutCurrency: 'USD',
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency "GBP" is not supported');

        $this->cartSummarizer->summarize($request);
    }

    public function testSummarizeWithEmptyCart(): void
    {
        $this->exchangeRateRepository
            ->expects($this->never())
            ->method('findOneByCurrency');

        $request = new CartSummaryRequest(
            items: [],
            checkoutCurrency: 'USD',
        );

        $response = $this->cartSummarizer->summarize($request);

        self::assertEquals(0.00, $response->checkoutPrice);
        self::assertEquals('USD', $response->checkoutCurrency);
    }
}