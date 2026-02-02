<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Exception\ApiException;

/**
 * API Exception Tests
 *
 * Tests constructor with error code parameter and getErrorCode() method.
 */
final class ApiExceptionTest extends TestCase
{
    public function test_exception_can_be_created_with_message_only(): void
    {
        $exception = new ApiException('API error');

        $this->assertSame('API error', $exception->getMessage());
        $this->assertNull($exception->getErrorCode());
    }

    public function test_exception_stores_error_code(): void
    {
        $exception = new ApiException('Bad request', 400);

        $this->assertSame(400, $exception->getErrorCode());
        $this->assertSame('Bad request', $exception->getMessage());
    }

    public function test_exception_with_previous_exception(): void
    {
        $previous = new \Exception('Previous error');
        $exception = new ApiException('API error', 500, $previous);

        $this->assertSame(500, $exception->getErrorCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_error_code_properties_are_readonly(): void
    {
        $exception = new ApiException('Test', 404);

        $reflection = new \ReflectionClass($exception);
        $property = $reflection->getProperty('errorCode');

        $this->assertTrue($property->isReadOnly());
    }

    public function test_extends_telegram_exception(): void
    {
        $exception = new ApiException('Test');

        $this->assertInstanceOf(\AhmCho\Telegram\Exception\TelegramException::class, $exception);
    }

    /**
     * @dataProvider errorCodeProvider
     */
    public function test_with_various_error_codes(int $code): void
    {
        $exception = new ApiException('Error', $code);

        $this->assertSame($code, $exception->getErrorCode());
    }

    public static function errorCodeProvider(): array
    {
        return [
            'bad request' => [400],
            'unauthorized' => [401],
            'forbidden' => [403],
            'not found' => [404],
            'too many requests' => [429],
            'internal error' => [500],
        ];
    }
}
