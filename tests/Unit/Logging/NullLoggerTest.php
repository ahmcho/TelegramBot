<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\NullLogger;

/**
 * NullLogger Tests
 */
final class NullLoggerTest extends TestCase
{
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    public function test_emergency_does_nothing(): void
    {
        $this->assertNull($this->logger->emergency('test message'));
    }

    public function test_alert_does_nothing(): void
    {
        $this->assertNull($this->logger->alert('test message'));
    }

    public function test_critical_does_nothing(): void
    {
        $this->assertNull($this->logger->critical('test message'));
    }

    public function test_error_does_nothing(): void
    {
        $this->assertNull($this->logger->error('test message'));
    }

    public function test_warning_does_nothing(): void
    {
        $this->assertNull($this->logger->warning('test message'));
    }

    public function test_notice_does_nothing(): void
    {
        $this->assertNull($this->logger->notice('test message'));
    }

    public function test_info_does_nothing(): void
    {
        $this->assertNull($this->logger->info('test message'));
    }

    public function test_debug_does_nothing(): void
    {
        $this->assertNull($this->logger->debug('test message'));
    }

    public function test_log_does_nothing(): void
    {
        $this->assertNull($this->logger->log('info', 'test message'));
        $this->assertNull($this->logger->log('error', 'test message', ['context' => 'data']));
    }

    public function test_log_exception_does_nothing(): void
    {
        $exception = new \Exception('Test exception');
        $this->assertNull($this->logger->logException($exception));
        $this->assertNull($this->logger->logException($exception, ['context' => 'data']));
    }

    public function test_logger_is_no_op(): void
    {
        // Call all methods and verify no output or side effects
        $this->logger->emergency('emergency');
        $this->logger->alert('alert');
        $this->logger->critical('critical');
        $this->logger->error('error');
        $this->logger->warning('warning');
        $this->logger->notice('notice');
        $this->logger->info('info');
        $this->logger->debug('debug');
        $this->logger->log('custom', 'custom');

        $exception = new \RuntimeException('test');
        $this->logger->logException($exception, ['key' => 'value']);

        // Test passes if no exceptions were thrown
        $this->assertTrue(true);
    }

    public function test_logger_accepts_context_arrays(): void
    {
        $this->assertNull($this->logger->info('test', [
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => ['data' => 'here']
        ]));
    }

    public function test_logger_accepts_empty_context(): void
    {
        $this->assertNull($this->logger->error('test', []));
        $this->assertNull($this->logger->error('test'));
    }

    public function test_logger_implements_logger_interface(): void
    {
        $this->assertInstanceOf(\AhmCho\Telegram\Logging\LoggerInterface::class, $this->logger);
    }
}
