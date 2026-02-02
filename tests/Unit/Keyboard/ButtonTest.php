<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Keyboard;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Keyboard\Button;

/**
 * Button Value Object Tests
 *
 * Tests URL, callback, switch inline, text factory methods,
 * toArray() structure, and readonly immutability.
 */
final class ButtonTest extends TestCase
{
    public function test_url_factory_creates_button_with_url(): void
    {
        $button = Button::url('Google', 'https://google.com');

        $this->assertSame('Google', $button->text);
        $this->assertSame('https://google.com', $button->url);
        $this->assertNull($button->callbackData);
        $this->assertNull($button->switchInlineQuery);
        $this->assertNull($button->switchInlineQueryCurrentChat);
        $this->assertSame([], $button->metadata);
    }

    public function test_callback_factory_creates_button_with_callback_data(): void
    {
        $button = Button::callback('Click Me', 'action:button_1');

        $this->assertSame('Click Me', $button->text);
        $this->assertSame('action:button_1', $button->callbackData);
        $this->assertNull($button->url);
        $this->assertNull($button->switchInlineQuery);
        $this->assertNull($button->switchInlineQueryCurrentChat);
        $this->assertSame([], $button->metadata);
    }

    public function test_switchInline_factory_creates_button_with_switch_query(): void
    {
        $button = Button::switchInline('Search', 'query text');

        $this->assertSame('Search', $button->text);
        $this->assertSame('query text', $button->switchInlineQuery);
        $this->assertNull($button->url);
        $this->assertNull($button->callbackData);
        $this->assertNull($button->switchInlineQueryCurrentChat);
        $this->assertSame([], $button->metadata);
    }

    public function test_switchInline_with_empty_query(): void
    {
        $button = Button::switchInline('Search');

        $this->assertSame('Search', $button->text);
        $this->assertSame('', $button->switchInlineQuery);
    }

    public function test_switchInlineCurrent_factory_creates_button_with_switch_current_query(): void
    {
        $button = Button::switchInlineCurrent('Search Here', 'query text');

        $this->assertSame('Search Here', $button->text);
        $this->assertSame('query text', $button->switchInlineQueryCurrentChat);
        $this->assertNull($button->url);
        $this->assertNull($button->callbackData);
        $this->assertNull($button->switchInlineQuery);
        $this->assertSame([], $button->metadata);
    }

    public function test_switchInlineCurrent_with_empty_query(): void
    {
        $button = Button::switchInlineCurrent('Search Here');

        $this->assertSame('Search Here', $button->text);
        $this->assertSame('', $button->switchInlineQueryCurrentChat);
    }

    public function test_text_factory_creates_simple_text_button(): void
    {
        $button = Button::text('Option 1');

        $this->assertSame('Option 1', $button->text);
        $this->assertNull($button->url);
        $this->assertNull($button->callbackData);
        $this->assertNull($button->switchInlineQuery);
        $this->assertNull($button->switchInlineQueryCurrentChat);
        $this->assertSame([], $button->metadata);
    }

    public function test_toArray_for_url_button(): void
    {
        $button = Button::url('Example', 'https://example.com');
        $array = $button->toArray();

        $this->assertSame(['text' => 'Example', 'url' => 'https://example.com'], $array);
    }

    public function test_toArray_for_callback_button(): void
    {
        $button = Button::callback('Click', 'data:123');
        $array = $button->toArray();

        $this->assertSame(['text' => 'Click', 'callback_data' => 'data:123'], $array);
    }

    public function test_toArray_for_switch_inline_button(): void
    {
        $button = Button::switchInline('Search', 'test query');
        $array = $button->toArray();

        $this->assertSame(['text' => 'Search', 'switch_inline_query' => 'test query'], $array);
    }

    public function test_toArray_for_switch_inline_current_button(): void
    {
        $button = Button::switchInlineCurrent('Search Here', 'test');
        $array = $button->toArray();

        $this->assertSame(['text' => 'Search Here', 'switch_inline_query_current_chat' => 'test'], $array);
    }

    public function test_toArray_for_text_button(): void
    {
        $button = Button::text('Button Text');
        $array = $button->toArray();

        $this->assertSame(['text' => 'Button Text'], $array);
    }

    public function test_button_is_readonly(): void
    {
        $button = Button::url('Test', 'https://test.com');

        $reflection = new \ReflectionClass($button);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function test_button_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(Button::class);

        $this->assertTrue(
            $reflection->isReadOnly(),
            'Button class should be readonly'
        );
    }

    public function test_url_button_with_special_characters_in_text(): void
    {
        $button = Button::url('Test & Demo', 'https://example.com');

        $this->assertSame('Test & Demo', $button->text);
    }

    public function test_callback_button_with_special_characters_in_data(): void
    {
        $button = Button::callback('Click', 'data:{"key":"value"}');

        $this->assertSame('data:{"key":"value"}', $button->callbackData);
    }

    public function test_button_with_empty_text(): void
    {
        $button = Button::text('');

        $this->assertSame('', $button->text);
    }

    public function test_button_with_unicode_text(): void
    {
        $button = Button::text('🎉 Button 🎉');

        $this->assertSame('🎉 Button 🎉', $button->text);
    }

    public function test_url_button_with_valid_url(): void
    {
        $urls = [
            'https://example.com',
            'http://example.com',
            'https://example.com/path?query=value',
            'https://example.com:8080/path',
            'https://user:pass@example.com',
        ];

        foreach ($urls as $url) {
            $button = Button::url('Link', $url);
            $this->assertSame($url, $button->url);
        }
    }

    public function test_callback_data_can_be_json(): void
    {
        $jsonData = json_encode(['action' => 'delete', 'id' => 123]);
        $button = Button::callback('Delete', $jsonData);

        $this->assertSame($jsonData, $button->callbackData);
    }

    public function test_toArray_generates_valid_json(): void
    {
        $button = Button::callback('Test', 'data');
        $array = $button->toArray();
        $json = json_encode($array);

        $this->assertIsString($json);
        $this->assertNotFalse(json_decode($json));
    }

    public function test_multiple_buttons_are_independent(): void
    {
        $button1 = Button::text('Button 1');
        $button2 = Button::text('Button 2');

        $this->assertNotSame($button1, $button2);
        $this->assertSame('Button 1', $button1->text);
        $this->assertSame('Button 2', $button2->text);
    }

    public function test_button_properties_are_public_and_readonly(): void
    {
        $button = Button::url('Test', 'https://test.com');

        // Should be able to read properties
        $this->assertSame('Test', $button->text);
        $this->assertSame('https://test.com', $button->url);

        // Properties should be readonly (cannot be reassigned)
        // This is a compile-time check in PHP 8.2+
    }
}
