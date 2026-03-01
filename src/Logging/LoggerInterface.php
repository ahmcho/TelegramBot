<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * PSR-3 compatible logger interface with exception logging support
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Log an exception with full context
     *
     * @param \Throwable $exception The exception to log
     * @param array<string, mixed> $context Additional context data
     */
    public function logException(\Throwable $exception, array $context = []): void;
}
