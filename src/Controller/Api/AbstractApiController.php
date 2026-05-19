<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController
{
    protected function notImplemented(): JsonResponse
    {
        return new JsonResponse(
            ['message' => 'Not implemented'],
            Response::HTTP_NOT_IMPLEMENTED,
        );
    }
}
