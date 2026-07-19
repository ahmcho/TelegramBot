<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Enums\HttpMethod;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Traits\LoggerHelperTrait;
use AhmCho\Telegram\Client\Traits\ResponseParserTrait;
use AhmCho\Telegram\Client\Traits\MultipartRequestTrait;
use AhmCho\Telegram\Client\Traits\TimeoutResolverTrait;

final class CurlHttpClient implements HttpClientInterface
{
    use LoggerHelperTrait;
    use ResponseParserTrait;
    use MultipartRequestTrait;
    use TimeoutResolverTrait;

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
        $ch = $this->createCurlHandle($method, $url, $params);

        $response = curl_exec($ch);
        $this->lastHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);

        if ($error || $errno) {
            $exception = new HttpClientException(
                "cURL error ($errno): $error",
                $this->lastHttpCode,
                $response ?: null
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        if ($response === false) {
            $exception = new HttpClientException(
                'cURL request failed without error message',
                $this->lastHttpCode
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        return $this->parseResponse($response);
    }

    public function requestMulti(
        HttpMethod $method,
        string $url,
        array $requestsArray,
        array $options = []
    ): array {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        $options = [
            'max_concurrent' => $options['max_concurrent'] ?? 30,
            'delay_ms' => $options['delay_ms'] ?? 0
        ];

        // Create handles
        foreach ($requestsArray as $index => $params) {
            $ch = $this->createCurlHandle($method, $url, $params);
            $handles[$index] = [
                'handle' => $ch,
                'params' => $params
            ];
            curl_multi_add_handle($mh, $ch);
        }

        // Execute with batching
        $results = $this->executeMultiHandles($mh, $handles, $options);

        // Cleanup
        foreach ($handles as $handleData) {
            curl_multi_remove_handle($mh, $handleData['handle']);
        }
        curl_multi_close($mh);

        return $results;
    }

    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    public static function isAvailable(): bool
    {
        return function_exists('curl_init') && function_exists('curl_exec');
    }

    private function createCurlHandle(
        HttpMethod $method,
        string $url,
        array $params
    ) {
        $ch = curl_init();

        $verifySsl = $this->config->shouldVerifySsl();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => $method === HttpMethod::POST,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->resolveTimeout($params),
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ];

        $hasFile = $this->hasFileUpload($params);

        if (!$hasFile) {
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
            $options[CURLOPT_POSTFIELDS] = json_encode($params);
        } else {
            $options[CURLOPT_POSTFIELDS] = $params;
        }

        curl_setopt_array($ch, $options);

        return $ch;
    }

    private function executeMultiHandles(
        $mh,
        array $handles,
        array $options
    ): array {
        $results = [];
        $active = null;
        $maxConcurrent = $options['max_concurrent'];
        $delayMs = $options['delay_ms'];

        $handleKeys = array_keys($handles);
        if (empty($handleKeys)) {
            return [];
        }
        $batchSize = min($maxConcurrent, count($handleKeys));
        $batches = array_chunk($handleKeys, $batchSize);

        foreach ($batches as $batch) {
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active > 0) {
                    curl_multi_select($mh);
                }
            } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

            foreach ($batch as $index) {
                $handleData = $handles[$index];
                $ch = $handleData['handle'];

                $response = curl_multi_getcontent($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                $errno = curl_errno($ch);

                $results[$index] = $this->processMultiResult(
                    $response,
                    $httpCode,
                    $error,
                    $errno,
                    $handleData['params']
                );
            }

            if ($delayMs > 0 && $batch !== end($batches)) {
                usleep($delayMs * 1000);
            }
        }

        return $results;
    }

    private function processMultiResult(
        ?string $response,
        int $httpCode,
        string $error,
        int $errno,
        array $params
    ): array {
        $chatId = $params['chat_id'] ?? 'unknown';

        if ($error || $errno) {
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => "cURL error ($errno): $error"
            ];
        }

        if ($response === false) {
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => 'cURL request failed without error message'
            ];
        }

        try {
            $data = $this->parseResponse($response);
            return [
                'success' => true,
                'chat_id' => $chatId,
                'message_id' => $data['message_id'] ?? null,
                'data' => $data,
                'error' => null
            ];
        } catch (TelegramException $e) {
            // Catches both HttpClientException (transport/parsing failures) and
            // ApiException (Telegram returned ok:false) so one bad request in a
            // batch surfaces as a per-item failure instead of aborting the batch.
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }

}
