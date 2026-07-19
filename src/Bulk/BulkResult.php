<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bulk;

use Countable;

/**
 * Bulk Operation Result Value Object
 *
 * Immutable result object for bulk operations
 */
readonly class BulkResult implements Countable
{
    /**
     * @param array<int, array{success: bool, chat_id: mixed, message_id: mixed|null, data: array<string, mixed>|null, error: string|null}> $results
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $total,
        public int $successful,
        public int $failed,
        public array $results,
        public array $errors
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->failed === 0;
    }

    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }

    public function getSuccessRate(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return ($this->successful / $this->total) * 100;
    }

    public function count(): int
    {
        return $this->total;
    }

    /**
     * @return array<int, array{success: bool, chat_id: mixed, message_id: mixed|null, data: array<string, mixed>|null, error: string|null}>
     */
    public function getFailedResults(): array
    {
        return array_filter(
            $this->results,
            fn(array $result): bool => !$result['success']
        );
    }

    /**
     * @return array<int, array{success: bool, chat_id: mixed, message_id: mixed|null, data: array<string, mixed>|null, error: string|null}>
     */
    public function getSuccessfulResults(): array
    {
        return array_filter(
            $this->results,
            fn(array $result): bool => $result['success']
        );
    }

    /**
     * @param array<int, array{success: bool, chat_id: mixed, message_id: mixed|null, data: array<string, mixed>|null, error: string|null}> $rawResults
     */
    public static function fromRawResults(array $rawResults): self
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rawResults as $result) {
            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
                $errors[] = $result['error'] ?? 'Unknown error';
            }
        }

        return new self(
            total: count($rawResults),
            successful: $successful,
            failed: $failed,
            results: $rawResults,
            errors: $errors
        );
    }

    public static function empty(): self
    {
        return new self(
            total: 0,
            successful: 0,
            failed: 0,
            results: [],
            errors: []
        );
    }
}
