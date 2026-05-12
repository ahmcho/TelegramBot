<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Enums\ParseMode;

/**
 * ParseMode Enum Tests
 */
final class ParseModeTest extends TestCase
{
    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('Markdown', ParseMode::MARKDOWN->value);
        $this->assertSame('MarkdownV2', ParseMode::MARKDOWN_V2->value);
        $this->assertSame('HTML', ParseMode::HTML->value);
    }

    public function test_requires_escaping_for_markdown_v2(): void
    {
        $this->assertTrue(ParseMode::MARKDOWN_V2->requiresEscaping());
    }

    public function test_requires_escaping_for_markdown(): void
    {
        $this->assertFalse(ParseMode::MARKDOWN->requiresEscaping());
    }

    public function test_requires_escaping_for_html(): void
    {
        $this->assertFalse(ParseMode::HTML->requiresEscaping());
    }

    public function test_get_formatter_class_for_html(): void
    {
        $formatter = ParseMode::HTML->getFormatterClass();
        $this->assertSame(\AhmCho\Telegram\Formatting\HtmlFormatter::class, $formatter);
    }

    public function test_get_formatter_class_for_markdown_v2(): void
    {
        $formatter = ParseMode::MARKDOWN_V2->getFormatterClass();
        $this->assertSame(\AhmCho\Telegram\Formatting\MarkdownV2Formatter::class, $formatter);
    }

    public function test_get_formatter_class_for_markdown(): void
    {
        $formatter = ParseMode::MARKDOWN->getFormatterClass();
        $this->assertSame(\AhmCho\Telegram\Formatting\MarkdownV2Formatter::class, $formatter);
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertIsString(ParseMode::MARKDOWN->value);
        $this->assertIsString(ParseMode::MARKDOWN_V2->value);
        $this->assertIsString(ParseMode::HTML->value);
    }

    public function test_from_method_returns_correct_enum(): void
    {
        $mode = ParseMode::from('Markdown');
        $this->assertSame(ParseMode::MARKDOWN, $mode);

        $mode = ParseMode::from('MarkdownV2');
        $this->assertSame(ParseMode::MARKDOWN_V2, $mode);

        $mode = ParseMode::from('HTML');
        $this->assertSame(ParseMode::HTML, $mode);
    }

    public function test_try_from_method_returns_correct_enum(): void
    {
        $mode = ParseMode::tryFrom('Markdown');
        $this->assertSame(ParseMode::MARKDOWN, $mode);

        $mode = ParseMode::tryFrom('HTML');
        $this->assertSame(ParseMode::HTML, $mode);

        $invalid = ParseMode::tryFrom('InvalidMode');
        $this->assertNull($invalid);
    }

    public function test_cases_method_returns_all_enum_cases(): void
    {
        $cases = ParseMode::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(ParseMode::MARKDOWN, $cases);
        $this->assertContains(ParseMode::MARKDOWN_V2, $cases);
        $this->assertContains(ParseMode::HTML, $cases);
    }

    public function test_enum_has_all_expected_methods(): void
    {
        $mode = ParseMode::MARKDOWN_V2;
        $this->assertTrue(method_exists($mode, 'requiresEscaping'));
        $this->assertTrue(method_exists($mode, 'getFormatterClass'));
    }

    public function test_all_parse_modes_are_different(): void
    {
        $this->assertNotSame(ParseMode::MARKDOWN, ParseMode::MARKDOWN_V2);
        $this->assertNotSame(ParseMode::MARKDOWN, ParseMode::HTML);
        $this->assertNotSame(ParseMode::MARKDOWN_V2, ParseMode::HTML);
    }
}
