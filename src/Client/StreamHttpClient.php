<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Traits\LoggerHelperTrait;
use AhmCho\Telegram\Client\Traits\ResponseParserTrait;
use AhmCho\Telegram\Client\Traits\MultipartRequestTrait;

final class StreamHttpClient implements HttpClientInterface
{
    use LoggerHelperTrait;
    use ResponseParserTrait;
    use MultipartRequestTrait;

    private int $lastHttpCode = 0;
    private bool $parallelWarningLogged = false;

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

        if ($this->hasFileUpload($params)) {
            $multipart = $this->buildMultipartBody($params);
            $header = "Content-Type: multipart/form-data; boundary={$multipart['boundary']}";
            $content = $multipart['body'];
        } else {
            $header = 'Content-Type: application/json';
            $content = json_encode($params);
        }

        $options = [
            'http' => [
                'method' => $method->value,
                'header' => $header,
                'content' => $content,
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
        if (!$this->parallelWarningLogged) {
            $this->logIfEnabled(
                'warning',
                'StreamHttpClient does not support parallel requests; falling back to serial execution. Use CurlHttpClient for better performance.'
            );
            $this->parallelWarningLogged = true;
        }

        $delayMs = (int) ($options['delay_ms'] ?? 0);
        $results = [];
        $lastKey = array_key_last($requestsArray);

        foreach ($requestsArray as $index => $params) {
            $chatId = $params['chat_id'] ?? 'unknown';

            try {
                $data = $this->request($method, $url, $params);
                $results[$index] = [
                    'success' => true,
                    'chat_id' => $chatId,
                    'message_id' => is_array($data) ? ($data['message_id'] ?? null) : null,
                    'data' => $data,
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $results[$index] = [
                    'success' => false,
                    'chat_id' => $chatId,
                    'message_id' => null,
                    'data' => null,
                    'error' => $e->getMessage(),
                ];
            }

            if ($delayMs > 0 && $index !== $lastKey) {
                usleep($delayMs * 1000);
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
        return extension_loaded('openssl') &&
            in_array('https', stream_get_wrappers(), true);
    }
}
