<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;

interface HttpClientInterface
{
    /**
     * Execute an HTTP request
     *
     * @param HttpMethod $method The HTTP method
     * @param string $url The URL to request
     * @param array<string, mixed> $params Request parameters
     * @return mixed The parsed response (can be array, int, string, bool, etc.)
     * @throws HttpClientException On HTTP errors
     */
    public function request(
        HttpMethod $method,
        string $url,
        array $params = []
    ): mixed;

    /**
     * Execute multiple requests in parallel
     *
     * @param HttpMethod $method The HTTP method
     * @param string $url The base URL
     * @param array<array<string, mixed>> $requestsArray Array of request parameter arrays
     * @param array{max_concurrent?: int, delay_ms?: int} $options Options for batch execution
     * @return array<int, array{success: bool, chat_id: mixed, message_id: mixed|null, data: array<string, mixed>|null, error: string|null}>
     */
    public function requestMulti(
        HttpMethod $method,
        string $url,
        array $requestsArray,
        array $options = []
    ): array;

    /**
     * Get the last HTTP status code
     */
    public function getLastHttpCode(): int;

    /**
     * Check if this client is available (extensions loaded, etc.)
     */
    public static function isAvailable(): bool;
}
