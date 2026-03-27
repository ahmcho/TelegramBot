<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bot;

use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;

/**
 * Bot Factory
 *
 * Factory for creating TelegramBot instances with various configurations
 */
class BotFactory
{
    /**
     * Create a bot instance with default configuration
     */
    public static function create(?string $token = null): TelegramBot
    {
        return new TelegramBot($token);
    }

    /**
     * Create a bot with custom configuration
     */
    public static function createWithConfig(BotConfig $config): TelegramBot
    {
        return new TelegramBot(null, $config);
    }

    /**
     * Create a bot with custom HTTP client
     */
    public static function createWithHttpClient(
        ?string $token,
        HttpClientInterface $httpClient
    ): TelegramBot {
        $config = new BotConfig(
            token: $token ?? self::getTokenFromEnv()
        );

        return new TelegramBot(null, $config, $httpClient);
    }

    /**
     * Get token from environment
     */
    private static function getTokenFromEnv(): string
    {
        $loader = new \AhmCho\Telegram\Config\EnvLoader();
        $loader->load();

        return $loader->require('TELEGRAM_BOT_TOKEN');
    }
}
