<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

/**
 * Null logger implementation that does nothing
 * Used when logging is disabled or for testing
 */
final class NullLogger implements LoggerInterface
{
    public function emergency($message, array $context = []): void
    {
        // Do nothing
    }

    public function alert($message, array $context = []): void
    {
        // Do nothing
    }

    public function critical($message, array $context = []): void
    {
        // Do nothing
    }

    public function error($message, array $context = []): void
    {
        // Do nothing
    }

    public function warning($message, array $context = []): void
    {
        // Do nothing
    }

    public function notice($message, array $context = []): void
    {
        // Do nothing
    }

    public function info($message, array $context = []): void
    {
        // Do nothing
    }

    public function debug($message, array $context = []): void
    {
        // Do nothing
    }

    public function log($level, $message, array $context = []): void
    {
        // Do nothing
    }

    public function logException(\Throwable $exception, array $context = []): void
    {
        // Do nothing
    }
}
