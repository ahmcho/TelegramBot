<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Config;

use NoDiscard;
use SensitiveParameter;
use Uri\Rfc3986\Uri;

/**
 * Bot Configuration Value Object
 *
 * Immutable configuration for Telegram bot
 */
final readonly class BotConfig
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
        public string $apiUrl = 'https://api.telegram.org/',
        public int $timeout = 30,
        public bool $throwExceptions = true,
        public bool $verifySsl = true,
        public bool $loggingEnabled = true,
        public string $logFilePath = 'bot.log',
        public string $logLevel = 'INFO',
        public int $logMaxBytes = 0,
        public string $logTimezone = 'UTC'
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
        return $this->apiUrl
            |> (fn(string $url): string => rtrim($url, '/'))
            |> (fn(string $url): string => $url . '/bot' . $this->token . '/');
    }

    /**
     * Get the full API URL parsed as an RFC 3986 URI instance
     */
    public function getUri(): ?Uri
    {
        return Uri::parse($this->getFullApiUrl());
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

    #[NoDiscard]
    public function withVerifySsl(bool $verify): self
    {
        return clone($this, ['verifySsl' => $verify]);
    }

    #[NoDiscard]
    public function withTimeout(int $timeout): self
    {
        return clone($this, ['timeout' => $timeout]);
    }

    #[NoDiscard]
    public function withThrowExceptions(bool $throw): self
    {
        return clone($this, ['throwExceptions' => $throw]);
    }

    #[NoDiscard]
    public function withLoggingEnabled(bool $enabled): self
    {
        return clone($this, ['loggingEnabled' => $enabled]);
    }

    #[NoDiscard]
    public function withLogFilePath(string $path): self
    {
        return clone($this, ['logFilePath' => $path]);
    }

    #[NoDiscard]
    public function withLogLevel(string $level): self
    {
        return clone($this, ['logLevel' => $level]);
    }

    /**
     * Create a new config with a log rotation threshold (0 = disabled)
     */
    #[NoDiscard]
    public function withLogMaxBytes(int $maxBytes): self
    {
        return clone($this, ['logMaxBytes' => $maxBytes]);
    }

    /**
     * Create a new config with a different log timestamp timezone
     */
    #[NoDiscard]
    public function withLogTimezone(string $timezone): self
    {
        return clone($this, ['logTimezone' => $timezone]);
    }
}
