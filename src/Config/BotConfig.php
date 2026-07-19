<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Config;

/**
 * Bot Configuration Value Object
 *
 * Immutable configuration for Telegram bot
 */
final class BotConfig
{
    public function __construct(
        private readonly string $token,
        private readonly string $apiUrl = 'https://api.telegram.org/',
        private readonly int $timeout = 30,
        private readonly bool $throwExceptions = true,
        private readonly bool $verifySsl = true,
        private readonly bool $loggingEnabled = true,
        private readonly string $logFilePath = 'bot.log',
        private readonly string $logLevel = 'INFO',
        private readonly int $logMaxBytes = 0,
        private readonly string $logTimezone = 'UTC'
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getFullApiUrl(): string
    {
        return rtrim($this->apiUrl, '/') . '/bot' . $this->token . '/';
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function shouldThrowExceptions(): bool
    {
        return $this->throwExceptions;
    }

    public function shouldVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    /**
     * Check if logging is enabled
     */
    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled;
    }

    /**
     * Get the log file path
     */
    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    /**
     * Get the log level
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Get log rotation threshold in bytes (0 = disabled)
     */
    public function getLogMaxBytes(): int
    {
        return $this->logMaxBytes;
    }

    /**
     * Get the IANA timezone name used for log timestamps
     */
    public function getLogTimezone(): string
    {
        return $this->logTimezone;
    }

    /**
     * Get all logging configuration as an array
     *
     * @return array{log_file_path: string, log_level: string, log_max_bytes: int, log_timezone: string}
     */
    public function getLogConfig(): array
    {
        return [
            'log_file_path' => $this->logFilePath,
            'log_level' => $this->logLevel,
            'log_max_bytes' => $this->logMaxBytes,
            'log_timezone' => $this->logTimezone,
        ];
    }

    public function withVerifySsl(bool $verify): self
    {
        return $this->with(verifySsl: $verify);
    }

    public function withTimeout(int $timeout): self
    {
        return $this->with(timeout: $timeout);
    }

    public function withThrowExceptions(bool $throw): self
    {
        return $this->with(throwExceptions: $throw);
    }

    public function withLoggingEnabled(bool $enabled): self
    {
        return $this->with(loggingEnabled: $enabled);
    }

    public function withLogFilePath(string $path): self
    {
        return $this->with(logFilePath: $path);
    }

    public function withLogLevel(string $level): self
    {
        return $this->with(logLevel: $level);
    }

    /**
     * Create a new config with a log rotation threshold (0 = disabled)
     */
    public function withLogMaxBytes(int $maxBytes): self
    {
        return $this->with(logMaxBytes: $maxBytes);
    }

    /**
     * Create a new config with a different log timestamp timezone
     */
    public function withLogTimezone(string $timezone): self
    {
        return $this->with(logTimezone: $timezone);
    }

    /**
     * Build a new instance, overriding only the given named fields.
     */
    private function with(
        ?string $token = null,
        ?string $apiUrl = null,
        ?int $timeout = null,
        ?bool $throwExceptions = null,
        ?bool $verifySsl = null,
        ?bool $loggingEnabled = null,
        ?string $logFilePath = null,
        ?string $logLevel = null,
        ?int $logMaxBytes = null,
        ?string $logTimezone = null
    ): self {
        return new self(
            $token ?? $this->token,
            $apiUrl ?? $this->apiUrl,
            $timeout ?? $this->timeout,
            $throwExceptions ?? $this->throwExceptions,
            $verifySsl ?? $this->verifySsl,
            $loggingEnabled ?? $this->loggingEnabled,
            $logFilePath ?? $this->logFilePath,
            $logLevel ?? $this->logLevel,
            $logMaxBytes ?? $this->logMaxBytes,
            $logTimezone ?? $this->logTimezone
        );
    }
}
