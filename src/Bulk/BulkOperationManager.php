<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bulk;

use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;

class BulkOperationManager
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly BotConfig $config,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * @param array<int, array<string, mixed>> $requestsArray
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function sendBulk(
        ApiMethod $method,
        array $requestsArray,
        array $options = []
    ): BulkResult {
        if (empty($requestsArray)) {
            return BulkResult::empty();
        }

        // Log bulk operation start
        $this->logIfEnabled('info', 'Starting bulk operation', [
            'method' => $method->value,
            'request_count' => count($requestsArray),
            'options' => $options
        ]);

        $url = $this->config->getFullApiUrl() . $method->value;
        $rawResults = $this->httpClient->requestMulti(
            HttpMethod::POST,
            $url,
            $requestsArray,
            $options
        );

        $result = BulkResult::fromRawResults($rawResults);

        // Log individual failures
        if ($result->hasFailures()) {
            foreach ($result->results as $r) {
                if (!$r['success']) {
                    $this->logIfEnabled('warning', 'Bulk operation individual failure', [
                        'chat_id' => $r['chat_id'],
                        'error' => $r['error']
                    ]);
                }
            }
        }

        // Log bulk operation completion with statistics
        $this->logIfEnabled('info', 'Bulk operation completed', [
            'method' => $method->value,
            'total' => $result->total,
            'successful' => $result->successful,
            'failed' => $result->failed,
            'success_rate' => $result->total > 0 ? round(($result->successful / $result->total) * 100, 2) . '%' : 'N/A'
        ]);

        // Throw exception if configured and there are failures
        if ($this->config->shouldThrowExceptions() && $result->hasFailures()) {
            $exception = new BulkSendException(
                "Bulk operation completed with {$result->failed} failures out of {$result->total}",
                $result
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        return $result;
    }

    /**
     * @param array<int, int|string> $chatIds
     * @param array<string, mixed> $commonParams
     * @param array{max_concurrent?: int, delay_ms?: int} $options
     */
    public function broadcast(
        ApiMethod $method,
        array $chatIds,
        array $commonParams,
        array $options = []
    ): BulkResult {
        if (empty($chatIds)) {
            return BulkResult::empty();
        }

        // Log broadcast start
        $this->logIfEnabled('info', 'Starting broadcast', [
            'method' => $method->value,
            'recipient_count' => count($chatIds),
            'options' => $options
        ]);

        $requestsArray = array_map(
            fn($chatId) => [...$commonParams, 'chat_id' => $chatId],
            $chatIds
        );

        return $this->sendBulk($method, $requestsArray, $options);
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
    private function logExceptionIfEnabled(\Throwable $exception): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->logException($exception);
            } catch (\Throwable $e) {
                // Fail silently - never throw from logger
            }
        }
    }
}
