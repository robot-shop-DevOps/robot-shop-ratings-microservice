<?php

declare(strict_types=1);

namespace robotshop\ratings\Service;

use PDO;
use PDOException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HealthCheckService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PDO
     */
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function checkConnectivity(): bool
    {
        try {
            return $this->pdo
                ->prepare('SELECT 1 + 1')
                ->execute();
        } catch (PDOException $e) {

            $this->logger->error('database connectivity check failed', [
                'service'    => 'ratings',
                'dependency' => 'database',
                'error_type' => 'DEPENDENCY_DOWN',
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}