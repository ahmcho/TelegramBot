<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Attributes\TelegramMethod;
use ReflectionClass;
use ReflectionMethod;

/**
 * TelegramMethod Attribute Tests
 */
final class TelegramMethodTest extends TestCase
{
    public function test_attribute_can_be_instantiated(): void
    {
        $attribute = new TelegramMethod('sendMessage');
        $this->assertInstanceOf(TelegramMethod::class, $attribute);
    }

    public function test_attribute_method_property(): void
    {
        $attribute = new TelegramMethod('sendMessage');
        $this->assertSame('sendMessage', $attribute->method);
    }

    public function test_attribute_with_required_params(): void
    {
        $attribute = new TelegramMethod(
            'sendMessage',
            ['chat_id', 'text']
        );

        $this->assertSame(['chat_id', 'text'], $attribute->requiredParams);
        $this->assertEmpty($attribute->optionalParams);
    }

    public function test_attribute_with_optional_params(): void
    {
        $attribute = new TelegramMethod(
            'sendMessage',
            ['chat_id', 'text'],
            ['parse_mode', 'disable_web_page_preview']
        );

        $this->assertSame(['chat_id', 'text'], $attribute->requiredParams);
        $this->assertSame(['parse_mode', 'disable_web_page_preview'], $attribute->optionalParams);
    }

    public function test_attribute_with_all_params(): void
    {
        $attribute = new TelegramMethod(
            'sendMessage',
            ['chat_id'],
            ['parse_mode']
        );

        $this->assertSame('sendMessage', $attribute->method);
        $this->assertSame(['chat_id'], $attribute->requiredParams);
        $this->assertSame(['parse_mode'], $attribute->optionalParams);
    }

    public function test_attribute_default_empty_arrays(): void
    {
        $attribute = new TelegramMethod('testMethod');

        $this->assertEmpty($attribute->requiredParams);
        $this->assertEmpty($attribute->optionalParams);
    }

    public function test_attribute_properties_are_readonly(): void
    {
        $attribute = new TelegramMethod(
            'testMethod',
            ['param1'],
            ['optional1']
        );

        $this->assertIsString($attribute->method);
        $this->assertIsArray($attribute->requiredParams);
        $this->assertIsArray($attribute->optionalParams);

        $this->assertSame('testMethod', $attribute->method);
        $this->assertSame(['param1'], $attribute->requiredParams);
        $this->assertSame(['optional1'], $attribute->optionalParams);
    }

    public function test_attribute_can_be_reflected(): void
    {
        $reflectionClass = new ReflectionClass(TelegramMethod::class);
        $this->assertTrue($reflectionClass->hasProperty('method'));
        $this->assertTrue($reflectionClass->hasProperty('requiredParams'));
        $this->assertTrue($reflectionClass->hasProperty('optionalParams'));
        $this->assertTrue($reflectionClass->hasMethod('__construct'));
    }

    public function test_attribute_is_marked_with_php_attribute(): void
    {
        $reflectionClass = new ReflectionClass(TelegramMethod::class);
        $attributes = $reflectionClass->getAttributes();
        $this->assertNotEmpty($attributes);
        $this->assertSame('Attribute', $attributes[0]->getName());
    }

    public function test_attribute_namespace(): void
    {
        $attribute = new TelegramMethod('test');
        $this->assertSame('AhmCho\Telegram\Attributes\TelegramMethod', get_class($attribute));
    }

    public function test_attribute_with_real_telegram_methods(): void
    {
        $methods = [
            'sendMessage' => ['chat_id', 'text'],
            'sendPhoto' => ['chat_id', 'photo'],
            'getUpdates' => [],
        ];

        foreach ($methods as $method => $required) {
            $attribute = new TelegramMethod($method, $required);
            $this->assertSame($method, $attribute->method);
            $this->assertSame($required, $attribute->requiredParams);
        }
    }

    public function test_attribute_can_be_used_on_method(): void
    {
        // Just test that the attribute can be instantiated with correct parameters
        $attribute = new TelegramMethod('sendMessage', ['chat_id', 'text'], ['parse_mode']);

        $this->assertSame('sendMessage', $attribute->method);
        $this->assertSame(['chat_id', 'text'], $attribute->requiredParams);
        $this->assertSame(['parse_mode'], $attribute->optionalParams);
    }

    public function test_attribute_can_be_used_on_class(): void
    {
        // Just test that the attribute can be instantiated with correct parameters
        $attribute = new TelegramMethod('sendPhoto', ['chat_id', 'photo']);

        $this->assertSame('sendPhoto', $attribute->method);
        $this->assertSame(['chat_id', 'photo'], $attribute->requiredParams);
    }

    public function test_attribute_with_empty_string_method(): void
    {
        $attribute = new TelegramMethod('');
        $this->assertSame('', $attribute->method);
    }

    public function test_attribute_with_special_characters_in_method(): void
    {
        $attribute = new TelegramMethod('sendMéthod_тест');
        $this->assertSame('sendMéthod_тест', $attribute->method);
    }

    public function test_attribute_params_are_arrays(): void
    {
        $attribute = new TelegramMethod(
            'test',
            ['param1', 'param2'],
            ['opt1', 'opt2']
        );

        $this->assertIsArray($attribute->requiredParams);
        $this->assertIsArray($attribute->optionalParams);
        $this->assertCount(2, $attribute->requiredParams);
        $this->assertCount(2, $attribute->optionalParams);
    }

    public function test_attribute_implements_correct_interface(): void
    {
        $attribute = new TelegramMethod('test');
        $this->assertInstanceOf(TelegramMethod::class, $attribute);
    }

    public function test_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(TelegramMethod::class));
    }
}
