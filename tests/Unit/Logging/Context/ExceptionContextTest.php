<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Logging\Context;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Logging\Context\ExceptionContext;
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * ExceptionContext Tests
 */
final class ExceptionContextTest extends TestCase
{
    public function test_from_exception_creates_context(): void
    {
        $exception = new \RuntimeException('Test exception', 123);
        $context = ExceptionContext::fromException($exception);

        $this->assertInstanceOf(ExceptionContext::class, $context);
        $this->assertSame('RuntimeException', $context->exceptionType);
        $this->assertSame('Test exception', $context->exceptionMessage);
        $this->assertSame(123, $context->exceptionCode);
    }

    public function test_from_exception_includes_file_and_line(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertIsString($context->file);
        $this->assertIsInt($context->line);
        $this->assertNotEmpty($context->file);
        $this->assertGreaterThan(0, $context->line);
    }

    public function test_from_exception_includes_trace(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertIsString($context->trace);
        $this->assertNotEmpty($context->trace);
    }

    public function test_from_exception_with_previous_exception(): void
    {
        $previous = new \LogicException('Previous exception');
        $exception = new \RuntimeException('Main exception', 0, $previous);

        $context = ExceptionContext::fromException($exception);

        $this->assertNotNull($context->previousException);
        $this->assertStringContainsString('LogicException', $context->previousException);
        $this->assertStringContainsString('Previous exception', $context->previousException);
    }

    public function test_from_exception_without_previous_exception(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertNull($context->previousException);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $exception = new \RuntimeException('Test exception', 456);
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('exception_type', $array);
        $this->assertArrayHasKey('exception_message', $array);
        $this->assertArrayHasKey('exception_code', $array);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
        $this->assertArrayHasKey('trace', $array);
    }

    public function test_to_array_values_are_correct(): void
    {
        $exception = new \RuntimeException('Test message', 789);
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertSame('RuntimeException', $array['exception_type']);
        $this->assertSame('Test message', $array['exception_message']);
        $this->assertSame(789, $array['exception_code']);
    }

    public function test_to_array_includes_previous_exception(): void
    {
        $previous = new \LogicException('Previous');
        $exception = new \RuntimeException('Main', 0, $previous);

        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertArrayHasKey('previous_exception', $array);
        $this->assertStringContainsString('LogicException', $array['previous_exception']);
    }

    public function test_from_api_exception_includes_additional_data(): void
    {
        $exception = new ApiException('API Error', 400);
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        // ApiException includes errorCode as 'error_code'
        $this->assertArrayHasKey('error_code', $array);
        $this->assertSame(400, $array['error_code']);
    }

    public function test_from_http_client_exception_includes_additional_data(): void
    {
        $exception = new HttpClientException('HTTP Error', 500, 'Response body');
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertArrayHasKey('http_code', $array);
        $this->assertArrayHasKey('response_body', $array);
        $this->assertSame(500, $array['http_code']);
        $this->assertSame('Response body', $array['response_body']);
    }

    public function test_from_exception_with_custom_exception(): void
    {
        $exception = new class extends \Exception {
            public function __construct() {
                parent::__construct('Custom exception', 999);
            }
        };

        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertStringContainsString('Exception', $array['exception_type']);
        $this->assertSame('Custom exception', $array['exception_message']);
        $this->assertSame(999, $array['exception_code']);
    }

    public function test_properties_are_readonly(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertIsString($context->exceptionType);
        $this->assertIsString($context->exceptionMessage);
        $this->assertIsInt($context->exceptionCode);
        $this->assertIsString($context->file);
        $this->assertIsInt($context->line);
        $this->assertIsString($context->trace);
    }

    public function test_context_is_readonly(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $reflection = new \ReflectionClass(ExceptionContext::class);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_from_exception_in_different_file(): void
    {
        $exception = new \RuntimeException('Test in different file');
        $context = ExceptionContext::fromException($exception);

        $this->assertStringContainsString('ExceptionContextTest.php', $context->file);
    }

    public function test_to_array_does_not_include_previous_when_null(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertArrayNotHasKey('previous_exception', $array);
    }

    public function test_to_array_does_not_include_additional_data_when_null(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);
        $array = $context->toArray();

        $this->assertArrayNotHasKey('http_code', $array);
        $this->assertArrayNotHasKey('api_response', $array);
    }

    public function test_trace_format_is_correct(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertStringContainsString('#', $context->trace);
        $this->assertStringContainsString('ExceptionContextTest', $context->trace);
    }

    public function test_from_exception_with_long_message(): void
    {
        $longMessage = str_repeat('A', 1000);
        $exception = new \RuntimeException($longMessage);
        $context = ExceptionContext::fromException($exception);

        $this->assertSame($longMessage, $context->exceptionMessage);
    }

    public function test_from_exception_with_special_characters(): void
    {
        $message = 'Test with 特殊 characters and emoji 🎉';
        $exception = new \RuntimeException($message);
        $context = ExceptionContext::fromException($exception);

        $this->assertSame($message, $context->exceptionMessage);
    }

    public function test_from_exception_in_nested_call_stack(): void
    {
        $exception = $this->createNestedException();
        $context = ExceptionContext::fromException($exception);

        $this->assertNotEmpty($context->trace);
        $this->assertStringContainsString('createNestedException', $context->trace);
    }

    private function createNestedException(): \RuntimeException
    {
        try {
            throw new \RuntimeException('Nested exception');
        } catch (\RuntimeException $e) {
            return $e;
        }
    }

    public function test_context_class_exists(): void
    {
        $this->assertTrue(class_exists(ExceptionContext::class));
    }

    public function test_context_namespace(): void
    {
        $exception = new \RuntimeException('Test');
        $context = ExceptionContext::fromException($exception);

        $this->assertSame('AhmCho\Telegram\Logging\Context\ExceptionContext', get_class($context));
    }
}
