<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Contract\CartSummarizerInterface;
use App\Dto\CartSummaryRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/cart/summary', name: 'api_cart_summary', methods: ['POST'])]
final class CartSummaryController extends AbstractApiController
{
    public function __invoke(
        Request $request,
        CartSummarizerInterface $cartSummarizer,
        #[MapRequestPayload(
            deserializationContext: [AbstractNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
        )]
        CartSummaryRequest $dto,
    ): JsonResponse {
        // Deserialize items array into CartItem objects
        $items = [];
        foreach ($dto->items as $productId => $itemData) {
            if (is_array($itemData)) {
                $items[$productId] = new \App\Dto\CartItem(
                    currency: $itemData['currency'],
                    price: (float) $itemData['price'],
                    quantity: (int) $itemData['quantity'],
                );
            }
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