<?php

declare(strict_types=1);

namespace robotshop\ratings\Service;

use PDO;
use PDOException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class RatingsService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const QUERY_RATINGS_BY_SKU        = 'select avg_rating, rating_count from ratings where sku = ?';
    private const QUERY_UPDATE_RATINGS_BY_SKU = 'update ratings set avg_rating = ?, rating_count = ? where sku = ?';
    private const QUERY_INSERT_RATING         = 'insert into ratings(sku, avg_rating, rating_count) values(?, ?, ?)';

    /**
     * @var PDO
     */
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function ratingBySku(string $sku): array
    {
        try {
            $stmt = $this->connection->prepare(self::QUERY_RATINGS_BY_SKU);

            if (!$stmt->execute([$sku])) {
                throw new PDOException('Query execution failed');
            }

            $data = $stmt->fetch();

            if ($data) {
                // avg_rating may come as string from PDO
                $data['avg_rating'] = (float) $data['avg_rating'];
                return $data;
            }

            // Valid business outcome: no rating yet
            return ['avg_rating' => 0.0, 'rating_count' => 0];

        } catch (PDOException $e) {
            $this->logger->error('failed to fetch rating', [
                'service'    => 'ratings',
                'operation'  => 'ratingBySku',
                'error_type' => 'DB_QUERY_FAILED',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw new \Exception('Failed to query rating data', 500, $e);
        }
    }

    public function updateRatingForSKU(string $sku, float $score, int $count): void
    {
        try {
            $stmt = $this->connection->prepare(self::QUERY_UPDATE_RATINGS_BY_SKU);

            if (!$stmt->execute([$score, $count, $sku])) {
                throw new PDOException('Update execution failed');
            }

        } catch (PDOException $e) {
            $this->logger->error('failed to update rating', [
                'service'    => 'ratings',
                'operation'  => 'updateRatingForSKU',
                'error_type' => 'DB_UPDATE_FAILED',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw new \Exception('Failed to update rating', 500, $e);
        }
    }

    public function addRatingForSKU(string $sku, float $rating): void
    {
        try {
            $stmt = $this->connection->prepare(self::QUERY_INSERT_RATING);

            if (!$stmt->execute([$sku, $rating, 1])) {
                throw new PDOException('Insert execution failed');
            }

        } catch (PDOException $e) {
            $this->logger->error('failed to insert rating', [
                'service'    => 'ratings',
                'operation'  => 'addRatingForSKU',
                'error_type' => 'DB_INSERT_FAILED',
                'sku'        => $sku,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            throw new \Exception('Failed to insert rating', 500, $e);
        }
    }
}