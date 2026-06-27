<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Traits;

use AhmCho\Telegram\Exception\ApiException;

/**
 * Retry Helper Trait
 *
 * Provides automatic retry functionality with exponential backoff
 * and rate limit handling for Telegram API requests
 */
trait RetryHelperTrait
{
    /**
     * Maximum number of retry attempts
     */
    private int $maxRetries = 3;

    /**
     * Initial delay in milliseconds (will increase exponentially)
     */
    private int $initialDelayMs = 1000;

    /**
     * Maximum delay in milliseconds
     */
    private int $maxDelayMs = 10000;

    /**
     * Execute a callback with automatic retry on failure
     *
     * @param callable $callback The function to execute (should return API response)
     * @param array<string, mixed> $options Retry options:
     *   - max_retries: int (default: 3)
     *   - initial_delay_ms: int (default: 1000)
     *   - max_delay_ms: int (default: 10000)
     *   - on_retry: callable Called on each retry with (attempt, error)
     * @return mixed The result from the callback
     * @throws ApiException If all retry attempts fail
     */
    protected function executeWithRetry(callable $callback, array $options = []): mixed
    {
        $maxRetries = $options['max_retries'] ?? $this->maxRetries;
        $initialDelayMs = $options['initial_delay_ms'] ?? $this->initialDelayMs;
        $maxDelayMs = $options['max_delay_ms'] ?? $this->maxDelayMs;
        $onRetry = $options['on_retry'] ?? null;

        $lastException = null;
        $delayMs = $initialDelayMs;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (ApiException $e) {
                $lastException = $e;

                // Don't retry on client errors (4xx) except 429 (rate limit)
                if ($e->getHttpCode() >= 400 && $e->getHttpCode() < 500 && $e->getHttpCode() !== 429) {
                    throw $e;
                }

                // Don't retry if this was the last attempt
                if ($attempt === $maxRetries) {
                    break;
                }

                // Handle rate limit (429) with retry-after header
                if ($e->getHttpCode() === 429) {
                    $retryAfter = $this->extractRetryAfter($e);
                    if ($retryAfter > 0) {
                        $delayMs = $retryAfter * 1000;
                    }
                }

                // Call retry callback if provided
                if ($onRetry !== null && is_callable($onRetry)) {
                    $onRetry($attempt + 1, $e, $delayMs);
                }

                // Wait before retry
                usleep($delayMs * 1000);

                // Exponential backoff for next attempt (capped at max)
                $delayMs = min($delayMs * 2, $maxDelayMs);
            }
        }

        throw $lastException;
    }

    /**
     * Extract retry-after time from API exception
     *
     * @param ApiException $e The exception
     * @return int Seconds to wait before retry
     */
    private function extractRetryAfter(ApiException $e): int
    {
        $response = $e->getResponseBody();

        if (is_array($response) && isset($response['parameters']['retry_after'])) {
            return (int) $response['parameters']['retry_after'];
        }

        // Default to 1 second if retry-after not specified
        return 1;
    }

    /**
     * Execute a bulk operation with retry on individual failures
     *
     * @param callable $callback The bulk operation to execute
     * @param array<string, mixed> $options Retry options
     * @return mixed The result from the callback
     */
    protected function executeBulkWithRetry(callable $callback, array $options = []): mixed
    {
        return $this->executeWithRetry($callback, [
            'max_retries' => $options['max_retries'] ?? 1,  // Fewer retries for bulk
            'initial_delay_ms' => $options['initial_delay_ms'] ?? 500,
            'max_delay_ms' => $options['max_delay_ms'] ?? 5000,
        ]);
    }
}
