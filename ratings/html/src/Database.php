<?php

declare(strict_types=1);

namespace robotshop\ratings;

use PDO;
use PDOException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Database implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $dsn;
    private string $user;
    private string $password;

    public function __construct(string $dsn, string $user, string $password)
    {
        $this->dsn      = $dsn;
        $this->user     = $user;
        $this->password = $password;
    }

    public function getConnection(): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($this->dsn, $this->user, $this->password, $options);

        } catch (PDOException $e) {
            $this->logger->error('database connection failed', [
                'service'    => 'ratings',
                'dependency' => 'database',
                'error_type' => 'DEPENDENCY_DOWN',
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
                'dsn'        => $this->sanitizeDsn($this->dsn),
            ]);

            throw $e;
        }
    }

    private function sanitizeDsn(string $dsn): string
    {
        return preg_replace('/password=[^;]+/', 'password=***', $dsn);
    }
}