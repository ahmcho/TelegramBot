<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Bulk;

use AhmCho\Telegram\Bulk\BulkResult;
use AhmCho\Telegram\Bulk\BulkSendException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BulkSendException
 */
class BulkSendExceptionTest extends TestCase
{
    public function test_exception_can_be_created_with_message_and_result(): void
    {
        $result = BulkResult::empty();
        $exception = new BulkSendException('Test message', $result);

        $this->assertInstanceOf(BulkSendException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function test_exception_getResult_returns_attached_bulk_result(): void
    {
        $result = BulkResult::fromRawResults([
            ['success' => true, 'chat_id' => 123, 'message_id' => 1, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 456, 'message_id' => null, 'data' => null, 'error' => 'Failed'],
        ]);

        $exception = new BulkSendException('Bulk operation failed', $result);
        $retrievedResult = $exception->getResult();

        $this->assertSame($result, $retrievedResult);
        $this->assertEquals(2, $retrievedResult->total);
        $this->assertEquals(1, $retrievedResult->successful);
        $this->assertEquals(1, $retrievedResult->failed);
    }

    public function test_exception_extends_telegram_exception(): void
    {
        $result = BulkResult::empty();
        $exception = new BulkSendException('Test', $result);

        $this->assertInstanceOf(\AhmCho\Telegram\Exception\TelegramException::class, $exception);
    }

    public function test_exception_message_format_includes_failure_counts(): void
    {
        $result = BulkResult::fromRawResults([
            ['success' => true, 'chat_id' => 123, 'message_id' => 1, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 456, 'message_id' => 2, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 789, 'message_id' => null, 'data' => null, 'error' => 'Error 1'],
            ['success' => false, 'chat_id' => 999, 'message_id' => null, 'data' => null, 'error' => 'Error 2'],
            ['success' => false, 'chat_id' => 111, 'message_id' => null, 'data' => null, 'error' => 'Error 3'],
        ]);

        $exception = new BulkSendException(
            "Bulk operation completed with {$result->failed} failures out of {$result->total}",
            $result
        );

        $this->assertStringContainsString('3 failures', $exception->getMessage());
        $this->assertStringContainsString('out of 5', $exception->getMessage());
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $result = BulkResult::fromRawResults([
            ['success' => false, 'chat_id' => 123, 'message_id' => null, 'data' => null, 'error' => 'Test error'],
        ]);

        try {
            throw new BulkSendException('Test exception', $result);
            $this->fail('Expected BulkSendException to be thrown');
        } catch (BulkSendException $e) {
            $this->assertEquals('Test exception', $e->getMessage());
            $this->assertSame($result, $e->getResult());
        }
    }

    public function test_exception_with_empty_result(): void
    {
        $result = BulkResult::empty();
        $exception = new BulkSendException(
            "Bulk operation completed with {$result->failed} failures out of {$result->total}",
            $result
        );

        $this->assertEquals('Bulk operation completed with 0 failures out of 0', $exception->getMessage());
        $this->assertSame($result, $exception->getResult());
        $this->assertTrue($exception->getResult()->isSuccess());
    }
}
