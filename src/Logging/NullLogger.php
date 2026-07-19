<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

/**
 * Null logger implementation that does nothing
 * Used when logging is disabled or for testing
 */
final class NullLogger implements LoggerInterface
{
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    public function logException(\Throwable $exception, array $context = []): void
    {
        // Do nothing
    }
}
