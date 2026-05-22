<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartSummaryControllerTest extends WebTestCase
{
    public function testSummarizeCartWithMixedCurrencies(): void
    {
        // Skip integration test if database is not available
        if (!extension_loaded('pdo_mysql')) {
            self::markTestSkipped('MySQL PDO extension not available');
        }

        $client = static::createClient();

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

        $client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertArrayHasKey('checkoutPrice', $response);
        self::assertArrayHasKey('checkoutCurrency', $response);
        self::assertEquals('EUR', $response['checkoutCurrency']);
        // 49.99 EUR + (36 USD * 0.89475) = 49.99 + 32.211 = 82.201 ≈ 82.20
        self::assertEqualsWithDelta(82.20, $response['checkoutPrice'], 0.01);
    }

    public function testSummarizeCartWithSameCurrency(): void
    {
        $client = static::createClient();

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

        $client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertEquals('USD', $response['checkoutCurrency']);
        self::assertEquals(35.00, $response['checkoutPrice']);
    }

    public function testSummarizeCartWithEmptyItems(): void
    {
        // Empty items array triggers validation error (Cart items are required)
        $client = static::createClient();

        $payload = [
            'items' => [],
            'checkoutCurrency' => 'USD',
        ];

        $client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        // Empty items array is rejected by validation
        self::assertResponseStatusCodeSame(422);
    }

    public function testSummarizeCartReturns400ForInvalidMethod(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/cart/summary');
        self::assertResponseStatusCodeSame(405);
    }

    public function testSummarizeCartReturns400ForMalformedJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/cart/summary',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json',
        );

        self::assertResponseStatusCodeSame(400);
    }
}