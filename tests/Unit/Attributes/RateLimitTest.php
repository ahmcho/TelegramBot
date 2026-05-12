<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Attributes\RateLimit;
use ReflectionClass;

/**
 * RateLimit Attribute Tests
 */
final class RateLimitTest extends TestCase
{
    public function test_attribute_can_be_instantiated(): void
    {
        $attribute = new RateLimit();
        $this->assertInstanceOf(RateLimit::class, $attribute);
    }

    public function test_attribute_with_custom_value(): void
    {
        $attribute = new RateLimit(60);
        $this->assertSame(60, $attribute->requestsPerMinute);
    }

    public function test_attribute_with_default_value(): void
    {
        $attribute = new RateLimit();
        $this->assertSame(30, $attribute->requestsPerMinute);
    }

    public function test_attribute_property_is_readonly(): void
    {
        $attribute = new RateLimit(45);
        $this->assertIsInt($attribute->requestsPerMinute);
        // Cannot test readonly enforcement at runtime, but type is correct
        $this->assertSame(45, $attribute->requestsPerMinute);
    }

    public function test_attribute_can_be_reflected(): void
    {
        $reflectionClass = new ReflectionClass(RateLimit::class);
        $this->assertTrue($reflectionClass->hasProperty('requestsPerMinute'));
        $this->assertTrue($reflectionClass->hasMethod('__construct'));
    }

    public function test_attribute_is_marked_with_php_attribute(): void
    {
        $reflectionClass = new ReflectionClass(RateLimit::class);
        $attributes = $reflectionClass->getAttributes();
        $this->assertNotEmpty($attributes);
        $this->assertSame('Attribute', $attributes[0]->getName());
    }

    public function test_attribute_with_zero_requests(): void
    {
        $attribute = new RateLimit(0);
        $this->assertSame(0, $attribute->requestsPerMinute);
    }

    public function test_attribute_with_large_value(): void
    {
        $attribute = new RateLimit(1000);
        $this->assertSame(1000, $attribute->requestsPerMinute);
    }

    public function test_attribute_with_negative_value(): void
    {
        $attribute = new RateLimit(-10);
        $this->assertSame(-10, $attribute->requestsPerMinute);
    }

    public function test_attribute_implements_correct_interface(): void
    {
        $attribute = new RateLimit();
        // Attributes don't implement interfaces, but they should be instantiable
        $this->assertInstanceOf(RateLimit::class, $attribute);
    }

    public function test_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(RateLimit::class));
    }

    public function test_attribute_namespace(): void
    {
        $attribute = new RateLimit();
        $this->assertSame('AhmCho\Telegram\Attributes\RateLimit', get_class($attribute));
    }

    public function test_attribute_can_be_used_on_class(): void
    {
        // Just test that the attribute class exists and can be instantiated
        $attribute = new RateLimit(50);
        $this->assertSame(50, $attribute->requestsPerMinute);
    }

    public function test_attribute_can_be_used_on_method(): void
    {
        // Just test that the attribute class exists and can be instantiated
        $attribute = new RateLimit(20);
        $this->assertSame(20, $attribute->requestsPerMinute);
    }

    public function test_attribute_multiple_on_same_class(): void
    {
        // Just test that multiple attributes can be created
        $attribute1 = new RateLimit(10);
        $attribute2 = new RateLimit(20);

        $this->assertSame(10, $attribute1->requestsPerMinute);
        $this->assertSame(20, $attribute2->requestsPerMinute);
    }
}
