<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Enums\HttpMethod;

/**
 * HttpMethod Enum Tests
 */
final class HttpMethodTest extends TestCase
{
    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('GET', HttpMethod::GET->value);
        $this->assertSame('POST', HttpMethod::POST->value);
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertIsString(HttpMethod::GET->value);
        $this->assertIsString(HttpMethod::POST->value);
    }

    public function test_from_method_returns_correct_enum(): void
    {
        $method = HttpMethod::from('GET');
        $this->assertSame(HttpMethod::GET, $method);

        $method = HttpMethod::from('POST');
        $this->assertSame(HttpMethod::POST, $method);
    }

    public function test_try_from_method_returns_correct_enum(): void
    {
        $method = HttpMethod::tryFrom('GET');
        $this->assertSame(HttpMethod::GET, $method);

        $method = HttpMethod::tryFrom('POST');
        $this->assertSame(HttpMethod::POST, $method);

        $invalid = HttpMethod::tryFrom('PUT');
        $this->assertNull($invalid);

        $invalid = HttpMethod::tryFrom('DELETE');
        $this->assertNull($invalid);
    }

    public function test_cases_method_returns_all_enum_cases(): void
    {
        $cases = HttpMethod::cases();
        $this->assertCount(2, $cases);
        $this->assertContains(HttpMethod::GET, $cases);
        $this->assertContains(HttpMethod::POST, $cases);
    }

    public function test_enum_case_independent(): void
    {
        $this->assertNotSame(HttpMethod::GET, HttpMethod::POST);
        $this->assertNotEquals(HttpMethod::GET->value, HttpMethod::POST->value);
    }

    public function test_get_method_is_get(): void
    {
        $this->assertTrue(HttpMethod::GET === HttpMethod::from('GET'));
    }

    public function test_post_method_is_post(): void
    {
        $this->assertTrue(HttpMethod::POST === HttpMethod::from('POST'));
    }
}
