<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * Telegram Exception Tests
 *
 * Tests base exception functionality.
 */
final class TelegramExceptionTest extends TestCase
{
    public function test_exception_can_be_thrown(): void
    {
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Test exception');

        throw new HttpClientException('Test exception');
    }

    public function test_exception_can_be_caught(): void
    {
        try {
            throw new HttpClientException('Test error');
        } catch (TelegramException $e) {
            $this->assertSame('Test error', $e->getMessage());
            return;
        }

        $this->fail('Exception was not caught');
    }

    public function test_exception_with_previous(): void
    {
        $previous = new \Exception('Previous');
        $exception = new HttpClientException('Wrapper', 0, 'response body', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_extends_php_exception(): void
    {
        $exception = new HttpClientException('Test');

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function test_extends_telegram_exception(): void
    {
        $exception = new HttpClientException('Test');

        $this->assertInstanceOf(TelegramException::class, $exception);
    }
}
