<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api;

use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;

/**
 * Core API Service
 *
 * Central orchestration for all Telegram API calls
 */
class ApiService
{
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

    /**
     * Log message if logger is configured
     * Never throws exceptions from logging operations
     *
     * @param 'info'|'warning'|'error'|'debug' $level
     * @param array<string, mixed> $context
     */
    private function logIfEnabled(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->log($level, $message, $context);
            } catch (\Throwable $e) {
                // Fail silently - never throw from logger
            }
        }
    }

    /**
     * Log exception if logger is configured
     * Never throws exceptions from logging operations
     */
    private function logExceptionIfEnabled(\Throwable $exception, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->logException($exception, $context);
            } catch (\Throwable $e) {
                // Fail silently - never throw from logger
            }
        }
    }
}
