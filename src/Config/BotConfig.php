<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Config;

/**
 * Bot Configuration Value Object
 *
 * Immutable configuration for Telegram bot
 */
class BotConfig
{
    public function __construct(
        private readonly string $token,
        private readonly string $apiUrl = 'https://api.telegram.org/',
        private readonly int $timeout = 30,
        private readonly bool $throwExceptions = true,
        private readonly bool $verifySsl = false
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

    public function withTimeout(int $timeout): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $timeout,
            $this->throwExceptions,
            $this->verifySsl
        );
    }

    public function withThrowExceptions(bool $throw): self
    {
        return new self(
            $this->token,
            $this->apiUrl,
            $this->timeout,
            $throw,
            $this->verifySsl
        );
    }
}
