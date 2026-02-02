<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Helpers;

use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;

/**
 * Mock HTTP Client for Testing
 *
 * Implements HttpClientInterface with configurable responses.
 * Records requests for assertions in tests.
 */
class MockHttpClient implements HttpClientInterface
{
    private int $lastHttpCode = 200;

    /**
     * @var array<array{response: mixed, exception: \Exception|null, http_code: int}>
     */
    private array $responses = [];

    /**
     * @var array<array{method: HttpMethod, url: string, params: array<string, mixed>}>
     */
    private array $requests = [];

    private int $responseIndex = 0;

    /**
     * Set a predefined response for the next request
     *
     * @param array<string, mixed> $response The response data
     * @param int $http_code The HTTP status code
     */
    public function setResponse(array $response, int $http_code = 200): void
    {
        $this->responses[] = [
            'response' => $response,
            'exception' => null,
            'http_code' => $http_code
        ];
    }

    /**
     * Set a predefined integer response for the next request
     * Used for methods like getChatMemberCount that return int
     *
     * @param int $value The integer response value
     * @param int $http_code The HTTP status code
     */
    public function setIntResponse(int $value, int $http_code = 200): void
    {
        $this->responses[] = [
            'response' => $value,
            'exception' => null,
            'http_code' => $http_code
        ];
    }

    /**
     * Set a predefined boolean response for the next request
     * Used for methods like deleteMessage, setWebhook that return bool
     *
     * @param bool $value The boolean response value
     * @param int $http_code The HTTP status code
     */
    public function setBoolResponse(bool $value, int $http_code = 200): void
    {
        $this->responses[] = [
            'response' => $value,
            'exception' => null,
            'http_code' => $http_code
        ];
    }

    /**
     * Set a predefined string response for the next request
     * Used for methods that return string values
     *
     * @param string $value The string response value
     * @param int $http_code The HTTP status code
     */
    public function setStringResponse(string $value, int $http_code = 200): void
    {
        $this->responses[] = [
            'response' => $value,
            'exception' => null,
            'http_code' => $http_code
        ];
    }

    /**
     * Set a predefined exception for the next request
     */
    public function setException(\Exception $exception, int $http_code = 0): void
    {
        $this->responses[] = [
            'response' => null,
            'exception' => $exception,
            'http_code' => $http_code
        ];
    }

    /**
     * Set multiple responses at once
     *
     * @param array<int, array{response: mixed, exception: \Exception|null, http_code: int}> $responses
     */
    public function setResponses(array $responses): void
    {
        $this->responses = array_merge($this->responses, $responses);
    }

    /**
     * Get all recorded requests
     *
     * @return array<array{method: HttpMethod, url: string, params: array<string, mixed>}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get the last request
     *
     * @return array{method: HttpMethod, url: string, params: array<string, mixed>}|null
     */
    public function getLastRequest(): ?array
    {
        return $this->requests[array_key_last($this->requests)] ?? null;
    }

    /**
     * Clear all recorded requests
     */
    public function clearRequests(): void
    {
        $this->requests = [];
    }

    /**
     * Get the number of requests made
     */
    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    /**
     * Reset the mock state
     */
    public function reset(): void
    {
        $this->responses = [];
        $this->requests = [];
        $this->responseIndex = 0;
        $this->lastHttpCode = 200;
    }

    /**
     * Execute an HTTP request
     *
     * @param HttpMethod $method The HTTP method
     * @param string $url The URL to request
     * @param array<string, mixed> $params Request parameters
     * @return mixed The parsed response (can be array, int, bool, string, etc.)
     * @throws \RuntimeException If no response is configured
     * @throws \Exception If an exception response is configured
     */
    public function request(
        HttpMethod $method,
        string $url,
        array $params = []
    ): mixed {
        // Record the request
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'params' => $params
        ];

        // Check if we have a response configured
        if (!isset($this->responses[$this->responseIndex])) {
            throw new \RuntimeException(
                'No response configured for request #' . ($this->responseIndex + 1) .
                '. Use setResponse() or setException() before calling request().'
            );
        }

        $responseConfig = $this->responses[$this->responseIndex];
        $this->lastHttpCode = $responseConfig['http_code'];
        $this->responseIndex++;

        // Throw exception if configured
        if ($responseConfig['exception'] !== null) {
            throw $responseConfig['exception'];
        }

        return $responseConfig['response'];
    }

    public function requestMulti(
        HttpMethod $method,
        string $url,
        array $requestsArray,
        array $options = []
    ): array {
        $results = [];

        foreach ($requestsArray as $index => $params) {
            try {
                $data = $this->request($method, $url, $params);
                $chatId = $params['chat_id'] ?? 'unknown';

                $results[$index] = [
                    'success' => true,
                    'chat_id' => $chatId,
                    'message_id' => $data['message_id'] ?? null,
                    'data' => $data,
                    'error' => null
                ];
            } catch (\Exception $e) {
                $chatId = $params['chat_id'] ?? 'unknown';
                $results[$index] = [
                    'success' => false,
                    'chat_id' => $chatId,
                    'message_id' => null,
                    'data' => null,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    public static function isAvailable(): bool
    {
        return true; // Always available for testing
    }

    /**
     * Get the current response index
     */
    public function getResponseIndex(): int
    {
        return $this->responseIndex;
    }

    /**
     * Set the response index (for rewinding to specific responses)
     */
    public function setResponseIndex(int $index): void
    {
        if ($index < 0 || $index > count($this->responses)) {
            throw new \InvalidArgumentException("Invalid response index: $index");
        }
        $this->responseIndex = $index;
    }
}
