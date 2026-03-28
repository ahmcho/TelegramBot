<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Bulk\BulkResult;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

/**
 * Message Service
 *
 * Handles all message-related Telegram API operations
 */
class MessageService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    /**
     * Auto-escape text and caption for MarkdownV2 format
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function escapeForMarkdownV2(array $params): array
    {
        if (!isset($params['parse_mode']) || $params['parse_mode'] !== 'MarkdownV2') {
            return $params;
        }

        $formatter = new MarkdownV2Formatter();

        if (isset($params['text']) && is_string($params['text'])) {
            $params['text'] = $formatter->escape($params['text']);
        }

        if (isset($params['caption']) && is_string($params['caption'])) {
            $params['caption'] = $formatter->escape($params['caption']);
        }
    
        return $params;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function send(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendRaw(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function editText(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::EDIT_MESSAGE_TEXT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function editTextRaw(array $params): array
    {
        return $this->apiService->call(ApiMethod::EDIT_MESSAGE_TEXT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function editCaption(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::EDIT_MESSAGE_CAPTION, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function editCaptionRaw(array $params): array
    {
        return $this->apiService->call(ApiMethod::EDIT_MESSAGE_CAPTION, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function delete(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::DELETE_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function forward(array $params): array
    {
        return $this->apiService->call(ApiMethod::FORWARD_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function copy(array $params): array
    {
        return $this->apiService->call(ApiMethod::COPY_MESSAGE, $params);
    }

    // Bulk operations

    /**
     * @param array<int, array<string, mixed>> $messagesArray
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function sendBulk(array $messagesArray, array $options = []): BulkResult
    {
        // Apply escaping to each message in the bulk array
        $escapedMessagesArray = array_map(
            fn($params) => $this->escapeForMarkdownV2($params),
            $messagesArray
        );

        return $this->apiService->getBulkManager()->sendBulk(
            ApiMethod::SEND_MESSAGE,
            $escapedMessagesArray,
            $options
        );
    }

    /**
     * @param array<int, array<string, mixed>> $messagesArray
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function sendBulkRaw(array $messagesArray, array $options = []): BulkResult
    {
        return $this->apiService->getBulkManager()->sendBulk(
            ApiMethod::SEND_MESSAGE,
            $messagesArray,
            $options
        );
    }

    /**
     * @param array<int, int|string> $chatIds
     * @param array<string, mixed> $commonParams
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function broadcast(
        array $chatIds,
        string $text,
        array $commonParams = [],
        array $options = []
    ): BulkResult {
        $params = [...$commonParams, 'text' => $text];

        // Apply escaping to the params before broadcasting
        $params = $this->escapeForMarkdownV2($params);

        return $this->apiService->getBulkManager()->broadcast(
            ApiMethod::SEND_MESSAGE,
            $chatIds,
            $params,
            $options
        );
    }

    /**
     * @param array<int, int|string> $chatIds
     * @param array<string, mixed> $commonParams
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function broadcastRaw(
        array $chatIds,
        string $text,
        array $commonParams = [],
        array $options = []
    ): BulkResult {
        $params = [...$commonParams, 'text' => $text];

        return $this->apiService->getBulkManager()->broadcast(
            ApiMethod::SEND_MESSAGE,
            $chatIds,
            $params,
            $options
        );
    }
}
