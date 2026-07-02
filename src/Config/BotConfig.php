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
        private readonly int $logMaxBytes = 0
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
     * Get all logging configuration as an array
     *
     * @return array{log_file_path: string, log_level: string, log_max_bytes: int}
     */
    public function getLogConfig(): array
    {
        return [
            'log_file_path' => $this->logFilePath,
            'log_level' => $this->logLevel,
            'log_max_bytes' => $this->logMaxBytes,
        ];
    }

    public function withVerifySsl(bool $verify): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $this->throwExceptions,
            $verify,
            $this->loggingEnabled,
            $this->logFilePath,
            $this->logLevel,
            $this->logMaxBytes
        );
    }

    public function withTimeout(int $timeout): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $timeout,
            $this->throwExceptions,
            $this->verifySsl,
            $this->loggingEnabled,
            $this->logFilePath,
            $this->logLevel,
            $this->logMaxBytes
        );
    }

    public function withThrowExceptions(bool $throw): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $throw,
            $this->verifySsl,
            $this->loggingEnabled,
            $this->logFilePath,
            $this->logLevel,
            $this->logMaxBytes
        );
    }

    /**
     * Create a new config with logging enabled/disabled
     */
    public function withLoggingEnabled(bool $enabled): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $this->throwExceptions,
            $this->verifySsl,
            $enabled,
            $this->logFilePath,
            $this->logLevel,
            $this->logMaxBytes
        );
    }

    /**
     * Create a new config with a different log file path
     */
    public function withLogFilePath(string $path): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $this->throwExceptions,
            $this->verifySsl,
            $this->loggingEnabled,
            $path,
            $this->logLevel,
            $this->logMaxBytes
        );
    }

    /**
     * Create a new config with a different log level
     */
    public function withLogLevel(string $level): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $this->throwExceptions,
            $this->verifySsl,
            $this->loggingEnabled,
            $this->logFilePath,
            $level,
            $this->logMaxBytes
        );
    }

    /**
     * Create a new config with a log rotation threshold
     * Set to 0 to disable rotation (default)
     */
    public function withLogMaxBytes(int $maxBytes): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $this->throwExceptions,
            $this->verifySsl,
            $this->loggingEnabled,
            $this->logFilePath,
            $this->logLevel,
            $maxBytes
        );
    }
}
