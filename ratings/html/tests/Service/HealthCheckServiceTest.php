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

        $service = new HealthCheckService(
            'mysql:host=localhost;dbname=test',
            'test_user',
            'test_password'
        );

        $reflection = new \ReflectionClass($service);
        $pdoProperty = $reflection->getProperty('pdo');
        $pdoProperty->setAccessible(true);
        $pdoProperty->setValue($service, $pdo);

        $this->assertTrue($service->checkConnectivity());
    }
}