<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Webhook Service
 *
 * Handles all webhook-related Telegram API operations
 */
class WebhookService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function set(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_WEBHOOK, $params);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return $this->apiService->call(ApiMethod::GET_WEBHOOK_INFO);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function delete(array $params = []): mixed
    {
        return $this->apiService->call(ApiMethod::DELETE_WEBHOOK, $params);
    }
}
