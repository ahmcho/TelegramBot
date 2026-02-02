<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api;

use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\HttpMethod;

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
        private readonly BulkOperationManager $bulkManager
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return mixed The API response (can be array, int, string, bool, etc.)
     */
    public function call(ApiMethod $method, array $params = []): mixed
    {
        $url = $this->config->getFullApiUrl() . $method->value;

        return $this->httpClient->request(HttpMethod::POST, $url, $params);
    }

    public function getBulkManager(): BulkOperationManager
    {
        return $this->bulkManager;
    }

    public function getConfig(): BotConfig
    {
        return $this->config;
    }
}
