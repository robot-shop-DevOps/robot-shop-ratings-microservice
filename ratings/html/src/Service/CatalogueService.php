<?php

declare(strict_types=1);

namespace robotshop\ratings\Service;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class CatalogueService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    private string $catalogueUrl;

    public function __construct(string $catalogueUrl)
    {
        $this->catalogueUrl = rtrim($catalogueUrl, '/');
    }

    public function checkSKU(string $sku): bool
    {
        $url = sprintf('%s/product/%s', $this->catalogueUrl, $sku);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
        ]);

        $data = curl_exec($curl);

        if ($data === false) {
            $this->logger->error('catalogue connection failed', [
                'service'    => 'ratings',
                'dependency' => 'catalogue',
                'error_type' => 'DEPENDENCY_DOWN',
                'sku'        => $sku,
                'curl_error' => curl_error($curl),
            ]);

            curl_close($curl);
            throw new Exception('Failed to connect to catalogue');
        }

        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        // Log ONLY unexpected failures
        if ($status >= 500) {
            $this->logger->error('catalogue service error', [
                'service'    => 'ratings',
                'dependency' => 'catalogue',
                'error_type' => 'DEPENDENCY_FAILURE',
                'sku'        => $sku,
                'status'     => $status,
            ]);

            throw new Exception('Catalogue service error');
        }

        // 200 = exists, 404 = not found (both valid outcomes)
        return $status === 200;
    }
}