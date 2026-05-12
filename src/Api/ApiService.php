<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api;

use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Traits\LoggerHelperTrait;

/**
 * Core API Service
 *
 * Central orchestration for all Telegram API calls
 */
final class ApiService
{
    use LoggerHelperTrait;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly BotConfig $config,
        private readonly BulkOperationManager $bulkManager,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return mixed The API response (can be array, int, string, bool, etc.)
     */
    public function call(ApiMethod $method, array $params = []): mixed
    {
        $url = $this->config->getFullApiUrl() . $method->value;

        // Log API call at DEBUG level with sanitized params
        $this->logIfEnabled('debug', 'API call', [
            'method' => $method->value,
            'params' => $this->sanitizeParams($params)
        ]);

        try {
            $response = $this->httpClient->request(HttpMethod::POST, $url, $params);
            return $response;
        } catch (\Throwable $e) {
            // Log API call failure at ERROR level
            $this->logExceptionIfEnabled($e, [
                'method' => $method->value,
                'params' => $this->sanitizeParams($params)
            ]);
            throw $e;
        }
    }

    public function getBulkManager(): BulkOperationManager
    {
        return $this->bulkManager;
    }

    public function getConfig(): BotConfig
    {
        return $this->config;
    }

    /**
     * Sanitize parameters by removing sensitive data (tokens)
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function sanitizeParams(array $params): array
    {
        $sanitized = $params;
        unset($sanitized['token']);
        return $sanitized;
    }
}
