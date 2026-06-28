<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Logging\LoggerFactory;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Logger;
use AhmCho\Telegram\Logging\NullLogger;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/logger-test.php and examples/logger-syntax-test.php
 *
 * Verifies the logging system works as shown in the examples:
 * - Logger is created when logging is enabled in BotConfig
 * - NullLogger is used when logging is disabled
 * - Custom log levels and file paths work
 * - LoggerFactory produces correct logger types
 * - Example PHP files pass syntax check
 */
final class LoggerExampleTest extends TestCase
{
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
    }

    public function test_bot_has_no_logger_when_logging_disabled(): void
    {
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $bot = new TelegramBot(null, $config, $this->mockClient);

        $this->assertNull($bot->getLogger());
    }

    public function test_bot_has_logger_when_logging_enabled(): void
    {
        $logFile = sys_get_temp_dir() . '/test_bot_' . uniqid() . '.log';
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $logFile,
            logLevel: 'DEBUG'
        );
        $bot = new TelegramBot(null, $config, $this->mockClient);

        $this->assertInstanceOf(LoggerInterface::class, $bot->getLogger());

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function test_logger_factory_creates_null_logger(): void
    {
        $logger = LoggerFactory::createNull();

        $this->assertInstanceOf(NullLogger::class, $logger);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function test_null_logger_accepts_all_levels_without_error(): void
    {
        $logger = LoggerFactory::createNull();

        $logger->debug('debug message', ['key' => 'value']);
        $logger->info('info message');
        $logger->notice('notice message');
        $logger->warning('warning message');
        $logger->error('error message');
        $logger->critical('critical message');
        $logger->alert('alert message');
        $logger->emergency('emergency message');

        $this->assertTrue(true);
    }

    public function test_logger_factory_creates_from_config_with_logging_disabled(): void
    {
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $logger = LoggerFactory::createFromConfig($config);

        $this->assertNull($logger);
    }

    public function test_logger_factory_creates_from_config_with_logging_enabled(): void
    {
        $logFile = sys_get_temp_dir() . '/test_factory_' . uniqid() . '.log';
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $logFile,
            logLevel: 'INFO'
        );

        $logger = LoggerFactory::createFromConfig($config);

        $this->assertInstanceOf(Logger::class, $logger);

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function test_logger_factory_create_default_returns_logger(): void
    {
        $logger = LoggerFactory::createDefault();

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        if (file_exists('bot.log')) {
            unlink('bot.log');
        }
    }

    public function test_botconfig_logging_defaults(): void
    {
        $config = new BotConfig(token: 'test_token');

        $this->assertTrue($config->isLoggingEnabled());
        $this->assertSame('bot.log', $config->getLogFilePath());
        $this->assertSame('INFO', $config->getLogLevel());
    }

    public function test_botconfig_with_logging_enabled_false(): void
    {
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);

        $this->assertFalse($config->isLoggingEnabled());
    }

    public function test_botconfig_with_custom_log_file_and_level(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            logFilePath: '/tmp/custom_bot.log',
            logLevel: 'DEBUG'
        );

        $this->assertSame('/tmp/custom_bot.log', $config->getLogFilePath());
        $this->assertSame('DEBUG', $config->getLogLevel());
    }

    public function test_bot_can_be_created_with_info_log_level(): void
    {
        $logFile = sys_get_temp_dir() . '/test_info_' . uniqid() . '.log';
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $logFile,
            logLevel: 'INFO'
        );

        $bot = new TelegramBot(null, $config, $this->mockClient);

        $this->assertInstanceOf(LoggerInterface::class, $bot->getLogger());

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function test_bot_can_be_created_with_debug_log_level(): void
    {
        $logFile = sys_get_temp_dir() . '/test_debug_' . uniqid() . '.log';
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $logFile,
            logLevel: 'DEBUG'
        );

        $bot = new TelegramBot(null, $config, $this->mockClient);

        $logger = $bot->getLogger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);

        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public function test_example_files_pass_php_syntax_check(): void
    {
        $examplesDir = dirname(__DIR__, 3) . '/examples';

        $exampleFiles = glob($examplesDir . '/*.php');
        $this->assertNotEmpty($exampleFiles, 'Should find example files');

        foreach ($exampleFiles as $file) {
            $output = [];
            $exitCode = 0;
            exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

            $this->assertSame(0, $exitCode, sprintf(
                'PHP syntax error in %s: %s',
                basename($file),
                implode("\n", $output)
            ));
        }
    }
}
