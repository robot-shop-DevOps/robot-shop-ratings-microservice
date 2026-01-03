<?php

declare(strict_types=1);

namespace robotshop\ratings\Controller;

use robotshop\ratings\Service\HealthCheckService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_health")
 */
class HealthController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var HealthCheckService
     */
    private $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    public function __invoke(Request $request)
    {
        $checks = [
            'pdo_connectivity' => true,
        ];

        try {
            $this->healthCheckService->checkConnectivity();
        } catch (\PDOException $e) {
            $checks['pdo_connectivity'] = false;

            $this->logger->warning('health check failed', [
                'service'     => 'ratings',
                'dependency'  => 'database',
                'error_type'  => 'DEPENDENCY_DOWN',
                'exception'   => get_class($e),
                'message'     => $e->getMessage(),
            ]);
        }

        return new JsonResponse(
            $checks,
            $checks['pdo_connectivity']
                ? Response::HTTP_OK
                : Response::HTTP_BAD_REQUEST
        );
    }
}