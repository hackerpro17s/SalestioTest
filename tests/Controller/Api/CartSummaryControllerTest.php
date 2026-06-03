<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\ExchangeRate\ExchangeRateSyncService;
use App\ExchangeRate\OpenExchangeRatesClient;
use App\Repository\ExchangeRateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CartSummaryControllerTest extends WebTestCase
{
    private ExchangeRateSyncService $syncService;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        //parent::setUp();

        $this->client = self::createClient();
        
        // Get the sync service from container
        //self::bootKernel();
        $this->syncService = $this->mockSyncService([
            'EUR' => '0.89475',
            'JPY' => '150.00',
        ]);

        $this->syncService->sync();
    }

    private function mockSyncService(array $rates): ExchangeRateSyncService
    {
        // Create a mock HTTP client with the expected Open Exchange Rates response
        $mockResponse = new MockResponse(json_encode([
            'disclaimer' => 'Usage subject to terms: https://openexchangerates.org/terms',
            'license' => 'https://openexchangerates.org/license',
            'timestamp' => time(),
            'base' => 'USD',
            'rates' => $rates,
        ]), [
            'http_code' => 200,
            'response_headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        $mockHttpClient = new MockHttpClient($mockResponse);

        return new ExchangeRateSyncService(
            new OpenExchangeRatesClient($mockHttpClient, $_ENV['OPENEXCHANGERATES_APP_ID']),
            self::getContainer()->get(ExchangeRateRepository::class),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->getParameter('app.supported_currencies'),
        );
    }

    public function testSummarizeCartWithMixedCurrencies(): void
    {
        // Example from LLM_HISTORY.md
        $payload = [
            'items' => [
                '42' => [
                    'currency' => 'EUR',
                    'price' => 49.99,
                    'quantity' => 1,
                ],
                '55' => [
                    'currency' => 'USD',
                    'price' => 12.00,
                    'quantity' => 3,
                ],
            ],
            'checkoutCurrency' => 'EUR',
        ];

        $this->client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('checkoutPrice', $response);
        self::assertArrayHasKey('checkoutCurrency', $response);
        self::assertEquals('EUR', $response['checkoutCurrency']);
        // 49.99 EUR + (36 USD * 0.89475) = 49.99 + 32.211 = 82.201 ≈ 82.20
        self::assertEqualsWithDelta(82.20, $response['checkoutPrice'], 0.01);
    }

    public function testSummarizeCartWithSameCurrency(): void
    {
        $payload = [
            'items' => [
                '1' => [
                    'currency' => 'USD',
                    'price' => 10.00,
                    'quantity' => 2,
                ],
                '2' => [
                    'currency' => 'USD',
                    'price' => 15.00,
                    'quantity' => 1,
                ],
            ],
            'checkoutCurrency' => 'USD',
        ];

        $this->client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertEquals('USD', $response['checkoutCurrency']);
        self::assertEquals(35.00, $response['checkoutPrice']);
    }

    public function testSummarizeCartConvertsToJpy(): void
    {
        $payload = [
            'items' => [
                '1' => [
                    'currency' => 'USD',
                    'price' => 100.00,
                    'quantity' => 1,
                ],
            ],
            'checkoutCurrency' => 'JPY',
        ];

        $this->client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        self::assertEquals('JPY', $response['checkoutCurrency']);
        self::assertEquals(15000.00, $response['checkoutPrice']);
    }
}