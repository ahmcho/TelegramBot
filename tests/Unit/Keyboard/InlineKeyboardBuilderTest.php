<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Keyboard;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

/**
 * Inline Keyboard Builder Tests
 *
 * Tests adding rows with multiple buttons, build(), toArray(),
 * fluent interface, and empty keyboard.
 */
final class InlineKeyboardBuilderTest extends TestCase
{
    private InlineKeyboardBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new InlineKeyboardBuilder();
    }

    public function test_create_returns_new_instance(): void
    {
        $builder = InlineKeyboardBuilder::create();

        $this->assertInstanceOf(InlineKeyboardBuilder::class, $builder);
        $this->assertNotSame($this->builder, $builder);
    }

    public function test_addRow_adds_single_button(): void
    {
        $button = Button::callback('Button 1', 'data1');
        $result = $this->builder->addRow($button);

        $array = $this->builder->toArray();

        $this->assertCount(1, $array['inline_keyboard']);
        $this->assertCount(1, $array['inline_keyboard'][0]);
        $this->assertSame(['text' => 'Button 1', 'callback_data' => 'data1'], $array['inline_keyboard'][0][0]);
    }

    public function test_addRow_adds_multiple_buttons_in_same_row(): void
    {
        $button1 = Button::callback('Button 1', 'data1');
        $button2 = Button::callback('Button 2', 'data2');
        $button3 = Button::url('Button 3', 'https://example.com');

        $this->builder->addRow($button1, $button2, $button3);

        $array = $this->builder->toArray();

        $this->assertCount(1, $array['inline_keyboard']);
        $this->assertCount(3, $array['inline_keyboard'][0]);
        $this->assertSame('Button 1', $array['inline_keyboard'][0][0]['text']);
        $this->assertSame('Button 2', $array['inline_keyboard'][0][1]['text']);
        $this->assertSame('Button 3', $array['inline_keyboard'][0][2]['text']);
    }

    public function test_addRow_returns_self_for_fluent_interface(): void
    {
        $button = Button::text('Button');

        $result = $this->builder->addRow($button);

        $this->assertSame($this->builder, $result);
    }

    public function test_multiple_addRow_calls_create_multiple_rows(): void
    {
        $this->builder
            ->addRow(Button::callback('B1', 'd1'), Button::callback('B2', 'd2'))
            ->addRow(Button::callback('B3', 'd3'))
            ->addRow(Button::url('B4', 'https://test.com'), Button::url('B5', 'https://test2.com'));

        $array = $this->builder->toArray();

        $this->assertCount(3, $array['inline_keyboard']);
        $this->assertCount(2, $array['inline_keyboard'][0]);
        $this->assertCount(1, $array['inline_keyboard'][1]);
        $this->assertCount(2, $array['inline_keyboard'][2]);
    }

    public function test_toArray_returns_correct_structure(): void
    {
        $this->builder->addRow(Button::callback('Test', 'data'));

        $array = $this->builder->toArray();

        $this->assertArrayHasKey('inline_keyboard', $array);
        $this->assertIsArray($array['inline_keyboard']);
    }

    public function test_build_returns_json_string(): void
    {
        $this->builder->addRow(
            Button::callback('Button 1', 'data1'),
            Button::callback('Button 2', 'data2')
        );

        $json = $this->builder->build();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);
    }

    public function test_build_generates_valid_json(): void
    {
        $this->builder->addRow(Button::callback('Test', 'data'));

        $json = $this->builder->build();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('inline_keyboard', $decoded);
    }

    public function test_empty_keyboard_toArray(): void
    {
        $array = $this->builder->toArray();

        $this->assertSame(['inline_keyboard' => []], $array);
    }

    public function test_empty_keyboard_build(): void
    {
        $json = $this->builder->build();

        $this->assertSame('{"inline_keyboard":[]}', $json);
    }

    public function test_complex_keyboard_structure(): void
    {
        $this->builder
            ->addRow(
                Button::url('Google', 'https://google.com'),
                Button::url('Bing', 'https://bing.com')
            )
            ->addRow(
                Button::callback('Like', 'action:like'),
                Button::callback('Dislike', 'action:dislike'),
                Button::callback('Share', 'action:share')
            )
            ->addRow(
                Button::switchInline('Search', ''),
                Button::switchInlineCurrent('Search Here', 'query')
            );

        $json = $this->builder->build();
        $decoded = json_decode($json, true);

        $this->assertCount(3, $decoded['inline_keyboard']);
        $this->assertCount(2, $decoded['inline_keyboard'][0]);
        $this->assertCount(3, $decoded['inline_keyboard'][1]);
        $this->assertCount(2, $decoded['inline_keyboard'][2]);
    }

    public function test_all_button_types_in_single_keyboard(): void
    {
        $this->builder
            ->addRow(
                Button::url('URL', 'https://example.com'),
                Button::callback('Callback', 'data'),
                Button::switchInline('Switch', 'query'),
                Button::switchInlineCurrent('Switch Current', 'query2')
            );

        $array = $this->builder->toArray();

        $this->assertCount(4, $array['inline_keyboard'][0]);
        $this->assertArrayHasKey('url', $array['inline_keyboard'][0][0]);
        $this->assertArrayHasKey('callback_data', $array['inline_keyboard'][0][1]);
        $this->assertArrayHasKey('switch_inline_query', $array['inline_keyboard'][0][2]);
        $this->assertArrayHasKey('switch_inline_query_current_chat', $array['inline_keyboard'][0][3]);
    }

    public function test_chaining_multiple_addRow_calls(): void
    {
        $result = $this->builder
            ->addRow(Button::text('1'))
            ->addRow(Button::text('2'))
            ->addRow(Button::text('3'))
            ->toArray();

        $this->assertCount(3, $result['inline_keyboard']);
    }

    public function test_unicode_in_button_text(): void
    {
        $this->builder->addRow(
            Button::callback('🎉 Celebrate', 'celebrate'),
            Button::callback('❌ Cancel', 'cancel')
        );

        $json = $this->builder->build();
        $decoded = json_decode($json, true);

        $this->assertSame('🎉 Celebrate', $decoded['inline_keyboard'][0][0]['text']);
        $this->assertSame('❌ Cancel', $decoded['inline_keyboard'][0][1]['text']);
    }

    public function test_special_characters_in_callback_data(): void
    {
        $this->builder->addRow(
            Button::callback('Test', 'data:special-chars_123')
        );

        $array = $this->builder->toArray();

        $this->assertSame('data:special-chars_123', $array['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_multiple_builders_are_independent(): void
    {
        $builder1 = new InlineKeyboardBuilder();
        $builder2 = new InlineKeyboardBuilder();

        $builder1->addRow(Button::text('Builder 1'));
        $builder2->addRow(Button::text('Builder 2'));

        $array1 = $builder1->toArray();
        $array2 = $builder2->toArray();

        $this->assertSame('Builder 1', $array1['inline_keyboard'][0][0]['text']);
        $this->assertSame('Builder 2', $array2['inline_keyboard'][0][0]['text']);
    }

    public function test_builder_can_be_reused(): void
    {
        $this->builder->addRow(Button::text('First'));

        $firstBuild = $this->builder->build();

        $this->builder->addRow(Button::text('Second'));

        $secondBuild = $this->builder->build();

        $firstDecoded = json_decode($firstBuild, true);
        $secondDecoded = json_decode($secondBuild, true);

        $this->assertCount(1, $firstDecoded['inline_keyboard']);
        $this->assertCount(2, $secondDecoded['inline_keyboard']);
    }
}
