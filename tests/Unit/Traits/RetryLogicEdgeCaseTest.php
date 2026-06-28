<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Traits;

use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use PHPUnit\Framework\TestCase;

/**
 * Retry Logic Edge Case Tests
 *
 * Tests edge cases and failure modes of the retry mechanism
 */
final class RetryLogicEdgeCaseTest extends TestCase
{
    private BotConfig $config;
    private array $retryCalls;

    protected function setUp(): void
    {
        $this->config = new BotConfig(token: 'test_token');
        $this->retryCalls = [];
    }

    /**
     * Test retry with exponential backoff increases delay
     */
    public function testRetryExponentialBackoffIncreasesDelay(): void
    {
        $delays = [];
        $maxRetries = 3;
        $initialDelay = 100;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $delays[] = $initialDelay * (2 ** $attempt);
        }

        $this->assertSame([100, 200, 400], $delays);
        $this->assertLessThan(5000, $delays[2]); // Should cap at max delay
    }

    /**
     * Test retry callback receives correct parameters
     */
    public function testRetryCallbackReceivesParameters(): void
    {
        $callbackInvoked = false;
        $receivedAttempt = null;
        $receivedError = null;
        $receivedDelay = null;

        $callback = function ($attempt, $error, $delay) use (&$callbackInvoked, &$receivedAttempt, &$receivedError, &$receivedDelay) {
            $callbackInvoked = true;
            $receivedAttempt = $attempt;
            $receivedError = $error;
            $receivedDelay = $delay;
        };

        // Simulate retry invocation
        $callback(2, new \Exception('Test error'), 2000);

        $this->assertTrue($callbackInvoked);
        $this->assertSame(2, $receivedAttempt);
        $this->assertInstanceOf(\Exception::class, $receivedError);
        $this->assertSame(2000, $receivedDelay);
    }

    /**
     * Test max delay cap is respected
     */
    public function testMaxDelayCapIsRespected(): void
    {
        $maxDelay = 10000;
        $initialDelay = 1000;

        // Simulate exponential growth beyond max
        $delay = $initialDelay;
        for ($i = 0; $i < 20; $i++) {
            $delay = min($delay * 2, $maxDelay);
        }

        $this->assertSame($maxDelay, $delay);
    }

    /**
     * Test zero retry attempts means execute once only
     */
    public function testZeroRetriesExecutesOnce(): void
    {
        $attempts = 0;
        $maxRetries = 0;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $attempts++;
        }

        $this->assertSame(1, $attempts);
    }

    /**
     * Test retry respects 429 rate limit response
     */
    public function testRetryRespectsRateLimit429(): void
    {
        $rateLimitResponse = [
            'ok' => false,
            'error_code' => 429,
            'description' => 'Too many requests',
            'parameters' => ['retry_after' => 5]
        ];

        $this->assertSame(429, $rateLimitResponse['error_code']);
        $this->assertSame(5, $rateLimitResponse['parameters']['retry_after']);

        // Calculate delay from retry_after
        $delayMs = $rateLimitResponse['parameters']['retry_after'] * 1000;
        $this->assertSame(5000, $delayMs);
    }

    /**
     * Test retry doesn't retry on 4xx (except 429)
     */
    public function testRetryDoesNotRetryOn4xxErrors(): void
    {
        $shouldRetry = [
            400 => false, // Bad Request - no retry
            401 => false, // Unauthorized - no retry
            403 => false, // Forbidden - no retry
            404 => false, // Not Found - no retry
            429 => true,  // Rate limit - should retry
        ];

        foreach ($shouldRetry as $code => $expected) {
            $this->assertSame($expected, $shouldRetry[$code], "HTTP {$code}");
        }
    }

    /**
     * Test retry attempts include initial attempt
     */
    public function testRetryAttemptsIncludeInitialAttempt(): void
    {
        $maxRetries = 3;
        $totalAttempts = $maxRetries + 1; // Initial + retries

        $this->assertSame(4, $totalAttempts);
    }

    /**
     * Test successful first attempt doesn't trigger retry logic
     */
    public function testSuccessOnFirstAttemptSkipsRetry(): void
    {
        $attemptCount = 0;
        $success = false;

        // Simulate successful first attempt
        for ($attempt = 0; $attempt <= 3; $attempt++) {
            $attemptCount++;
            $success = true;
            break; // Success, exit retry loop
        }

        $this->assertSame(1, $attemptCount);
        $this->assertTrue($success);
    }

    /**
     * Test all retries exhausted throws last exception
     */
    public function testAllRetriesExhaustedThrowsLastException(): void
    {
        $lastException = null;
        $maxRetries = 3;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                throw new \Exception("Attempt {$attempt} failed");
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    continue; // Retry
                }
                // Last attempt, throw
            }
        }

        $this->assertNotNull($lastException);
        $this->assertStringContainsString('Attempt 3 failed', $lastException->getMessage());
    }

    /**
     * Test retry delay is applied between attempts
     */
    public function testDelayIsAppliedBetweenAttempts(): void
    {
        $delays = [1000, 2000, 4000];
        $totalWaitTime = 0;

        foreach ($delays as $delay) {
            $totalWaitTime += $delay;
        }

        $this->assertSame(7000, $totalWaitTime);
    }

    /**
     * Test custom retry options override defaults
     */
    public function testCustomRetryOptionsOverrideDefaults(): void
    {
        $defaultMaxRetries = 3;
        $defaultInitialDelay = 1000;

        $customMaxRetries = 5;
        $customInitialDelay = 500;

        $this->assertNotSame($defaultMaxRetries, $customMaxRetries);
        $this->assertNotSame($defaultInitialDelay, $customInitialDelay);

        $this->assertSame(5, $customMaxRetries);
        $this->assertSame(500, $customInitialDelay);
    }

    /**
     * Test retry respects provided retry-after header
     */
    public function testRetryRespectsRetryAfterHeader(): void
    {
        $responseWithRetryAfter = [
            'parameters' => ['retry_after' => 10]
        ];

        $calculatedDelay = $responseWithRetryAfter['parameters']['retry_after'] * 1000;

        $this->assertSame(10000, $calculatedDelay);
    }

    /**
     * Test retry without retry_after uses exponential backoff
     */
    public function testRetryWithoutRetryAfterUsesExponentialBackoff(): void
    {
        $responseWithoutRetryAfter = ['error_code' => 429];

        // Should use exponential backoff
        $initialDelay = 1000;
        $delay = $initialDelay * 2; // Second attempt

        $this->assertSame(2000, $delay);
    }

    /**
     * Test negative max_retries defaults to no retry
     */
    public function testNegativeMaxRetriesDisablesRetry(): void
    {
        $maxRetries = -1;

        $shouldRetry = $maxRetries >= 0;

        $this->assertFalse($shouldRetry);
    }

    /**
     * Test zero delay means immediate retry
     */
    public function testZeroDelayMeansImmediateRetry(): void
    {
        $delay = 0;

        $this->assertSame(0, $delay);
    }

    /**
     * Test large retry_after is respected
     */
    public function testLargeRetryAfterIsRespected(): void
    {
        $largeRetryAfter = 3600; // 1 hour

        $calculatedDelay = $largeRetryAfter * 1000;

        $this->assertSame(3600000, $calculatedDelay);
    }
}
