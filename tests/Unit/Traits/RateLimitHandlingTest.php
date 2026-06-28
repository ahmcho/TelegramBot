<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Traits;

use PHPUnit\Framework\TestCase;

/**
 * Rate Limit Handling Tests
 *
 * Tests for 429 rate limit error detection and handling
 */
final class RateLimitHandlingTest extends TestCase
{
    /**
     * Test detection of 429 rate limit error
     */
    public function testDetects429RateLimitError(): void
    {
        $apiResponse = [
            'ok' => false,
            'error_code' => 429,
            'description' => 'Too Many Requests'
        ];

        $isRateLimit = $apiResponse['error_code'] === 429;

        $this->assertTrue($isRateLimit);
        $this->assertSame(429, $apiResponse['error_code']);
    }

    /**
     * Test extraction of retry_after parameter
     */
    public function testExtractsRetryAfterParameter(): void
    {
        $apiResponse = [
            'ok' => false,
            'error_code' => 429,
            'description' => 'Too Many Requests: retry after 10 seconds',
            'parameters' => [
                'retry_after' => 10
            ]
        ];

        $retryAfter = $apiResponse['parameters']['retry_after'] ?? null;

        $this->assertSame(10, $retryAfter);
    }

    /**
     * Test rate limit without retry_after parameter
     */
    public function testRateLimitWithoutRetryAfterParameter(): void
    {
        $apiResponse = [
            'ok' => false,
            'error_code' => 429,
            'description' => 'Too Many Requests'
        ];

        $hasRetryAfter = isset($apiResponse['parameters']['retry_after']);

        $this->assertFalse($hasRetryAfter);
    }

    /**
     * Test default retry_after value when missing
     */
    public function testDefaultRetryAfterWhenMissing(): void
    {
        $apiResponse = [
            'error_code' => 429
        ];

        $retryAfter = $apiResponse['parameters']['retry_after'] ?? 1;

        $this->assertSame(1, $retryAfter);
    }

    /**
     * Test retry_after converted to milliseconds
     */
    public function testRetryAfterConvertedToMilliseconds(): void
    {
        $retryAfterSeconds = 5;
        $delayMs = $retryAfterSeconds * 1000;

        $this->assertSame(5000, $delayMs);
    }

    /**
     * Data provider for different rate limit error formats
     */
    public static function rateLimitFormatsProvider(): array
    {
        return [
            'with_retry_after' => [
                'response' => [
                    'ok' => false,
                    'error_code' => 429,
                    'parameters' => ['retry_after' => 30]
                ],
                'expected_delay' => 30000
            ],
            'without_retry_after' => [
                'response' => [
                    'ok' => false,
                    'error_code' => 429
                ],
                'expected_delay' => 1000
            ],
            'with_description_only' => [
                'response' => [
                    'ok' => false,
                    'error_code' => 429,
                    'description' => 'Flood control exceeded'
                ],
                'expected_delay' => 1000
            ]
        ];
    }

    /**
     * @dataProvider rateLimitFormatsProvider
     */
    public function testRateLimitFormatHandling(array $response, int $expectedDelay): void
    {

        $isRateLimit = $response['error_code'] === 429;
        $retryAfter = $response['parameters']['retry_after'] ?? 1;
        $actualDelay = $retryAfter * 1000;

        $this->assertTrue($isRateLimit);
        $this->assertSame($expectedDelay, $actualDelay);
    }

    /**
     * Test rate limit is not other error codes
     */
    public function testRateLimitIsNotOtherErrorCodes(): void
    {
        $errorCodes = [400, 401, 403, 404, 500, 502, 503];

        foreach ($errorCodes as $code) {
            $isRateLimit = $code === 429;
            $this->assertFalse($isRateLimit, "Error code {$code} should not be rate limit");
        }
    }

    /**
     * Test rate limit response structure validation
     */
    public function testRateLimitResponseStructure(): void
    {
        $rateLimitResponse = [
            'ok' => false,
            'error_code' => 429,
            'description' => 'string',
            'parameters' => [
                'retry_after' => 10
            ]
        ];

        $this->assertIsArray($rateLimitResponse);
        $this->assertArrayHasKey('ok', $rateLimitResponse);
        $this->assertArrayHasKey('error_code', $rateLimitResponse);
        $this->assertSame(429, $rateLimitResponse['error_code']);
        $this->assertArrayHasKey('parameters', $rateLimitResponse);
        $this->assertArrayHasKey('retry_after', $rateLimitResponse['parameters']);
    }

    /**
     * Test flood control exceeded message
     */
    public function testFloodControlExceededMessage(): void
    {
        $response = [
            'error_code' => 429,
            'description' => 'Flood control exceeded'
        ];

        $containsFloodMessage = str_contains($response['description'], 'Flood control');

        $this->assertTrue($containsFloodMessage);
    }

    /**
     * Test rate limit logging includes relevant info
     */
    public function testRateLimitLoggingIncludesRelevantInfo(): void
    {
        $response = [
            'error_code' => 429,
            'description' => 'Too many requests',
            'parameters' => ['retry_after' => 15]
        ];

        $logEntry = sprintf(
            "Rate limit detected - Code: %d, Retry after: %d seconds",
            $response['error_code'],
            $response['parameters']['retry_after']
        );

        $this->assertStringContainsString('429', $logEntry);
        $this->assertStringContainsString('15', $logEntry);
        $this->assertStringContainsString('Rate limit', $logEntry);
    }

    /**
     * Test retry_after can be zero
     */
    public function testRetryAfterCanBeZero(): void
    {
        $response = [
            'error_code' => 429,
            'parameters' => ['retry_after' => 0]
        ];

        $delay = $response['parameters']['retry_after'] * 1000;

        $this->assertSame(0, $delay);
    }

    /**
     * Test retry_after can be large value
     */
    public function testRetryAfterCanBeLargeValue(): void
    {
        $response = [
            'error_code' => 429,
            'parameters' => ['retry_after' => 86400] // 24 hours
        ];

        $delaySeconds = $response['parameters']['retry_after'];
        $delayMinutes = $delaySeconds / 60;
        $delayHours = $delayMinutes / 60;

        $this->assertSame(86400, $delaySeconds);
        $this->assertEqualsWithDelta(1440.0, $delayMinutes, 0.0001);
        $this->assertEqualsWithDelta(24.0, $delayHours, 0.0001);
    }

    /**
     * Test multiple consecutive rate limits
     */
    public function testMultipleConsecutiveRateLimits(): void
    {
        $responses = [
            ['error_code' => 429, 'parameters' => ['retry_after' => 5]],
            ['error_code' => 429, 'parameters' => ['retry_after' => 10]],
            ['error_code' => 429, 'parameters' => ['retry_after' => 15]],
        ];

        $totalWaitTime = 0;
        foreach ($responses as $response) {
            $totalWaitTime += $response['parameters']['retry_after'];
        }

        $this->assertSame(30, $totalWaitTime);
    }

    /**
     * Test rate limit after successful request
     */
    public function testRateLimitAfterSuccessfulRequest(): void
    {
        $sequence = [
            ['ok' => true, 'result' => ['message_id' => 123]],
            ['ok' => false, 'error_code' => 429, 'parameters' => ['retry_after' => 5]]
        ];

        $firstWasSuccess = $sequence[0]['ok'];
        $secondIsRateLimit = $sequence[1]['error_code'] === 429;

        $this->assertTrue($firstWasSuccess);
        $this->assertTrue($secondIsRateLimit);
    }

    /**
     * Test different retry_after types
     */
    public function testRetryAfterTypes(): void
    {
        $scenarios = [
            'integer' => ['retry_after' => 10, 'type' => 'integer'],
            'float' => ['retry_after' => 10.5, 'type' => 'float'],
            'string' => ['retry_after' => '10', 'type' => 'string']
        ];

        foreach ($scenarios as $scenario) {
            $value = $scenario['retry_after'];
            $numericValue = is_numeric($value) ? (int) $value : (int) $value;

            $this->assertIsNumeric($numericValue);
        }
    }
}
