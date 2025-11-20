<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use robotshop\ratings\Service\HealthCheckService;

class HealthCheckServiceTest extends TestCase
{
    public function testCheckConnectivityRunsQuery()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $service = new HealthCheckService($pdo);

        $this->assertTrue($service->checkConnectivity());
    }
}