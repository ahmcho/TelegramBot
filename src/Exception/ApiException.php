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
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }
}
