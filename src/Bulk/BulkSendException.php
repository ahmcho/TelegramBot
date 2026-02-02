<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bulk;

use AhmCho\Telegram\Exception\TelegramException;

/**
 * Bulk Send Exception
 *
 * Thrown when bulk operations complete with failures
 * Contains the full BulkResult for inspection
 */
class BulkSendException extends TelegramException
{
    public function __construct(
        string $message,
        private readonly BulkResult $result
    ) {
        parent::__construct($message);
    }

    public function getResult(): BulkResult
    {
        return $this->result;
    }
}
