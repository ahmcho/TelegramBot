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
        // Clean up any rotated file left by rotation tests
        $rotated = $this->testLogFile . '.1';
        if (file_exists($rotated)) {
            unlink($rotated);
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

    public function test_get_max_bytes_returns_zero_by_default(): void
    {
        $this->assertSame(0, $this->handler->getMaxBytes());
    }

    public function test_get_max_bytes_returns_configured_value(): void
    {
        $handler = new FileLogHandler($this->testLogFile, false, 1024);
        $this->assertSame(1024, $handler->getMaxBytes());
    }

    public function test_rotation_renames_file_when_limit_exceeded(): void
    {
        $rotatedPath = $this->testLogFile . '.1';

        // Set a tiny limit so any write triggers rotation
        $handler = new FileLogHandler($this->testLogFile, false, 10);

        // Write enough to exceed 10 bytes
        $handler->write("first entry that is long\n");

        // Second write should trigger rotation
        $handler->write("second entry\n");

        $this->assertFileExists($rotatedPath);

        // New log file contains only the second entry
        $content = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('second entry', $content);
        $this->assertStringNotContainsString('first entry', $content);

        // Rotated file contains the first entry
        $rotatedContent = file_get_contents($rotatedPath);
        $this->assertStringContainsString('first entry', $rotatedContent);

        unlink($rotatedPath);
    }

    public function test_rotation_overwrites_previous_rotated_file(): void
    {
        $rotatedPath = $this->testLogFile . '.1';

        // maxBytes = 10; each entry is 6 bytes ("run N\n")
        // Two entries accumulate to 12 bytes before rotation fires on the third write
        $handler = new FileLogHandler($this->testLogFile, false, 10);

        $handler->write("run 1\n");  // 0 → 6B, no rotate
        $handler->write("run 2\n");  // 6B < 10, no rotate; file now 12B
        $handler->write("run 3\n");  // 12B >= 10: rotate → .1 gets "run1+run2", write run3 (6B)
        $handler->write("run 4\n");  // 6B < 10, no rotate; file now 12B
        $handler->write("run 5\n");  // 12B >= 10: rotate → .1 overwritten with "run3+run4", write run5

        $this->assertFileExists($rotatedPath);
        $rotatedContent = file_get_contents($rotatedPath);

        // Second rotation replaced the first .1 contents
        $this->assertStringContainsString('run 3', $rotatedContent);
        $this->assertStringContainsString('run 4', $rotatedContent);
        $this->assertStringNotContainsString('run 1', $rotatedContent);
        $this->assertStringNotContainsString('run 2', $rotatedContent);

        $currentContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('run 5', $currentContent);
        $this->assertStringNotContainsString('run 3', $currentContent);
        $this->assertStringNotContainsString('run 4', $currentContent);
    }

    public function test_no_rotation_when_max_bytes_is_zero(): void
    {
        $rotatedPath = $this->testLogFile . '.1';

        // Default: maxBytes = 0, no rotation regardless of size
        $bigEntry = str_repeat('X', 1000) . "\n";
        $this->handler->write($bigEntry);
        $this->handler->write($bigEntry);

        $this->assertFileDoesNotExist($rotatedPath);
    }
}
