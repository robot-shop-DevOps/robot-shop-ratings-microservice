<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use robotshop\ratings\Controller\HealthController;
use robotshop\ratings\Service\HealthCheckService;
use Symfony\Component\HttpFoundation\Request;

class HealthControllerTest extends TestCase
{
    public function testHealthCheckSuccess()
    {
        // mock service
        $service = $this->createMock(HealthCheckService::class);
        $service->method('checkConnectivity')->willReturn(true);

        // create controller and attach a logger mock
        $controller = new HealthController($service);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $controller->setLogger($logger);

        $request = Request::create('/_health');
        $response = $controller($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('"pdo_connectivity":true', $response->getContent());
    }

    public function testHealthCheckFailure()
    {
        $service = $this->createMock(HealthCheckService::class);
        $service->method('checkConnectivity')->willThrowException(new \PDOException());

        $controller = new HealthController($service);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $controller->setLogger($logger);

        $request = Request::create('/_health');
        $response = $controller($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('"pdo_connectivity":false', $response->getContent());
    }
}