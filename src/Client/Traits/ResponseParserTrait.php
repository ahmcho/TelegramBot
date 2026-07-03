<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client\Traits;

use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * Response Parser Trait
 *
 * Provides common response parsing logic for HTTP clients
 */
trait ResponseParserTrait
{
    /**
     * Parse and validate HTTP response
     *
     * @param string $response Raw response body
     * @return mixed Parsed response data
     * @throws HttpClientException On transport failures: invalid JSON, non-object response
     * @throws ApiException On Telegram API errors: ok === false
     */
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

        if (!is_array($data)) {
            $exception = new HttpClientException(
                'Invalid response structure: expected JSON object',
                $this->lastHttpCode,
                $response
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        if (!($data['ok'] ?? false)) {
            $exception = new ApiException(
                $data['description'] ?? 'Unknown Telegram API error',
                $data['error_code'] ?? null,
                $this->lastHttpCode,
                $data
            );
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }

        return $data['result'] ?? [];
    }
}
