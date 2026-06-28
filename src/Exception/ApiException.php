<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Exception;

/**
 * API Exception
 *
 * Thrown when Telegram API returns an error response
 */
class ApiException extends TelegramException
{
    public function __construct(
        string $message = '',
        private readonly ?int $errorCode = null,
        private readonly ?int $httpCode = null,
        private readonly array $responseBody = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
