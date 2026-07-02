<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

use AhmCho\Telegram\Config\BotConfig;

/**
 * Factory for creating logger instances
 */
final class LoggerFactory
{
    /**
     * Create a logger from BotConfig
     *
     * @param BotConfig $config The bot configuration
     * @return LoggerInterface|null The logger instance, or null if logging is disabled
     */
    public static function createFromConfig(BotConfig $config): ?LoggerInterface
    {
        if (!$config->isLoggingEnabled()) {
            return null;
        }

        return self::create($config->getLogConfig());
    }

    /**
     * Create a logger from array configuration
     *
     * @param array{log_file_path?: string, log_level?: string, log_max_bytes?: int, log_timezone?: string} $config
     * @return LoggerInterface The logger instance
     */
    public static function create(array $config = []): LoggerInterface
    {
        $logFilePath = $config['log_file_path'] ?? 'bot.log';
        $logLevel = $config['log_level'] ?? 'INFO';
        $logMaxBytes = (int) ($config['log_max_bytes'] ?? 0);
        $logTimezone = $config['log_timezone'] ?? 'UTC';

        $handler = new FileLogHandler($logFilePath, true, $logMaxBytes);
        $level = LogLevel::fromPsr3($logLevel);

        return new Logger($handler, $level, $logTimezone);
    }

    /**
     * Create a simple logger with defaults
     *
     * @return LoggerInterface The logger instance
     */
    public static function createDefault(): LoggerInterface
    {
        return self::create();
    }

    /**
     * Create a null logger that does nothing
     * Useful for testing or when logging is disabled
     *
     * @return LoggerInterface A null logger implementation
     */
    public static function createNull(): LoggerInterface
    {
        return new NullLogger();
    }
}
