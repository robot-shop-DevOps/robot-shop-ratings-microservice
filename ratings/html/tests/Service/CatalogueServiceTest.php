<?php

declare(strict_types=1);

/**
 * IMPORTANT:
 * Override curl functions inside the same namespace as CatalogueService
 * so CatalogueService will call these test doubles (no real network).
 */
namespace robotshop\ratings\Service {
    function curl_exec($handle) {
        return \CatalogueServiceTest::$mockedCurlExec;
    }

    function curl_getinfo($handle, $option) {
        return \CatalogueServiceTest::$mockedStatus;
    }

    function curl_close($handle) {
        // no-op for test
    }
}

namespace {

use PHPUnit\Framework\TestCase;
use robotshop\ratings\Service\CatalogueService;

class CatalogueServiceTest extends TestCase
{
    public static $mockedCurlExec;
    public static $mockedStatus;

    public function testCheckSkuReturnsTrueFor200()
    {
        self::$mockedCurlExec = "{}";
        self::$mockedStatus = 200;

        $service = new CatalogueService("http://example.com");
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $service->setLogger($logger);

        $this->assertTrue($service->checkSKU("ABC123"));
    }

    public function testCheckSkuReturnsFalseFor404()
    {
        self::$mockedCurlExec = "{}";
        self::$mockedStatus = 404;

        $service = new CatalogueService("http://example.com");
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $service->setLogger($logger);

        $this->assertFalse($service->checkSKU("ABC123"));
    }

    public function testCheckSkuThrowsExceptionOnCurlFail()
    {
        self::$mockedCurlExec = false;

        $this->expectException(\Exception::class);

        $service = new CatalogueService("http://example.com");
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $service->setLogger($logger);

        $service->checkSKU("ABC123");
    }
}

}