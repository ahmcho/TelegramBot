<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Responses;

/**
 * Base API Response
 *
 * All typed API responses extend this base class
 */
abstract class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(protected readonly array $data)
    {
    }

    /**
     * Get raw data
     *
     * @return array<string, mixed>
     */
    public function rawData(): array
    {
        return $this->data;
    }

    /**
     * Get a value from the response data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if the response was successful
     */
    public function ok(): bool
    {
        return $this->get('ok', false) === true;
    }

    /**
     * Get the result data
     *
     * @return mixed
     */
    public function result(): mixed
    {
        return $this->get('result');
    }

    /**
     * Get error description if present
     */
    public function error(): ?string
    {
        return $this->get('description');
    }

    /**
     * Get error code if present
     */
    public function errorCode(): ?int
    {
        return $this->get('error_code');
    }

    /**
     * Check if this is an error response
     */
    public function isError(): bool
    {
        return !$this->ok();
    }
}
