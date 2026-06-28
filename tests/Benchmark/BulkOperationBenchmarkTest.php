<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Benchmark;

use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use PHPUnit\Framework\TestCase;

/**
 * Bulk Operation Performance Benchmarks
 *
 * Performance tests for bulk operations
 */
class BulkOperationBenchmarkTest extends TestCase
{
    private BotConfig $config;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->config = new BotConfig(token: 'test_token');
        $this->httpClient = new MockHttpClient($this->config);
    }

    /**
     * Benchmark bulk operation with different batch sizes
     *
     * @dataProvider batchSizeProvider
     */
    public function testBulkOperationWithDifferentBatchSizes(int $messageCount, int $maxConcurrent): void
    {
        $messages = [];
        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = [
                'chat_id' => 100 + $i,
                'text' => "Test message {$i}"
            ];
        }

        $bulkManager = new BulkOperationManager($this->httpClient, $this->config);

        $startTime = microtime(true);
        $result = $bulkManager->executeBulk($messages, ApiMethod::SEND_MESSAGE, $maxConcurrent);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;
        $messagesPerSecond = $messageCount / $duration;

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertSame($messageCount, $result['total']);

        // Log performance metrics
        $this->addBenchmarkResult(
            "Batch size: {$messageCount}, Concurrent: {$maxConcurrent}",
            [
                'duration' => $duration,
                'messages_per_second' => $messagesPerSecond,
                'total_messages' => $messageCount
            ]
        );
    }

    /**
     * Data provider for batch sizes
     */
    public static function batchSizeProvider(): array
    {
        return [
            'small_batch' => [10, 5],
            'medium_batch' => [50, 10],
            'large_batch' => [100, 20],
            'extra_large_batch' => [200, 30],
        ];
    }

    /**
     * Benchmark sequential vs concurrent execution
     */
    public function testSequentialVsConcurrentExecution(): void
    {
        $messageCount = 50;

        // Sequential (max_concurrent = 1)
        $sequentialStart = microtime(true);
        $this->runBulkOperation($messageCount, 1);
        $sequentialDuration = microtime(true) - $sequentialStart;

        // Concurrent (max_concurrent = 10)
        $concurrentStart = microtime(true);
        $this->runBulkOperation($messageCount, 10);
        $concurrentDuration = microtime(true) - $concurrentStart;

        $speedup = $sequentialDuration / $concurrentDuration;

        $this->assertGreaterThan(1.0, $speedup, "Concurrent should be faster than sequential");

        $this->addBenchmarkResult(
            'Sequential vs Concurrent',
            [
                'sequential_duration' => $sequentialDuration,
                'concurrent_duration' => $concurrentDuration,
                'speedup_factor' => $speedup,
                'message_count' => $messageCount
            ]
        );
    }

    /**
     * Benchmark memory usage for large batches
     */
    public function testMemoryUsageForLargeBatches(): void
    {
        $messageCounts = [100, 500, 1000];

        foreach ($messageCounts as $count) {
            $memoryBefore = memory_get_usage(true);

            $messages = [];
            for ($i = 0; $i < $count; $i++) {
                $messages[] = [
                    'chat_id' => 100 + $i,
                    'text' => "Test message {$i}"
                ];
            }

            $bulkManager = new BulkOperationManager($this->httpClient, $this->config);
            $bulkManager->executeBulk($messages, ApiMethod::SEND_MESSAGE, 30);

            $memoryAfter = memory_get_usage(true);
            $memoryUsed = $memoryAfter - $memoryBefore;
            $memoryPerMessage = $memoryUsed / $count;

            $this->addBenchmarkResult(
                "Memory usage for {$count} messages",
                [
                    'memory_used_bytes' => $memoryUsed,
                    'memory_per_message_bytes' => $memoryPerMessage,
                    'peak_memory_bytes' => memory_get_peak_usage(true)
                ]
            );
        }

        $this->assertTrue(true);
    }

    /**
     * Benchmark with different delay settings
     */
    public function testPerformanceWithDifferentDelays(): void
    {
        $messageCount = 50;
        $delays = [0, 50, 100, 200];

        foreach ($delays as $delay) {
            $startTime = microtime(true);

            $messages = [];
            for ($i = 0; $i < $messageCount; $i++) {
                $messages[] = [
                    'chat_id' => 100 + $i,
                    'text' => "Test message {$i}"
                ];
            }

            $bulkManager = new BulkOperationManager($this->httpClient, $this->config);
            $bulkManager->executeBulk(
                $messages,
                ApiMethod::SEND_MESSAGE,
                10,
                $delay
            );

            $duration = microtime(true) - $startTime;

            $this->addBenchmarkResult(
                "Delay: {$delay}ms",
                [
                    'duration_seconds' => $duration,
                    'messages_per_second' => $messageCount / $duration,
                    'delay_ms' => $delay
                ]
            );
        }

        $this->assertTrue(true);
    }

    /**
     * Benchmark error handling in bulk operations
     */
    public function testErrorHandlingPerformance(): void
    {
        $messageCount = 100;
        $failureRate = 0.1; // 10% failure rate

        $messages = [];
        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = [
                'chat_id' => ($i % 10 === 0) ? 999999 : (100 + $i), // Every 10th will fail
                'text' => "Test message {$i}"
            ];
        }

        $startTime = microtime(true);

        $bulkManager = new BulkOperationManager($this->httpClient, $this->config);
        $result = $bulkManager->executeBulk($messages, ApiMethod::SEND_MESSAGE, 10);

        $duration = microtime(true) - $startTime;

        $this->addBenchmarkResult(
            'Error handling performance',
            [
                'duration_seconds' => $duration,
                'total_messages' => $result['total'],
                'successful' => $result['successful'],
                'failed' => $result['failed'],
                'success_rate' => $result['successful'] / $result['total']
            ]
        );
    }

    /**
     * Helper to run bulk operation
     */
    private function runBulkOperation(int $messageCount, int $maxConcurrent): void
    {
        $messages = [];
        for ($i = 0; $i < $messageCount; $i++) {
            $messages[] = [
                'chat_id' => 100 + $i,
                'text' => "Test message {$i}"
            ];
        }

        $bulkManager = new BulkOperationManager($this->httpClient, $this->config);
        $bulkManager->executeBulk($messages, ApiMethod::SEND_MESSAGE, $maxConcurrent);
    }

    /**
     * Store benchmark results
     */
    private function addBenchmarkResult(string $testName, array $metrics): void
    {
        // In a real scenario, this could write to a file or database
        static $results = [];
        $results[$testName] = $metrics;
    }
}
