<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Contract\CartSummarizerInterface;
use App\Dto\CartItem;
use App\Dto\CartSummaryRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/api/cart/summary', name: 'api_cart_summary', methods: ['POST'])]
final class CartSummaryController extends AbstractApiController
{
    public function __invoke(
        Request $request,
        CartSummarizerInterface $cartSummarizer,
        #[MapRequestPayload]
        CartSummaryRequest $dto,
    ): JsonResponse {
        // Deserialize items array into CartItem objects
        $items = [];
        foreach ($dto->items as $productId => $itemData) {
            $items[$productId] = new CartItem(
                currency: $itemData->currency,
                price: $itemData->price,
                quantity: $itemData->quantity,
            );
        }

        // Create a new CartSummaryRequest with properly typed items
        $cartSummaryRequest = new CartSummaryRequest(
            items: $items,
            checkoutCurrency: $dto->checkoutCurrency,
        );

        $response = $cartSummarizer->summarize($cartSummaryRequest);

        return new JsonResponse($response->toArray());
    }
}