<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

use Override;
use Stringable;
use Throwable;

/**
 * Null logger implementation that does nothing
 * Used when logging is disabled or for testing
 */
final class NullLogger implements LoggerInterface
{
    #[Override]
    public function emergency(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function alert(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function critical(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function error(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function warning(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function notice(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function info(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function debug(string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        // Do nothing
    }

    #[Override]
    public function logException(Throwable $exception, array $context = []): void
    {
        // Do nothing
    }
}
