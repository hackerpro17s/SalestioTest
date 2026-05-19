<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/currency/convert', name: 'api_currency_convert', methods: ['POST'])]
final class CurrencyConvertController extends AbstractApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->notImplemented();
    }
}
