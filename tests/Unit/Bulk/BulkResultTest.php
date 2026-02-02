<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Bulk;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bulk\BulkResult;

/**
 * Bulk Result Tests
 *
 * Tests fromRawResults calculation, isSuccess/hasFailures,
 * getSuccessRate with edge cases, count implementation,
 * getFailedResults/getSuccessfulResults, and empty static factory.
 */
final class BulkResultTest extends TestCase
{
    public function test_fromRawResults_calculates_success(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 2, 'message_id' => 101, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(2, $result->total);
        $this->assertSame(2, $result->successful);
        $this->assertSame(0, $result->failed);
    }

    public function test_fromRawResults_calculates_failures(): void
    {
        $results = [
            ['success' => false, 'chat_id' => 1, 'message_id' => null, 'data' => null, 'error' => 'Error 1'],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error 2'],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(2, $result->total);
        $this->assertSame(0, $result->successful);
        $this->assertSame(2, $result->failed);
    }

    public function test_fromRawResults_calculates_mixed(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error'],
            ['success' => true, 'chat_id' => 3, 'message_id' => 102, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->successful);
        $this->assertSame(1, $result->failed);
    }

    public function test_isSuccess_returns_true_when_no_failures(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertTrue($result->isSuccess());
    }

    public function test_isSuccess_returns_false_when_has_failures(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error'],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertFalse($result->isSuccess());
    }

    public function test_hasFailures_returns_true_when_failed(): void
    {
        $results = [
            ['success' => false, 'chat_id' => 1, 'message_id' => null, 'data' => null, 'error' => 'Error'],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertTrue($result->hasFailures());
    }

    public function test_hasFailures_returns_false_when_all_success(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertFalse($result->hasFailures());
    }

    public function test_getSuccessRate_all_success(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 2, 'message_id' => 101, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(100.0, $result->getSuccessRate());
    }

    public function test_getSuccessRate_half_success(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error'],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(50.0, $result->getSuccessRate());
    }

    public function test_getSuccessRate_zero_total(): void
    {
        $result = BulkResult::empty();

        $this->assertSame(0.0, $result->getSuccessRate());
    }

    public function test_count_returns_total(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 2, 'message_id' => 101, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 3, 'message_id' => 102, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertCount(3, $result);
        $this->assertSame(3, $result->count());
    }

    public function test_getFailedResults_returns_only_failed(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error'],
        ];

        $result = BulkResult::fromRawResults($results);
        $failed = $result->getFailedResults();

        $this->assertCount(1, $failed);
        $failedArray = array_values($failed);
        $this->assertSame(2, $failedArray[0]['chat_id']);
    }

    public function test_getSuccessfulResults_returns_only_success(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error'],
        ];

        $result = BulkResult::fromRawResults($results);
        $successful = $result->getSuccessfulResults();

        $this->assertCount(1, $successful);
        $this->assertSame(1, $successful[0]['chat_id']);
    }

    public function test_empty_returns_empty_result(): void
    {
        $result = BulkResult::empty();

        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->successful);
        $this->assertSame(0, $result->failed);
        $this->assertSame([], $result->results);
        $this->assertSame([], $result->errors);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->hasFailures());
    }

    public function test_errors_array_contains_error_messages(): void
    {
        $results = [
            ['success' => false, 'chat_id' => 1, 'message_id' => null, 'data' => null, 'error' => 'Error 1'],
            ['success' => false, 'chat_id' => 2, 'message_id' => null, 'data' => null, 'error' => 'Error 2'],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertSame(['Error 1', 'Error 2'], $result->errors);
    }

    public function test_result_is_readonly(): void
    {
        $result = BulkResult::empty();

        $reflection = new \ReflectionClass($result);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_countable_interface(): void
    {
        $results = [
            ['success' => true, 'chat_id' => 1, 'message_id' => 100, 'data' => [], 'error' => null],
            ['success' => true, 'chat_id' => 2, 'message_id' => 101, 'data' => [], 'error' => null],
        ];

        $result = BulkResult::fromRawResults($results);

        $this->assertInstanceOf(\Countable::class, $result);
    }
}
