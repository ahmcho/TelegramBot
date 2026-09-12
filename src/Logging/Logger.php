<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

use AhmCho\Telegram\Logging\Context\ExceptionContext;
use DateTimeImmutable;
use Override;
use Throwable;

/**
 * PSR-3 compliant logger implementation
 */
final readonly class Logger implements LoggerInterface
{
    /**
     * @param FileLogHandler $handler The file handler for writing logs
     * @param LogLevel $minLevel Minimum log level to record
     * @param string $timezone IANA timezone name for log timestamps (default: UTC)
     */
    public function __construct(private FileLogHandler $handler, private LogLevel $minLevel = LogLevel::INFO, private string $timezone = 'UTC')
    {
    }

    #[Override]
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    #[Override]
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    #[Override]
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    #[Override]
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    #[Override]
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    #[Override]
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    #[Override]
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    #[Override]
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Log with arbitrary level
     *
     * @param mixed $level PSR-3 level string or LogLevel enum
     * @param array<string, mixed> $context Context data
     */
    #[Override]
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        // Convert PSR-3 level string to enum
        $logLevel = $level instanceof LogLevel
            ? $level
            : LogLevel::fromPsr3((string) $level);

        // Check if this level should be logged
        if (!$logLevel->shouldLog($this->minLevel)) {
            return;
        }

        // Interpolate message with context
        $interpolatedMessage = $this->interpolate((string) $message, $context);

        // Format context as JSON
        $contextJson = '';
        if ($context !== []) {
            $encodedContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $contextJson = $encodedContext !== false ? $encodedContext : '(unencodable context)';
        }

        // Build log entry
        $entry = $this->formatEntry($logLevel, $interpolatedMessage, $contextJson);

        // Write to file (never throw from logger)
        try {
            $this->handler->write($entry);
        } catch (Throwable $e) {
            // Fail-safe: if logger fails, fall back to error_log
            error_log("Logger write failed: {$e->getMessage()}");
            error_log("Original log entry: {$entry}");
        }
    }

    /**
     * Log an exception with full context
     *
     * @param Throwable $exception The exception to log
     * @param array<string, mixed> $context Additional context data
     */
    #[Override]
    public function logException(Throwable $exception, array $context = []): void
    {
        $exceptionContext = ExceptionContext::fromException($exception);
        $mergedContext = [...$context, ...$exceptionContext->toArray()];

        $this->log(
            LogLevel::ERROR,
            $exception->getMessage(),
            $mergedContext
        );
    }

    /**
     * Interpolate message placeholders with context values
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        if ($context === []) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            // Skip if value is not scalar and not object that implements __toString
            if (!is_scalar($value) && !is_object($value)) {
                continue;
            }

            // Convert value to string
            if (is_object($value)) {
                if (method_exists($value, '__toString')) {
                    $stringValue = (string) $value;
                } else {
                    $encoded = json_encode($value);
                    $stringValue = $encoded !== false ? $encoded : '(unencodable value)';
                }
            } else {
                $stringValue = (string) $value;
            }

            $replace['{' . $key . '}'] = $stringValue;
        }

        return strtr($message, $replace);
    }

    /**
     * Format a log entry with timestamp and level
     */
    private function formatEntry(LogLevel $level, string $message, string $context): string
    {
        $timestamp = (new DateTimeImmutable('now', new \DateTimeZone($this->timezone)))->format('Y-m-d H:i:s');
        $context = $context !== '' ? "\nContext: {$context}" : '';

        return "[{$timestamp}] [{$level->value}] {$message}{$context}" . PHP_EOL;
    }
}
