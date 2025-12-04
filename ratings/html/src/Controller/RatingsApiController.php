<?php

declare(strict_types=1);

namespace robotshop\ratings\Controller;

use robotshop\ratings\Service\CatalogueService;
use robotshop\ratings\Service\RatingsService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RatingsApiController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RatingsService $ratingsService;
    private CatalogueService $catalogueService;
    private string $jwtSecret;

    public function __construct(
        CatalogueService $catalogueService,
        RatingsService $ratingsService,
        string $jwtSecret
    ) {
        $this->ratingsService = $ratingsService;
        $this->catalogueService = $catalogueService;
        $this->jwtSecret = $jwtSecret;
    }

    /**
     * @Route(path="/rate/{sku}/{score}", methods={"PUT"})
     */
    public function put(Request $request, string $sku, int $score): Response
    {
        // --- JWT CHECK ---
        $auth = $request->headers->get('Authorization');

        if (!$auth || strpos($auth, 'Bearer ') !== 0) {
            return new JsonResponse(['error' => 'Missing or invalid Authorization header'], 401);
        }

        $token = substr($auth, 7);

        try {
            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->jwtSecret, 'HS256')
            );

            // Optional: Use user info
            // $username = $decoded->name;

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid or expired token'], 403);
        }

        // --- SCORE SANITIZATION ---
        $score = min(max(1, $score), 5);

        // --- SKU VALIDATION ---
        try {
            if (false === $this->catalogueService->checkSKU($sku)) {
                throw new NotFoundHttpException("$sku not found");
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage(), $e);
        }

        // --- UPDATE RATING ---
        try {
            $rating = $this->ratingsService->ratingBySku($sku);

            if (0 === $rating['avg_rating']) {
                // First rating
                $this->ratingsService->addRatingForSKU($sku, $score);
            } else {
                // Update average
                $newAvg = (($rating['avg_rating'] * $rating['rating_count']) + $score)
                    / ($rating['rating_count'] + 1);

                $this->ratingsService
                    ->updateRatingForSKU($sku, $newAvg, $rating['rating_count'] + 1);
            }

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            throw new HttpException(500, 'Unable to update rating', $e);
        }
    }

    /**
     * @Route("/fetch/{sku}", methods={"GET"})
     */
    public function get(Request $request, string $sku): Response
    {
        try {
            $rating = $this->ratingsService->ratingBySku($sku);

            if (!$rating) {
                throw new NotFoundHttpException("$sku not found");
            }
        } catch (\Exception $e) {
            throw new HttpException(500, $e->getMessage(), $e);
        }

        return new JsonResponse($rating);
    }
}