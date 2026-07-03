<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Keyboard;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

/**
 * Reply Keyboard Builder Tests
 *
 * Tests adding rows, options integration, keyboard structure generation,
 * and with various ReplyKeyboardOptions.
 */
final class ReplyKeyboardBuilderTest extends TestCase
{
    public function test_create_returns_new_instance_with_default_options(): void
    {
        $builder = ReplyKeyboardBuilder::create();

        $this->assertInstanceOf(ReplyKeyboardBuilder::class, $builder);
    }

    public function test_create_returns_new_instance_with_custom_options(): void
    {
        $options = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            oneTimeKeyboard: true
        );

        $builder = ReplyKeyboardBuilder::create($options);

        $this->assertInstanceOf(ReplyKeyboardBuilder::class, $builder);
    }

    public function test_constructor_with_default_options(): void
    {
        $builder = new ReplyKeyboardBuilder();

        $array = $builder->toArray();

        $this->assertArrayHasKey('resize_keyboard', $array);
        $this->assertArrayHasKey('one_time_keyboard', $array);
        $this->assertArrayHasKey('selective', $array);
        $this->assertArrayHasKey('is_persistent', $array);

        // Default values from ReplyKeyboardOptions
        $this->assertFalse($array['resize_keyboard']);
        $this->assertFalse($array['one_time_keyboard']);
        $this->assertFalse($array['selective']);
        $this->assertFalse($array['is_persistent']);
    }

    public function test_constructor_with_custom_options(): void
    {
        $options = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            oneTimeKeyboard: true,
            selective: true,
            isPersistent: true
        );

        $builder = new ReplyKeyboardBuilder($options);
        $array = $builder->toArray();

        $this->assertTrue($array['resize_keyboard']);
        $this->assertTrue($array['one_time_keyboard']);
        $this->assertTrue($array['selective']);
        $this->assertTrue($array['is_persistent']);
    }

    public function test_addRow_adds_single_button_text(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(Button::text('Button 1'));

        $array = $builder->toArray();

        $this->assertCount(1, $array['keyboard']);
        $this->assertCount(1, $array['keyboard'][0]);
        $this->assertSame(['text' => 'Button 1'], $array['keyboard'][0][0]);
    }

    public function test_addRow_adds_multiple_button_texts_in_same_row(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(
            Button::text('Button 1'),
            Button::text('Button 2'),
            Button::text('Button 3')
        );

        $array = $builder->toArray();

        $this->assertCount(1, $array['keyboard']);
        $this->assertCount(3, $array['keyboard'][0]);
        $this->assertSame(
            [['text' => 'Button 1'], ['text' => 'Button 2'], ['text' => 'Button 3']],
            $array['keyboard'][0]
        );
    }

    public function test_addRow_returns_self_for_fluent_interface(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $result = $builder->addRow(Button::text('Test'));

        $this->assertSame($builder, $result);
    }

    public function test_multiple_addRow_calls_create_multiple_rows(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder
            ->addRow(Button::text('B1'), Button::text('B2'))
            ->addRow(Button::text('B3'))
            ->addRow(Button::text('B4'), Button::text('B5'));

        $array = $builder->toArray();

        $this->assertCount(3, $array['keyboard']);
        $this->assertCount(2, $array['keyboard'][0]);
        $this->assertCount(1, $array['keyboard'][1]);
        $this->assertCount(2, $array['keyboard'][2]);
    }

    public function test_toArray_returns_correct_structure(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(Button::text('Test'));

        $array = $builder->toArray();

        $this->assertArrayHasKey('keyboard', $array);
        $this->assertArrayHasKey('resize_keyboard', $array);
        $this->assertArrayHasKey('one_time_keyboard', $array);
        $this->assertIsArray($array['keyboard']);
    }

    public function test_build_returns_json_string(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(
            Button::text('Button 1'),
            Button::text('Button 2')
        );

        $json = $builder->build();

        $this->assertIsString($json);
        $this->assertNotEmpty($json);
    }

    public function test_build_generates_valid_json(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(Button::text('Test'));

        $json = $builder->build();
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('keyboard', $decoded);
    }

    public function test_empty_keyboard_toArray(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $array = $builder->toArray();

        $this->assertSame([], $array['keyboard']);
    }

    public function test_empty_keyboard_build(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $json = $builder->build();

        $decoded = json_decode($json, true);

        $this->assertArrayHasKey('keyboard', $decoded);
        $this->assertSame([], $decoded['keyboard']);
    }

    public function test_keyboard_with_resize_keyboard_option(): void
    {
        $options = new ReplyKeyboardOptions(resizeKeyboard: true);
        $builder = new ReplyKeyboardBuilder($options);
        $builder->addRow(Button::text('Button'));

        $array = $builder->toArray();

        $this->assertTrue($array['resize_keyboard']);
        $this->assertFalse($array['one_time_keyboard']);
    }

    public function test_keyboard_with_one_time_keyboard_option(): void
    {
        $options = new ReplyKeyboardOptions(oneTimeKeyboard: true);
        $builder = new ReplyKeyboardBuilder($options);
        $builder->addRow(Button::text('Button'));

        $array = $builder->toArray();

        $this->assertTrue($array['one_time_keyboard']);
        $this->assertFalse($array['resize_keyboard']);
    }

    public function test_keyboard_with_selective_option(): void
    {
        $options = new ReplyKeyboardOptions(selective: true);
        $builder = new ReplyKeyboardBuilder($options);

        $array = $builder->toArray();

        $this->assertTrue($array['selective']);
    }

    public function test_keyboard_with_persistent_option(): void
    {
        $options = new ReplyKeyboardOptions(isPersistent: true);
        $builder = new ReplyKeyboardBuilder($options);

        $array = $builder->toArray();

        $this->assertTrue($array['is_persistent']);
    }

    public function test_keyboard_with_all_options_enabled(): void
    {
        $options = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            oneTimeKeyboard: true,
            selective: true,
            isPersistent: true
        );

        $builder = new ReplyKeyboardBuilder($options);
        $builder->addRow(Button::text('Button'));

        $array = $builder->toArray();

        $this->assertTrue($array['resize_keyboard']);
        $this->assertTrue($array['one_time_keyboard']);
        $this->assertTrue($array['selective']);
        $this->assertTrue($array['is_persistent']);
    }

    public function test_reply_keyboard_uses_only_button_text_not_full_array(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(Button::callback('Test', 'data'));

        $array = $builder->toArray();

        // Reply keyboard should only use button text, not callback_data or other Button metadata
        $this->assertSame(['text' => 'Test'], $array['keyboard'][0][0]);
        $this->assertIsArray($array['keyboard'][0]);
        $this->assertArrayNotHasKey('callback_data', $array['keyboard'][0][0]);
    }

    public function test_complex_keyboard_structure(): void
    {
        $options = new ReplyKeyboardOptions(resizeKeyboard: true);
        $builder = new ReplyKeyboardBuilder($options);

        $builder
            ->addRow(Button::text('Option 1'), Button::text('Option 2'))
            ->addRow(Button::text('Option 3'))
            ->addRow(Button::text('Option 4'), Button::text('Option 5'), Button::text('Option 6'));

        $json = $builder->build();
        $decoded = json_decode($json, true);

        $this->assertCount(3, $decoded['keyboard']);
        $this->assertCount(2, $decoded['keyboard'][0]);
        $this->assertCount(1, $decoded['keyboard'][1]);
        $this->assertCount(3, $decoded['keyboard'][2]);
        $this->assertTrue($decoded['resize_keyboard']);
    }

    public function test_unicode_in_button_text(): void
    {
        $builder = new ReplyKeyboardBuilder();
        $builder->addRow(
            Button::text('🎉 Celebrate'),
            Button::text('❌ Cancel')
        );

        $json = $builder->build();
        $decoded = json_decode($json, true);

        $this->assertSame(['text' => '🎉 Celebrate'], $decoded['keyboard'][0][0]);
        $this->assertSame(['text' => '❌ Cancel'], $decoded['keyboard'][0][1]);
    }

    public function test_multiple_builders_are_independent(): void
    {
        $options1 = new ReplyKeyboardOptions(resizeKeyboard: true);
        $options2 = new ReplyKeyboardOptions(resizeKeyboard: false);

        $builder1 = new ReplyKeyboardBuilder($options1);
        $builder2 = new ReplyKeyboardBuilder($options2);

        $builder1->addRow(Button::text('Builder 1'));
        $builder2->addRow(Button::text('Builder 2'));

        $array1 = $builder1->toArray();
        $array2 = $builder2->toArray();

        $this->assertTrue($array1['resize_keyboard']);
        $this->assertFalse($array2['resize_keyboard']);
        $this->assertSame(['text' => 'Builder 1'], $array1['keyboard'][0][0]);
        $this->assertSame(['text' => 'Builder 2'], $array2['keyboard'][0][0]);
    }
}
