<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bulk;

use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\HttpMethod;

class BulkOperationManager
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly BotConfig $config
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

        $url = $this->config->getFullApiUrl() . $method->value;
        $rawResults = $this->httpClient->requestMulti(
            HttpMethod::POST,
            $url,
            $requestsArray,
            $options
        );

        $result = BulkResult::fromRawResults($rawResults);
        // Throw exception if configured and there are failures
        if ($this->config->shouldThrowExceptions() && $result->hasFailures()) {
            throw new BulkSendException(
                "Bulk operation completed with {$result->failed} failures out of {$result->total}",
                $result
            );
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

        $requestsArray = array_map(
            fn($chatId) => [...$commonParams, 'chat_id' => $chatId],
            $chatIds
        );

        return $this->sendBulk($method, $requestsArray, $options);
    }
}
