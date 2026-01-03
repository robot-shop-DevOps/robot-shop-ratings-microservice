<?php

declare(strict_types=1);

namespace robotshop\ratings\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_live")
 */
class LivenessController
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            [
                'status'  => 'UP',
                'service' => 'ratings',
            ],
            Response::HTTP_OK
        );
    }
}