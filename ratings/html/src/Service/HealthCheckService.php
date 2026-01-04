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

    private ?PDO $pdo = null;
    private string $pdoUrl;
    private string $pdoUser;
    private string $pdoPassword;

    public function __construct(string $pdoUrl, string $pdoUser, string $pdoPassword)
    {
        $this->pdoUrl = $pdoUrl;
        $this->pdoUser = $pdoUser;
        $this->pdoPassword = $pdoPassword;
    }

    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->pdoUrl, $this->pdoUser, $this->pdoPassword);
        }
        return $this->pdo;
    }

    public function checkConnectivity(): bool
    {
        try {
            return $this->getPdo()
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