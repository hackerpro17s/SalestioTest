<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart/summary', name: 'api_cart_summary', methods: ['POST'])]
final class CartSummaryController extends AbstractApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->notImplemented();
    }
}
