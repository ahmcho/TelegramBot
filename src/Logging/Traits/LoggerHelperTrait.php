<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging\Traits;

use AhmCho\Telegram\Logging\LoggerInterface;

/**
 * Logger Helper Trait
 *
 * Provides common logging methods for classes that use a logger
 */
trait LoggerHelperTrait
{
    /**
     * Log message if logger is configured
     * Never throws exceptions from logging operations
     *
     * @param 'info'|'warning'|'error'|'debug' $level
     * @param array<string, mixed> $context
     */
    protected function logIfEnabled(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->log($level, $message, $context);
            } catch (\Throwable) {
                // Fail silently - never throw from logger
            }
        }
    }

    /**
     * Log exception if logger is configured
     * Never throws exceptions from logging operations
     *
     * @param array<string, mixed> $context
     */
    protected function logExceptionIfEnabled(\Throwable $exception, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->logException($exception, $context);
            } catch (\Throwable) {
                // Fail silently - never throw from logger
            }
        }
    }
}
