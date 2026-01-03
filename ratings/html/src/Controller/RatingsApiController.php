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
        $this->ratingsService   = $ratingsService;
        $this->catalogueService = $catalogueService;
        $this->jwtSecret        = $jwtSecret;
    }

    /**
     * @Route(path="/rate/{sku}/{score}", methods={"PUT"})
     */
    public function put(Request $request, string $sku, int $score): Response
    {
        /* -------------------------
           Auth
        --------------------------*/
        $auth = $request->headers->get('Authorization');

        if (!$auth || strpos($auth, 'Bearer ') !== 0) {
            $this->logger->warning('missing or invalid authorization header', [
                'service'    => 'ratings',
                'error_type' => 'AUTH_HEADER_INVALID',
                'sku'        => $sku,
            ]);

            return new JsonResponse(
                ['error' => 'Missing or invalid Authorization header'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = substr($auth, 7);

        try {
            \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->jwtSecret, 'HS256')
            );
        } catch (\Exception $e) {
            $this->logger->warning('invalid or expired token', [
                'service'    => 'ratings',
                'error_type' => 'TOKEN_INVALID',
                'sku'        => $sku,
                'exception' => get_class($e),
            ]);

            return new JsonResponse(
                ['error' => 'Invalid or expired token'],
                Response::HTTP_FORBIDDEN
            );
        }

        /* -------------------------
           Score sanitization
        --------------------------*/
        $score = min(max(1, $score), 5);

        /* -------------------------
           SKU validation
        --------------------------*/
        try {
            if (false === $this->catalogueService->checkSKU($sku)) {
                $this->logger->warning('sku not found', [
                    'service'    => 'ratings',
                    'error_type' => 'SKU_NOT_FOUND',
                    'sku'        => $sku,
                ]);

                throw new NotFoundHttpException("$sku not found");
            }
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('catalogue service failure', [
                'service'    => 'ratings',
                'dependency' => 'catalogue',
                'error_type' => 'DEPENDENCY_FAILURE',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw new HttpException(500, 'Catalogue service unavailable', $e);
        }

        /* -------------------------
           Update rating
        --------------------------*/
        try {
            $rating = $this->ratingsService->ratingBySku($sku);

            if (0 === $rating['avg_rating']) {
                $this->ratingsService->addRatingForSKU($sku, $score);
            } else {
                $newAvg = (($rating['avg_rating'] * $rating['rating_count']) + $score)
                    / ($rating['rating_count'] + 1);

                $this->ratingsService->updateRatingForSKU(
                    $sku,
                    $newAvg,
                    $rating['rating_count'] + 1
                );
            }

            return new JsonResponse(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error('failed to update rating', [
                'service'    => 'ratings',
                'error_type' => 'RATING_UPDATE_FAILED',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

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
                $this->logger->warning('rating not found', [
                    'service'    => 'ratings',
                    'error_type' => 'RATING_NOT_FOUND',
                    'sku'        => $sku,
                ]);

                throw new NotFoundHttpException("$sku not found");
            }

            return new JsonResponse($rating);

        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('failed to fetch rating', [
                'service'    => 'ratings',
                'error_type' => 'FETCH_RATING_FAILED',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw new HttpException(500, 'Unable to fetch rating', $e);
        }
    }
}