<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Helpers;

/**
 * Stream Wrapper for Mocking php://input
 *
 * Allows testing webhook functionality by providing test data
 * instead of reading from actual php://input stream.
 */
final class WebhookStreamWrapper
{
    private static ?string $data = null;
    private static int $position = 0;
    public $context = null;

    /**
     * Set the data to be returned when reading from the stream
     */
    public static function setData(string $jsonData): void
    {
        self::$data = $jsonData;
        self::$position = 0;
    }

    /**
     * Clear any stored data
     */
    public static function clear(): void
    {
        self::$data = null;
        self::$position = 0;
    }

    /**
     * Register the stream wrapper
     */
    public static function register(): void
    {
        if (in_array('webhook-test', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('webhook-test');
        }
        stream_wrapper_register('webhook-test', __CLASS__);
    }

    /**
     * Unregister the stream wrapper
     */
    public static function unregister(): void
    {
        if (in_array('webhook-test', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('webhook-test');
        }
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
    {
        $opened_path = $path;
        return true;
    }

    public function stream_read(int $count): string|false
    {
        if (self::$data === null) {
            return false;
        }

        $remaining = strlen(self::$data) - self::$position;
        $bytesToRead = min($count, $remaining);

        if ($bytesToRead <= 0) {
            return false;
        }

        $result = substr(self::$data, self::$position, $bytesToRead);
        self::$position += $bytesToRead;

        return $result;
    }

    public function stream_eof(): bool
    {
        if (self::$data === null) {
            return true;
        }

        return self::$position >= strlen(self::$data);
    }

    public function stream_stat(): array|false
    {
        return [
            'size' => self::$data !== null ? strlen(self::$data) : 0,
            'mode' => 0100000, // Regular file
        ];
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return [
            'size' => self::$data !== null ? strlen(self::$data) : 0,
            'mode' => 0100000, // Regular file
        ];
    }
}
