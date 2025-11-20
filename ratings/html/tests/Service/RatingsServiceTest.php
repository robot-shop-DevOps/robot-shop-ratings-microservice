<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use robotshop\ratings\Service\RatingsService;

class RatingsServiceTest extends TestCase
{
    public function testRatingBySkuReturnsEmptyIfNotFound()
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $service = new RatingsService($pdo);

        $result = $service->ratingBySku('ABC123');
        $this->assertEquals(['avg_rating' => 0, 'rating_count' => 0], $result);
    }

    public function testRatingBySkuReturnsData()
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn([
            'avg_rating' => '4.5',
            'rating_count' => 2
        ]);

        $service = new RatingsService($pdo);

        $result = $service->ratingBySku('ABC123');
        $this->assertSame(4.5, $result['avg_rating']);
        $this->assertSame(2, $result['rating_count']);
    }

    public function testAddRatingCallsInsert()
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->with(['ABC123', 5, 1])->willReturn(true);

        $service = new RatingsService($pdo);
        $service->addRatingForSKU('ABC123', 5);

        $this->assertTrue(true); // no exception
    }

    public function testUpdateRatingCallsUpdate()
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);

        $pdo->method('prepare')->willReturn($stmt);
        $stmt->method('execute')->with([4.7, 5, 'ABC123'])->willReturn(true);

        $service = new RatingsService($pdo);
        $service->updateRatingForSKU('ABC123', 4.7, 5);

        $this->assertTrue(true);
    }
}