<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Exception;

/**
 * HTTP Client Exception
 *
 * Thrown when HTTP requests fail due to network issues, timeout, or invalid responses
 */
class HttpClientException extends TelegramException
{
    public function __construct(
        string $message = '',
        private readonly ?int $httpCode = null,
        private readonly ?string $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
