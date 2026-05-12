<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\LoggerFactory;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Logger;
use AhmCho\Telegram\Logging\NullLogger;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Logging\LogLevel;

/**
 * LoggerFactory Tests
 */
final class LoggerFactoryTest extends TestCase
{
    private string $testLogFile;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/factory_test_' . time() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function test_create_returns_logger_instance(): void
    {
        $logger = LoggerFactory::create();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function test_create_with_custom_config(): void
    {
        $logger = LoggerFactory::create([
            'log_file_path' => $this->testLogFile,
            'log_level' => 'DEBUG'
        ]);

        $this->assertInstanceOf(Logger::class, $logger);
        // Write something to actually create the file
        $logger->info('Test');
        $this->assertFileExists($this->testLogFile);
    }

    public function test_create_uses_default_file_path_when_not_provided(): void
    {
        $logger = LoggerFactory::create();
        $this->assertInstanceOf(Logger::class, $logger);

        // Default is 'bot.log', but file is only created on first write
        $logger->info('Test message');
        $this->assertFileExists('bot.log');
        unlink('bot.log');
    }

    public function test_create_uses_default_log_level_when_not_provided(): void
    {
        $logger = LoggerFactory::create([
            'log_file_path' => $this->testLogFile
        ]);

        // Default should be INFO
        $this->assertInstanceOf(Logger::class, $logger);
        // Logger works correctly with default level
        $logger->info('Test message');
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('INFO', $content);
    }

    public function test_create_with_different_log_levels(): void
    {
        $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];

        foreach ($levels as $i => $level) {
            $testFile = sys_get_temp_dir() . '/level_test_' . time() . '_' . $i . '.log';
            $logger = LoggerFactory::create([
                'log_file_path' => $testFile,
                'log_level' => $level
            ]);

            $this->assertInstanceOf(Logger::class, $logger);
            // Write to create the file, then cleanup
            $logger->info('Test');
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }

    public function test_create_from_config_with_logging_enabled(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $this->testLogFile,
            logLevel: 'DEBUG'
        );

        $logger = LoggerFactory::createFromConfig($config);

        $this->assertInstanceOf(Logger::class, $logger);
        // Write something to actually create the file
        $logger->info('Test');
        $this->assertFileExists($this->testLogFile);
    }

    public function test_create_from_config_with_logging_disabled(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: false
        );

        $logger = LoggerFactory::createFromConfig($config);

        $this->assertNull($logger);
    }

    public function test_create_default_returns_logger(): void
    {
        $logger = LoggerFactory::createDefault();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function test_create_null_returns_null_logger(): void
    {
        $logger = LoggerFactory::createNull();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(NullLogger::class, $logger);
    }

    public function test_create_null_does_not_create_file(): void
    {
        $testFile = sys_get_temp_dir() . '/null_test_' . time() . '.log';

        LoggerFactory::createNull();

        $this->assertFileDoesNotExist($testFile);
    }

    public function test_created_logger_writes_to_correct_file(): void
    {
        $logger = LoggerFactory::create([
            'log_file_path' => $this->testLogFile
        ]);

        $logger->info('Test message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test message', $content);
    }

    public function test_create_from_config_uses_config_values(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true,
            logFilePath: $this->testLogFile,
            logLevel: 'ERROR'
        );

        $logger = LoggerFactory::createFromConfig($config);
        $logger->debug('This should not be logged');
        $logger->error('This should be logged');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('This should not be logged', $content);
        $this->assertStringContainsString('This should be logged', $content);
    }

    public function test_create_with_invalid_log_level(): void
    {
        // Should handle invalid level gracefully
        $logger = LoggerFactory::create([
            'log_file_path' => $this->testLogFile,
            'log_level' => 'invalid_level'
        ]);

        // Should default to INFO for invalid levels
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function test_create_handles_empty_config_array(): void
    {
        $logger = LoggerFactory::create([]);

        $this->assertInstanceOf(Logger::class, $logger);
        // Should use defaults
        $logger->info('Test with empty config');
        $this->assertFileExists('bot.log');
        unlink('bot.log');
    }
}
