<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Keyboard;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

/**
 * Reply Keyboard Options Tests
 *
 * Tests default values, all option parameters, and readonly properties.
 */
final class ReplyKeyboardOptionsTest extends TestCase
{
    public function test_default_values(): void
    {
        $options = new ReplyKeyboardOptions();

        $this->assertFalse($options->resizeKeyboard);
        $this->assertFalse($options->oneTimeKeyboard);
        $this->assertFalse($options->selective);
        $this->assertFalse($options->isPersistent);
    }

    public function test_resize_keyboard_can_be_set_to_true(): void
    {
        $options = new ReplyKeyboardOptions(resizeKeyboard: true);

        $this->assertTrue($options->resizeKeyboard);
        $this->assertFalse($options->oneTimeKeyboard);
        $this->assertFalse($options->selective);
        $this->assertFalse($options->isPersistent);
    }

    public function test_one_time_keyboard_can_be_set_to_true(): void
    {
        $options = new ReplyKeyboardOptions(oneTimeKeyboard: true);

        $this->assertFalse($options->resizeKeyboard);
        $this->assertTrue($options->oneTimeKeyboard);
        $this->assertFalse($options->selective);
        $this->assertFalse($options->isPersistent);
    }

    public function test_selective_can_be_set_to_true(): void
    {
        $options = new ReplyKeyboardOptions(selective: true);

        $this->assertFalse($options->resizeKeyboard);
        $this->assertFalse($options->oneTimeKeyboard);
        $this->assertTrue($options->selective);
        $this->assertFalse($options->isPersistent);
    }

    public function test_persistent_can_be_set_to_true(): void
    {
        $options = new ReplyKeyboardOptions(isPersistent: true);

        $this->assertFalse($options->resizeKeyboard);
        $this->assertFalse($options->oneTimeKeyboard);
        $this->assertFalse($options->selective);
        $this->assertTrue($options->isPersistent);
    }

    public function test_all_options_can_be_set_to_true(): void
    {
        $options = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            oneTimeKeyboard: true,
            selective: true,
            isPersistent: true
        );

        $this->assertTrue($options->resizeKeyboard);
        $this->assertTrue($options->oneTimeKeyboard);
        $this->assertTrue($options->selective);
        $this->assertTrue($options->isPersistent);
    }

    public function test_named_parameters_work_correctly(): void
    {
        $options = new ReplyKeyboardOptions(
            oneTimeKeyboard: true,
            resizeKeyboard: true
        );

        $this->assertTrue($options->oneTimeKeyboard);
        $this->assertTrue($options->resizeKeyboard);
    }

    public function test_properties_are_public(): void
    {
        $options = new ReplyKeyboardOptions();

        $reflection = new \ReflectionClass($options);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isPublic(), "Property {$property->getName()} should be public");
        }
    }

    public function test_properties_are_readonly(): void
    {
        $options = new ReplyKeyboardOptions();

        $reflection = new \ReflectionClass($options);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(ReplyKeyboardOptions::class);

        $this->assertTrue(
            $reflection->isReadOnly(),
            'ReplyKeyboardOptions class should be readonly'
        );
    }

    /**
     * @dataProvider optionCombinationProvider
     */
    public function test_various_option_combinations(
        bool $resize,
        bool $oneTime,
        bool $selective,
        bool $persistent
    ): void {
        $options = new ReplyKeyboardOptions(
            resizeKeyboard: $resize,
            oneTimeKeyboard: $oneTime,
            selective: $selective,
            isPersistent: $persistent
        );

        $this->assertSame($resize, $options->resizeKeyboard);
        $this->assertSame($oneTime, $options->oneTimeKeyboard);
        $this->assertSame($selective, $options->selective);
        $this->assertSame($persistent, $options->isPersistent);
    }

    public static function optionCombinationProvider(): array
    {
        return [
            'all false' => [false, false, false, false],
            'all true' => [true, true, true, true],
            'resize only' => [true, false, false, false],
            'one time only' => [false, true, false, false],
            'selective only' => [false, false, true, false],
            'persistent only' => [false, false, false, true],
            'resize and one time' => [true, true, false, false],
            'selective and persistent' => [false, false, true, true],
        ];
    }

    public function test_multiple_instances_are_independent(): void
    {
        $options1 = new ReplyKeyboardOptions(resizeKeyboard: true);
        $options2 = new ReplyKeyboardOptions(resizeKeyboard: false);

        $this->assertTrue($options1->resizeKeyboard);
        $this->assertFalse($options2->resizeKeyboard);
    }

    public function test_options_with_real_world_scenario(): void
    {
        // Scenario: A menu keyboard that should be resized but not one-time
        $menuKeyboard = new ReplyKeyboardOptions(resizeKeyboard: true);

        $this->assertTrue($menuKeyboard->resizeKeyboard);
        $this->assertFalse($menuKeyboard->oneTimeKeyboard);

        // Scenario: A settings keyboard that should appear once
        $settingsKeyboard = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            oneTimeKeyboard: true
        );

        $this->assertTrue($settingsKeyboard->resizeKeyboard);
        $this->assertTrue($settingsKeyboard->oneTimeKeyboard);

        // Scenario: A persistent admin keyboard
        $adminKeyboard = new ReplyKeyboardOptions(
            resizeKeyboard: true,
            isPersistent: true
        );

        $this->assertTrue($adminKeyboard->resizeKeyboard);
        $this->assertTrue($adminKeyboard->isPersistent);
    }
}
