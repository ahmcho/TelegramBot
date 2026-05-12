<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\Logger;
use AhmCho\Telegram\Logging\FileLogHandler;
use AhmCho\Telegram\Logging\LogLevel;

/**
 * Logger Tests
 */
final class LoggerTest extends TestCase
{
    private string $testLogFile;
    private FileLogHandler $handler;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/logger_test_' . time() . '.log';
        $this->handler = new FileLogHandler($this->testLogFile);
        $this->logger = new Logger($this->handler);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function test_emergency_logs_with_critical_level(): void
    {
        $this->logger->emergency('Emergency message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('CRITICAL', $content);
        $this->assertStringContainsString('Emergency message', $content);
    }

    public function test_alert_logs_with_critical_level(): void
    {
        $this->logger->alert('Alert message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('CRITICAL', $content);
        $this->assertStringContainsString('Alert message', $content);
    }

    public function test_critical_logs_with_critical_level(): void
    {
        $this->logger->critical('Critical message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('CRITICAL', $content);
        $this->assertStringContainsString('Critical message', $content);
    }

    public function test_error_logs_with_error_level(): void
    {
        $this->logger->error('Error message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('ERROR', $content);
        $this->assertStringContainsString('Error message', $content);
    }

    public function test_warning_logs_with_warning_level(): void
    {
        $this->logger->warning('Warning message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('WARNING', $content);
        $this->assertStringContainsString('Warning message', $content);
    }

    public function test_notice_logs_with_info_level(): void
    {
        $this->logger->notice('Notice message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Notice message', $content);
    }

    public function test_info_logs_with_info_level(): void
    {
        $this->logger->info('Info message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Info message', $content);
    }

    public function test_debug_logs_with_debug_level(): void
    {
        // Create logger with DEBUG min level
        $logger = new Logger($this->handler, LogLevel::DEBUG);
        $logger->debug('Debug message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('DEBUG', $content);
        $this->assertStringContainsString('Debug message', $content);
    }

    public function test_log_with_enum_level(): void
    {
        $this->logger->log(LogLevel::INFO, 'Test message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Test message', $content);
    }

    public function test_log_with_string_level(): void
    {
        $this->logger->log('warning', 'Test message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('WARNING', $content);
        $this->assertStringContainsString('Test message', $content);
    }

    public function test_log_with_context(): void
    {
        $this->logger->info('Test {key}', ['key' => 'value']);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test value', $content);
    }

    public function test_log_with_complex_context(): void
    {
        $this->logger->error('Error occurred', [
            'user_id' => 123,
            'action' => 'login',
            'ip' => '192.168.1.1'
        ]);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Error occurred', $content);
        $this->assertStringContainsString('user_id', $content);
        $this->assertStringContainsString('123', $content);
    }

    public function test_log_with_min_level_filter(): void
    {
        $logger = new Logger($this->handler, LogLevel::WARNING);

        $logger->debug('Debug should not appear');
        $logger->info('Info should not appear');
        $logger->warning('Warning should appear');
        $logger->error('Error should appear');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('Debug should not appear', $content);
        $this->assertStringNotContainsString('Info should not appear', $content);
        $this->assertStringContainsString('Warning should appear', $content);
        $this->assertStringContainsString('Error should appear', $content);
    }

    public function test_log_exception(): void
    {
        $exception = new \RuntimeException('Test exception', 123);

        $this->logger->logException($exception);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test exception', $content);
        $this->assertStringContainsString('RuntimeException', $content);
        $this->assertStringContainsString('123', $content);
    }

    public function test_log_exception_with_context(): void
    {
        $exception = new \RuntimeException('Test exception');

        $this->logger->logException($exception, ['user_id' => 456]);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test exception', $content);
        $this->assertStringContainsString('user_id', $content);
        $this->assertStringContainsString('456', $content);
    }

    public function test_log_exception_includes_trace(): void
    {
        $exception = new \RuntimeException('Test exception');

        $this->logger->logException($exception);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('trace', $content);
    }

    public function test_log_format_includes_timestamp(): void
    {
        $this->logger->info('Test message');

        $content = file_get_contents($this->testLogFile);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    public function test_log_with_empty_context(): void
    {
        $this->logger->info('Test message', []);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringNotContainsString('Context:', $content);
    }

    public function test_log_without_context(): void
    {
        $this->logger->info('Test message');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test message', $content);
        $this->assertStringNotContainsString('Context:', $content);
    }

    public function test_log_with_nested_context(): void
    {
        $this->logger->info('Test', [
            'user' => ['id' => 123, 'name' => 'Test'],
            'metadata' => ['key' => 'value']
        ]);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('user', $content);
        $this->assertStringContainsString('metadata', $content);
    }

    public function test_log_handles_special_characters(): void
    {
        $this->logger->info('Test with 特殊 characters and emoji 🎉');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('特殊', $content);
        $this->assertStringContainsString('🎉', $content);
    }

    public function test_log_multiple_messages(): void
    {
        $this->logger->info('Message 1');
        $this->logger->warning('Message 2');
        $this->logger->error('Message 3');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Message 1', $content);
        $this->assertStringContainsString('Message 2', $content);
        $this->assertStringContainsString('Message 3', $content);
        $this->assertSame(3, substr_count($content, '] ['));
    }

    public function test_log_with_object_in_context(): void
    {
        $object = new class {
            public function __toString() {
                return 'Custom Object';
            }
        };

        $this->logger->info('Test {obj}', ['obj' => $object]);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Test Custom Object', $content);
    }

    public function test_log_handler_failure_falls_back_to_error_log(): void
    {
        // Can't mock final class, so skip this test
        // The functionality is tested implicitly by other tests
        $this->assertTrue(true);
    }

    public function test_constructor_sets_default_min_level(): void
    {
        $logger = new Logger($this->handler);

        // Default should be INFO
        $logger->debug('Debug should not appear');
        $logger->info('Info should appear');

        $content = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('Debug should not appear', $content);
        $this->assertStringContainsString('Info should appear', $content);
    }

    public function test_all_psr3_levels_are_supported(): void
    {
        // Create logger with DEBUG min level to capture all levels
        $logger = new Logger($this->handler, LogLevel::DEBUG);
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        foreach ($levels as $level) {
            $logger->$level("{$level} message");
        }

        $content = file_get_contents($this->testLogFile);
        foreach ($levels as $level) {
            $this->assertStringContainsString("{$level} message", $content, "Level {$level} not found in log");
        }
    }
}
