<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;

class StreamHttpClient implements HttpClientInterface
{
    private int $lastHttpCode = 0;

    public function __construct(
        private readonly BotConfig $config,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Execute an HTTP request
     *
     * @param HttpMethod $method The HTTP method
     * @param string $url The URL to request
     * @param array<string, mixed> $params Request parameters
     * @return mixed The parsed response (can be array, int, bool, string, etc.)
     *               - array: Most API responses (message objects, chat objects, etc.)
     *               - int: getChatMemberCount, etc.
     *               - bool: deleteMessage, setWebhook, sendChatAction, etc.
     * @throws HttpClientException On HTTP errors
     */
    public function request(
        HttpMethod $method,
        string $url,
        array $params = []
    ): mixed {
        if (!extension_loaded('openssl')) {
            $exception = new HttpClientException(
                'OpenSSL extension is not enabled. Please enable extension=openssl in your php.ini file.'
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        $options = [
            'http' => [
                'method' => $method->value,
                'header' => 'Content-Type: application/json',
                'content' => json_encode($params),
                'timeout' => $this->config->getTimeout(),
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => $this->config->shouldVerifySsl(),
                'verify_peer_name' => $this->config->shouldVerifySsl(),
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'Unknown error';
            $exception = new HttpClientException("HTTP request failed: $errorMessage");
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        // Parse HTTP status code
        if (function_exists('http_get_last_response_headers')) {
            $headers = http_get_last_response_headers();
        } else {
            $headers = $http_response_header ?? [];
        }

        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $this->lastHttpCode = (int) $matches[1];
                break;
            }
        }

        return $this->parseResponse($response);
    }

    public function requestMulti(
        HttpMethod $method,
        string $url,
        array $requestsArray,
        array $options = []
    ): array {
        throw new HttpClientException(
            'Stream HTTP client does not support parallel requests. Use CurlHttpClient instead.'
        );
    }

    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    public static function isAvailable(): bool
    {
        return extension_loaded('openssl') &&
            in_array('https', stream_get_wrappers(), true);
    }

    private function parseResponse(string $response): mixed
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $exception = new HttpClientException(
                'Invalid JSON response: ' . json_last_error_msg(),
                $this->lastHttpCode,
                $response
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        if (!$data['ok']) {
            $exception = new HttpClientException(
                "Telegram API error: " . ($data['description'] ?? 'Unknown error'),
                $this->lastHttpCode,
                $response
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        return $data['result'] ?? [];
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
