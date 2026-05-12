<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

use AhmCho\Telegram\Logging\Context\ExceptionContext;

/**
 * PSR-3 compliant logger implementation
 */
final class Logger implements LoggerInterface
{
    private readonly LogLevel $minLevel;

    /**
     * @param FileLogHandler $handler The file handler for writing logs
     * @param LogLevel $minLevel Minimum log level to record
     */
    public function __construct(
        private readonly FileLogHandler $handler,
        LogLevel $minLevel = LogLevel::INFO
    ) {
        $this->minLevel = $minLevel;
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Log with arbitrary level
     *
     * @param mixed $level PSR-3 level string or LogLevel enum
     * @param string $message The log message
     * @param array<string, mixed> $context Context data
     */
    public function log($level, $message, array $context = []): void
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
        $interpolatedMessage = $this->interpolate($message, $context);

        // Format context as JSON
        $contextJson = !empty($context)
            ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            : '';

        // Build log entry
        $entry = $this->formatEntry($logLevel, $interpolatedMessage, $contextJson);

        // Write to file (never throw from logger)
        try {
            $this->handler->write($entry);
        } catch (\Throwable $e) {
            // Fail-safe: if logger fails, fall back to error_log
            error_log("Logger write failed: {$e->getMessage()}");
            error_log("Original log entry: {$entry}");
        }
    }

    /**
     * Log an exception with full context
     *
     * @param \Throwable $exception The exception to log
     * @param array<string, mixed> $context Additional context data
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $exceptionContext = ExceptionContext::fromException($exception);
        $mergedContext = array_merge($context, $exceptionContext->toArray());

        $this->log(
            LogLevel::ERROR,
            $exception->getMessage(),
            $mergedContext
        );
    }

    /**
     * Interpolate message placeholders with context values
     */
    private function interpolate(string $message, array $context): string
    {
        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            // Skip if value is not scalar and not object that implements __toString
            if (!is_scalar($value) && !is_object($value)) {
                continue;
            }

            // Convert value to string
            $value = is_object($value) && method_exists($value, '__toString')
                ? (string) $value
                : (is_scalar($value) ? (string) $value : json_encode($value));

            $replace['{' . $key . '}'] = $value;
        }

        return strtr($message, $replace);
    }

    /**
     * Format a log entry with timestamp and level
     */
    private function formatEntry(LogLevel $level, string $message, string $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $context = $context !== '' ? "\nContext: {$context}" : '';

        return "[{$timestamp}] [{$level->value}] {$message}{$context}" . PHP_EOL;
    }
}
