<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging\Context;

use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Bulk\BulkSendException;

/**
 * Builder for exception context data
 */
readonly class ExceptionContext
{
    /**
     * @param array<string, mixed>|null $additionalData
     */
    private function __construct(
        public string $exceptionType,
        public string $exceptionMessage,
        public int $exceptionCode,
        public string $file,
        public int $line,
        public string $trace,
        public ?string $previousException = null,
        public ?array $additionalData = null
    ) {}

    /**
     * Create context from any throwable
     */
    public static function fromException(\Throwable $exception): self
    {
        $previous = $exception->getPrevious();
        $previousContext = $previous !== null ? self::formatPreviousException($previous) : null;

        // Add specific data for known exception types using match expression
        $additionalData = match ($exception::class) {
            ApiException::class => [
                'error_code' => $exception->getErrorCode(),
                'http_code' => $exception->getHttpCode(),
            ],
            HttpClientException::class => [
                'http_code' => $exception->getHttpCode(),
                'response_body' => $exception->getResponseBody(),
            ],
            BulkSendException::class => self::buildBulkContext($exception),
            default => null
        };

        return new self(
            exceptionType: $exception::class,
            exceptionMessage: $exception->getMessage(),
            exceptionCode: $exception->getCode(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            trace: $exception->getTraceAsString(),
            previousException: $previousContext,
            additionalData: $additionalData
        );
    }

    /**
     * Convert to array for logging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'exception_type' => $this->exceptionType,
            'exception_message' => $this->exceptionMessage,
            'exception_code' => $this->exceptionCode,
            'file' => $this->file,
            'line' => $this->line,
            'trace' => $this->trace,
        ];

        if ($this->previousException !== null) {
            $data['previous_exception'] = $this->previousException;
        }

        if ($this->additionalData !== null) {
            return [...$data, ...$this->additionalData];
        }

        return $data;
    }

    /**
     * Build additional context data for a BulkSendException
     *
     * @return array<string, mixed>
     */
    private static function buildBulkContext(BulkSendException $exception): array
    {
        $result = $exception->getResult();

        return [
            'bulk_total' => $result->total,
            'bulk_successful' => $result->successful,
            'bulk_failed' => $result->failed,
            'success_rate' => round($result->getSuccessRate(), 2) . '%',
        ];
    }

    /**
     * Format previous exception info
     */
    private static function formatPreviousException(\Throwable $exception): string
    {
        return sprintf(
            'Caused by: %s: %s in %s:%d',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }
}
