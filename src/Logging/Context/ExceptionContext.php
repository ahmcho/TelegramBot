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
    private function __construct(
        public readonly string $exceptionType,
        public readonly string $exceptionMessage,
        public readonly int $exceptionCode,
        public readonly string $file,
        public readonly int $line,
        public readonly string $trace,
        public readonly ?string $previousException = null,
        public readonly ?array $additionalData = null
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
            BulkSendException::class => (function() use ($exception) {
                $result = $exception->getResult();
                return [
                    'bulk_total' => $result->total,
                    'bulk_successful' => $result->successful,
                    'bulk_failed' => $result->failed,
                    'success_rate' => round($result->getSuccessRate(), 2) . '%',
                ];
            })(),
            default => null
        };

        return new self(
            exceptionType: get_class($exception),
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
            $data = [...$data, ...$this->additionalData];
        }

        return $data;
    }

    /**
     * Format previous exception info
     */
    private static function formatPreviousException(\Throwable $exception): string
    {
        return sprintf(
            'Caused by: %s: %s in %s:%d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }
}
