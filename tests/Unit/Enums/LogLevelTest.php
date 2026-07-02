<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\LogLevel;

/**
 * LogLevel Enum Tests
 */
final class LogLevelTest extends TestCase
{
    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('DEBUG', LogLevel::DEBUG->value);
        $this->assertSame('INFO', LogLevel::INFO->value);
        $this->assertSame('NOTICE', LogLevel::NOTICE->value);
        $this->assertSame('WARNING', LogLevel::WARNING->value);
        $this->assertSame('ERROR', LogLevel::ERROR->value);
        $this->assertSame('CRITICAL', LogLevel::CRITICAL->value);
        $this->assertSame('ALERT', LogLevel::ALERT->value);
        $this->assertSame('EMERGENCY', LogLevel::EMERGENCY->value);
    }

    public function test_weight_returns_correct_values(): void
    {
        $this->assertSame(100, LogLevel::DEBUG->weight());
        $this->assertSame(200, LogLevel::INFO->weight());
        $this->assertSame(250, LogLevel::NOTICE->weight());
        $this->assertSame(300, LogLevel::WARNING->weight());
        $this->assertSame(400, LogLevel::ERROR->weight());
        $this->assertSame(500, LogLevel::CRITICAL->weight());
        $this->assertSame(550, LogLevel::ALERT->weight());
        $this->assertSame(600, LogLevel::EMERGENCY->weight());
    }

    public function test_weight_increases_with_severity(): void
    {
        $this->assertLessThan(LogLevel::INFO->weight(), LogLevel::DEBUG->weight());
        $this->assertLessThan(LogLevel::NOTICE->weight(), LogLevel::INFO->weight());
        $this->assertLessThan(LogLevel::WARNING->weight(), LogLevel::NOTICE->weight());
        $this->assertLessThan(LogLevel::ERROR->weight(), LogLevel::WARNING->weight());
        $this->assertLessThan(LogLevel::CRITICAL->weight(), LogLevel::ERROR->weight());
        $this->assertLessThan(LogLevel::ALERT->weight(), LogLevel::CRITICAL->weight());
        $this->assertLessThan(LogLevel::EMERGENCY->weight(), LogLevel::ALERT->weight());
    }

    public function test_should_log_with_same_level(): void
    {
        $this->assertTrue(LogLevel::INFO->shouldLog(LogLevel::INFO));
        $this->assertTrue(LogLevel::ERROR->shouldLog(LogLevel::ERROR));
        $this->assertTrue(LogLevel::CRITICAL->shouldLog(LogLevel::CRITICAL));
    }

    public function test_should_log_with_higher_level(): void
    {
        $this->assertTrue(LogLevel::ERROR->shouldLog(LogLevel::WARNING));
        $this->assertTrue(LogLevel::CRITICAL->shouldLog(LogLevel::ERROR));
        $this->assertTrue(LogLevel::WARNING->shouldLog(LogLevel::DEBUG));
    }

    public function test_should_not_log_with_lower_level(): void
    {
        $this->assertFalse(LogLevel::DEBUG->shouldLog(LogLevel::INFO));
        $this->assertFalse(LogLevel::INFO->shouldLog(LogLevel::WARNING));
        $this->assertFalse(LogLevel::WARNING->shouldLog(LogLevel::ERROR));
        $this->assertFalse(LogLevel::ERROR->shouldLog(LogLevel::CRITICAL));
    }

    public function test_from_psr3_debug(): void
    {
        $level = LogLevel::fromPsr3('debug');
        $this->assertSame(LogLevel::DEBUG, $level);
    }

    public function test_from_psr3_info(): void
    {
        $level = LogLevel::fromPsr3('info');
        $this->assertSame(LogLevel::INFO, $level);
    }

    public function test_from_psr3_notice(): void
    {
        $level = LogLevel::fromPsr3('notice');
        $this->assertSame(LogLevel::NOTICE, $level);
    }

    public function test_from_psr3_warning(): void
    {
        $level = LogLevel::fromPsr3('warning');
        $this->assertSame(LogLevel::WARNING, $level);
    }

    public function test_from_psr3_warn(): void
    {
        $level = LogLevel::fromPsr3('warn');
        $this->assertSame(LogLevel::WARNING, $level); // WARN maps to WARNING
    }

    public function test_from_psr3_error(): void
    {
        $level = LogLevel::fromPsr3('error');
        $this->assertSame(LogLevel::ERROR, $level);
    }

    public function test_from_psr3_critical(): void
    {
        $level = LogLevel::fromPsr3('critical');
        $this->assertSame(LogLevel::CRITICAL, $level);
    }

    public function test_from_psr3_alert(): void
    {
        $level = LogLevel::fromPsr3('alert');
        $this->assertSame(LogLevel::ALERT, $level);
    }

    public function test_from_psr3_emergency(): void
    {
        $level = LogLevel::fromPsr3('emergency');
        $this->assertSame(LogLevel::EMERGENCY, $level);
    }

    public function test_from_psr3_case_insensitive(): void
    {
        $this->assertSame(LogLevel::DEBUG, LogLevel::fromPsr3('DEBUG'));
        $this->assertSame(LogLevel::INFO, LogLevel::fromPsr3('INFO'));
        $this->assertSame(LogLevel::ERROR, LogLevel::fromPsr3('ERROR'));
    }

    public function test_from_psr3_unknown_level_defaults_to_info(): void
    {
        $level = LogLevel::fromPsr3('unknown');
        $this->assertSame(LogLevel::INFO, $level);
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertIsString(LogLevel::INFO->value);
    }

    public function test_cases_method_returns_all_enum_cases(): void
    {
        $cases = LogLevel::cases();
        $this->assertCount(8, $cases);
        $this->assertContains(LogLevel::DEBUG, $cases);
        $this->assertContains(LogLevel::INFO, $cases);
        $this->assertContains(LogLevel::NOTICE, $cases);
        $this->assertContains(LogLevel::WARNING, $cases);
        $this->assertContains(LogLevel::ERROR, $cases);
        $this->assertContains(LogLevel::CRITICAL, $cases);
        $this->assertContains(LogLevel::ALERT, $cases);
        $this->assertContains(LogLevel::EMERGENCY, $cases);
    }
}
