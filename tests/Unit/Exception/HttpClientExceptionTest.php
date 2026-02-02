<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * HTTP Client Exception Tests
 *
 * Tests constructor with all parameters, getHttpCode(), and getResponseBody().
 */
final class HttpClientExceptionTest extends TestCase
{
    public function test_exception_can_be_created_with_message_only(): void
    {
        $exception = new HttpClientException('Test error');

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertNull($exception->getHttpCode());
        $this->assertNull($exception->getResponseBody());
    }

    public function test_exception_stores_http_code(): void
    {
        $exception = new HttpClientException('HTTP error', 404);

        $this->assertSame(404, $exception->getHttpCode());
        $this->assertSame('HTTP error', $exception->getMessage());
    }

    public function test_exception_stores_response_body(): void
    {
        $responseBody = '{"ok":false,"error_code":404,"description":"Not Found"}';
        $exception = new HttpClientException('API error', 404, $responseBody);

        $this->assertSame($responseBody, $exception->getResponseBody());
        $this->assertSame(404, $exception->getHttpCode());
    }

    public function test_exception_with_all_parameters(): void
    {
        $previous = new \Exception('Previous error');
        $responseBody = '{"error": "details"}';

        $exception = new HttpClientException(
            'HTTP request failed',
            500,
            $responseBody,
            $previous
        );

        $this->assertSame('HTTP request failed', $exception->getMessage());
        $this->assertSame(500, $exception->getHttpCode());
        $this->assertSame($responseBody, $exception->getResponseBody());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_exception_with_zero_http_code(): void
    {
        $exception = new HttpClientException('Connection timeout', 0);

        $this->assertSame(0, $exception->getHttpCode());
    }

    public function test_exception_with_null_http_code(): void
    {
        $exception = new HttpClientException('Generic error');

        $this->assertNull($exception->getHttpCode());
    }

    public function test_exception_with_empty_response_body(): void
    {
        $exception = new HttpClientException('Error', 400, '');

        $this->assertSame('', $exception->getResponseBody());
    }

    public function test_exception_with_null_response_body(): void
    {
        $exception = new HttpClientException('Error', 400, null);

        $this->assertNull($exception->getResponseBody());
    }

    public function test_exception_is_throwable(): void
    {
        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Test exception');

        throw new HttpClientException('Test exception');
    }

    public function test_exception_properties_are_readonly(): void
    {
        $exception = new HttpClientException('Test', 404, 'body');

        $reflection = new \ReflectionClass($exception);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            if ($property->getName() === 'httpCode' || $property->getName() === 'responseBody') {
                $this->assertTrue(
                    $property->isReadOnly(),
                    "Property {$property->getName()} should be readonly"
                );
            }
        }
    }

    public function test_exception_code_defaults_to_zero(): void
    {
        $exception = new HttpClientException('Test');

        $this->assertSame(0, $exception->getCode());
    }

    public function test_exception_extends_telegram_exception(): void
    {
        $exception = new HttpClientException('Test');

        $this->assertInstanceOf(\AhmCho\Telegram\Exception\TelegramException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    /**
     * @dataProvider httpCodeProvider
     */
    public function test_exception_with_various_http_codes(int $code): void
    {
        $exception = new HttpClientException('Error', $code);

        $this->assertSame($code, $exception->getHttpCode());
    }

    public static function httpCodeProvider(): array
    {
        return [
            'continue' => [100],
            'ok' => [200],
            'redirect' => [302],
            'bad request' => [400],
            'unauthorized' => [401],
            'forbidden' => [403],
            'not found' => [404],
            'too many requests' => [429],
            'internal error' => [500],
            'bad gateway' => [502],
            'service unavailable' => [503],
        ];
    }

    public function test_exception_with_complex_json_response_body(): void
    {
        $jsonBody = json_encode([
            'ok' => false,
            'error_code' => 400,
            'description' => 'Bad Request: chat not found',
            'parameters' => [
                'migrate_to_chat_id' => -100123456789
            ]
        ]);

        $exception = new HttpClientException('API Error', 400, $jsonBody);

        $this->assertSame($jsonBody, $exception->getResponseBody());
        $this->assertIsString($exception->getResponseBody());
    }
}
