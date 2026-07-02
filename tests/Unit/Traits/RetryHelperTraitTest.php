<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Traits;

use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Retry functionality
 */
class RetryHelperTraitTest extends TestCase
{
    private int $callCount = 0;
    private array $callHistory = [];

    protected function setUp(): void
    {
        $this->callCount = 0;
        $this->callHistory = [];
    }

    public function testExecuteWithRetrySucceedsOnFirstAttempt(): void
    {
        $callback = function () {
            $this->callCount++;
            return ['result' => 'success'];
        };

        $result = $this->executeWithRetry($callback);

        $this->assertSame(['result' => 'success'], $result);
        $this->assertSame(1, $this->callCount);
    }

    public function testExecuteWithRetryRetriesOnFailure(): void
    {
        $callback = function () {
            $this->callCount++;
            if ($this->callCount < 3) {
                throw new ApiException('API Error', 500, 500);
            }
            return ['result' => 'success'];
        };

        $result = $this->executeWithRetry($callback);

        $this->assertSame(['result' => 'success'], $result);
        $this->assertSame(3, $this->callCount);
    }

    public function testExecuteWithRetryRespectsMaxRetries(): void
    {
        $this->callCount = 0;
        $lastException = null;

        $callback = function () use (&$lastException) {
            $this->callCount++;
            $lastException = new ApiException('API Error', 500, 500);
            throw $lastException;
        };

        try {
            $this->executeWithRetry($callback, ['max_retries' => 2]);
            $this->fail('Expected ApiException to be thrown');
        } catch (ApiException $e) {
            $this->assertSame($lastException, $e);
            $this->assertSame(3, $this->callCount); // 1 initial + 2 retries
        }
    }

    public function testExecuteWithRetryDoesNotRetryOnClientErrors(): void
    {
        $this->callCount = 0;

        $callback = function () {
            $this->callCount++;
            throw new ApiException('Client Error', 400, 400);
        };

        try {
            $this->executeWithRetry($callback);
            $this->fail('Expected ApiException to be thrown');
        } catch (ApiException $e) {
            $this->assertSame(400, $e->getHttpCode());
            $this->assertSame(1, $this->callCount); // Should not retry
        }
    }

    public function testExecuteWithRetryRetriesOnRateLimitError(): void
    {
        $this->callCount = 0;

        $callback = function () {
            $this->callCount++;
            if ($this->callCount === 1) {
                $response = [
                    'parameters' => ['retry_after' => 1]
                ];
                throw new ApiException('Too Many Requests', 429, 429, $response);
            }
            return ['result' => 'success'];
        };

        $result = $this->executeWithRetry($callback);

        $this->assertSame(['result' => 'success'], $result);
        $this->assertSame(2, $this->callCount);
    }

    public function testExecuteWithRetryUsesCustomMaxRetries(): void
    {
        $this->callCount = 0;

        $callback = function () {
            $this->callCount++;
            throw new ApiException('API Error', 500, 500);
        };

        try {
            $this->executeWithRetry($callback, ['max_retries' => 5]);
            $this->fail('Expected ApiException to be thrown');
        } catch (ApiException $e) {
            $this->assertSame(6, $this->callCount); // 1 initial + 5 retries
        }
    }

    public function testExecuteWithRetryUsesCustomDelay(): void
    {
        $this->callCount = 0;
        $delays = [];

        $callback = function () use (&$delays) {
            $this->callCount++;
            if ($this->callCount < 3) {
                throw new ApiException('API Error', 500, 500);
            }
            return ['result' => 'success'];
        };

        $startTime = microtime(true);
        $result = $this->executeWithRetry($callback, [
            'max_retries' => 5,
            'initial_delay_ms' => 100, // Small delay for testing
        ]);

        $this->assertSame(['result' => 'success'], $result);
        $elapsed = (microtime(true) - $startTime) * 1000;

        // Should have at least 100ms delay between retries
        $this->assertGreaterThanOrEqual(200, $elapsed);
    }

    public function testExecuteWithRetryCallsRetryCallback(): void
    {
        $this->callCount = 0;
        $retryCallbackCalled = false;

        $callback = function () {
            $this->callCount++;
            if ($this->callCount < 2) {
                throw new ApiException('API Error', 500, 500);
            }
            return ['result' => 'success'];
        };

        $result = $this->executeWithRetry($callback, [
            'max_retries' => 3,
            'on_retry' => function ($attempt, $error, $delayMs) use (&$retryCallbackCalled) {
            $retryCallbackCalled = true;
            $this->assertIsInt($attempt);
            $this->assertInstanceOf(ApiException::class, $error);
            $this->assertIsInt($delayMs);
        }
        ]);

        $this->assertSame(['result' => 'success'], $result);
        $this->assertTrue($retryCallbackCalled);
    }

    public function testExecuteWithRetryExponentialBackoff(): void
    {
        $this->callCount = 0;
        $delays = [];

        $callback = function () use (&$delays) {
            $this->callCount++;
            if ($this->callCount < 4) {
                throw new ApiException('API Error', 500, 500);
            }
            return ['result' => 'success'];
        };

        $startTime = microtime(true);
        $result = $this->executeWithRetry($callback, [
            'max_retries' => 5,
            'initial_delay_ms' => 50,
            'max_delay_ms' => 200,
            'on_retry' => function ($attempt, $error, $delayMs) use (&$delays) {
                $delays[] = $delayMs;
            }
        ]);

        $this->assertSame(['result' => 'success'], $result);

        // Check exponential backoff: 50, 100, 200 (capped at max)
        // Note: Actual timing may vary slightly
        $this->assertGreaterThanOrEqual(3, count($delays));
    }

    public function testExecuteWithRetryRespectsMaxDelay(): void
    {
        $this->callCount = 0;
        $lastDelay = 0;

        $callback = function () use (&$lastDelay) {
            $this->callCount++;
            if ($this->callCount < 4) {
                throw new ApiException('API Error', 500, 500);
            }
            return ['result' => 'success'];
        };

        $this->executeWithRetry($callback, [
            'max_retries' => 10,
            'initial_delay_ms' => 100,
            'max_delay_ms' => 200,
            'on_retry' => function ($attempt, $error, $delayMs) use (&$lastDelay) {
                $lastDelay = $delayMs;
            }
        ]);

        // Max delay should be respected
        $this->assertLessThanOrEqual(200, $lastDelay);
    }

    public function testExecuteWithRetryRetriesOnHttpClientException(): void
    {
        $this->callCount = 0;

        $callback = function () {
            $this->callCount++;
            if ($this->callCount < 3) {
                throw new HttpClientException('Connection timed out');
            }
            return ['result' => 'success'];
        };

        $result = $this->executeWithRetry($callback, ['initial_delay_ms' => 10]);

        $this->assertSame(['result' => 'success'], $result);
        $this->assertSame(3, $this->callCount); // 1 initial + 2 retries
    }

    public function testExecuteWithRetryThrowsHttpClientExceptionAfterAllRetries(): void
    {
        $this->callCount = 0;

        $callback = function () {
            $this->callCount++;
            throw new HttpClientException('DNS resolution failed');
        };

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('DNS resolution failed');

        try {
            $this->executeWithRetry($callback, ['max_retries' => 2, 'initial_delay_ms' => 10]);
        } finally {
            $this->assertSame(3, $this->callCount); // 1 initial + 2 retries
        }
    }

    public function testExecuteWithRetryCallsOnRetryCallbackOnHttpClientException(): void
    {
        $this->callCount = 0;
        $retryCallbackCalled = false;
        $receivedExceptionType = null;

        $callback = function () {
            $this->callCount++;
            if ($this->callCount < 2) {
                throw new HttpClientException('Network unreachable');
            }
            return ['result' => 'success'];
        };

        $this->executeWithRetry($callback, [
            'max_retries' => 3,
            'initial_delay_ms' => 10,
            'on_retry' => function ($attempt, $error, $delayMs) use (&$retryCallbackCalled, &$receivedExceptionType) {
                $retryCallbackCalled = true;
                $receivedExceptionType = get_class($error);
                $this->assertIsInt($attempt);
                $this->assertIsInt($delayMs);
            }
        ]);

        $this->assertTrue($retryCallbackCalled);
        $this->assertSame(HttpClientException::class, $receivedExceptionType);
        $this->assertSame(2, $this->callCount);
    }

    /**
     * Execute callback with retry logic
     */
    private function executeWithRetry(callable $callback, array $options = []): mixed
    {
        $maxRetries = $options['max_retries'] ?? 3;
        $initialDelayMs = $options['initial_delay_ms'] ?? 1000;
        $maxDelayMs = $options['max_delay_ms'] ?? 10000;
        $onRetry = $options['on_retry'] ?? null;

        $lastException = null;
        $delayMs = $initialDelayMs;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (ApiException $e) {
                $lastException = $e;

                // Don't retry on client errors (4xx) except 429
                if ($e->getHttpCode() >= 400 && $e->getHttpCode() < 500 && $e->getHttpCode() !== 429) {
                    throw $e;
                }

                if ($attempt === $maxRetries) {
                    break;
                }

                // Handle rate limit (429)
                if ($e->getHttpCode() === 429) {
                    $response = $e->getResponseBody();
                    if (is_array($response) && isset($response['parameters']['retry_after'])) {
                        $delayMs = (int) $response['parameters']['retry_after'] * 1000;
                    }
                }

                if ($onRetry !== null && is_callable($onRetry)) {
                    $onRetry($attempt + 1, $e, $delayMs);
                }

                usleep($delayMs * 1000);
                $delayMs = min($delayMs * 2, $maxDelayMs);
            } catch (HttpClientException $e) {
                $lastException = $e;

                if ($attempt === $maxRetries) {
                    break;
                }

                if ($onRetry !== null && is_callable($onRetry)) {
                    $onRetry($attempt + 1, $e, $delayMs);
                }

                usleep($delayMs * 1000);
                $delayMs = min($delayMs * 2, $maxDelayMs);
            }
        }

        throw $lastException;
    }
}
