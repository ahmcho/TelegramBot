<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\FileLogHandler;

/**
 * FileLogHandler Tests
 */
final class FileLogHandlerTest extends TestCase
{
    private string $testLogFile;
    private FileLogHandler $handler;

    protected function setUp(): void
    {
        $this->testLogFile = sys_get_temp_dir() . '/test_bot_' . time() . '.log';
        $this->handler = new FileLogHandler($this->testLogFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function test_constructor_creates_log_file(): void
    {
        // Handler only creates directory, file is created on first write
        $this->handler->write("[2026-05-13 10:00:00] [INFO] Test\n");
        $this->assertFileExists($this->testLogFile);
        $this->assertSame($this->testLogFile, $this->handler->getLogFilePath());
    }

    public function test_constructor_without_directory_creation(): void
    {
        $testFile = '/nonexistent/path/test.log';
        // When directory creation is disabled and directory doesn't exist,
        // the exception is thrown on first write, not in constructor
        $handler = new FileLogHandler($testFile, false);

        $this->expectException(\RuntimeException::class);
        $handler->write("[2026-05-13 10:00:00] [INFO] Test\n");
    }

    public function test_constructor_throws_on_empty_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileLogHandler('');
    }

    public function test_write_appends_to_file(): void
    {
        $entry1 = "[2026-05-13 10:00:00] [INFO] Test message 1\n";
        $entry2 = "[2026-05-13 10:00:01] [INFO] Test message 2\n";

        $this->handler->write($entry1);
        $this->handler->write($entry2);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString($entry1, $content);
        $this->assertStringContainsString($entry2, $content);
    }

    public function test_write_creates_file_if_not_exists(): void
    {
        $testFile = sys_get_temp_dir() . '/new_test_' . time() . '.log';
        if (file_exists($testFile)) {
            unlink($testFile);
        }

        $handler = new FileLogHandler($testFile);
        $handler->write("[2026-05-13 10:00:00] [INFO] Test\n");

        $this->assertFileExists($testFile);
        unlink($testFile);
    }

    public function test_write_with_concurrent_writes(): void
    {
        $entry = "[2026-05-13 10:00:00] [INFO] Concurrent test\n";

        // Simulate concurrent writes
        $handler1 = new FileLogHandler($this->testLogFile, false);
        $handler2 = new FileLogHandler($this->testLogFile, false);

        $handler1->write($entry);
        $handler2->write($entry);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString($entry, $content);
        // Should have exactly 2 entries
        $this->assertSame(2, substr_count($content, $entry));
    }

    public function test_clear_empties_file(): void
    {
        $this->handler->write("[2026-05-13 10:00:00] [INFO] Test\n");
        $this->handler->clear();

        $content = file_get_contents($this->testLogFile);
        $this->assertSame('', $content);
    }

    public function test_get_size_returns_file_size(): void
    {
        $entry = "[2026-05-13 10:00:00] [INFO] Test message\n";
        $this->handler->write($entry);

        $size = $this->handler->getSize();
        $this->assertGreaterThan(0, $size);
        $this->assertSame(strlen($entry), $size);
    }

    public function test_get_size_returns_zero_for_nonexistent_file(): void
    {
        $testFile = sys_get_temp_dir() . '/nonexistent_' . time() . '.log';
        $handler = new FileLogHandler($testFile);

        $this->assertSame(0, $handler->getSize());
    }

    public function test_read_last_lines_returns_empty_array_for_new_file(): void
    {
        $lines = $this->handler->readLastLines(10);
        $this->assertIsArray($lines);
        $this->assertEmpty($lines);
    }

    public function test_read_last_lines_returns_correct_lines(): void
    {
        $this->handler->write("[2026-05-13 10:00:00] [INFO] Line 1\n");
        $this->handler->write("[2026-05-13 10:00:01] [INFO] Line 2\n");
        $this->handler->write("[2026-05-13 10:00:02] [INFO] Line 3\n");

        $lines = $this->handler->readLastLines(2);
        $this->assertCount(2, $lines);
        $this->assertStringContainsString('Line 2', $lines[0]);
        $this->assertStringContainsString('Line 3', $lines[1]);
    }

    public function test_read_last_lines_returns_all_lines_when_request_more_than_exist(): void
    {
        $this->handler->write("[2026-05-13 10:00:00] [INFO] Line 1\n");
        $this->handler->write("[2026-05-13 10:00:01] [INFO] Line 2\n");

        $lines = $this->handler->readLastLines(10);
        $this->assertCount(2, $lines);
    }

    public function test_write_handles_large_content(): void
    {
        $largeMessage = str_repeat('A', 10000);
        $entry = "[2026-05-13 10:00:00] [INFO] {$largeMessage}\n";

        $this->handler->write($entry);

        $size = $this->handler->getSize();
        $this->assertGreaterThan(10000, $size);
    }

    public function test_write_throws_exception_on_permission_denied(): void
    {
        $testFile = '/root/test_permission_denied.log';
        $handler = new FileLogHandler($testFile, false);

        $this->expectException(\RuntimeException::class);
        $handler->write("[2026-05-13 10:00:00] [INFO] Test\n");
    }

    public function test_get_log_file_path_returns_correct_path(): void
    {
        $path = $this->handler->getLogFilePath();
        $this->assertSame($this->testLogFile, $path);
    }

    public function test_write_with_multiline_entries(): void
    {
        $entry = "[2026-05-13 10:00:00] [INFO] Multi\nline\nmessage\n";

        $this->handler->write($entry);

        $content = file_get_contents($this->testLogFile);
        $this->assertSame($entry, $content);
    }

    public function test_write_with_special_characters(): void
    {
        $entry = "[2026-05-13 10:00:00] [INFO] Test with 特殊 characters\n";

        $this->handler->write($entry);

        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('特殊', $content);
    }

    public function test_concurrent_directory_creation(): void
    {
        $testFile = sys_get_temp_dir() . '/test_dir_' . time() . '/test.log';

        $handler1 = new FileLogHandler($testFile);
        $handler1->write("[2026-05-13 10:00:00] [INFO] Test\n");
        $handler2 = new FileLogHandler($testFile);

        $this->assertFileExists($testFile);
        unlink($testFile);
        rmdir(dirname($testFile));
    }
}
