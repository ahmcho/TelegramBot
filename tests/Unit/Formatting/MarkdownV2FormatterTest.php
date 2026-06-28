<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Formatting;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

/**
 * MarkdownV2 Formatter Tests
 *
 * Tests all special characters: `_*[]()~`>+=-{}.|\!`
 * Edge cases in escaping, formatting methods, method chaining,
 * and auto-escaping preserves user intent.
 */
final class MarkdownV2FormatterTest extends TestCase
{
    private MarkdownV2Formatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new MarkdownV2Formatter();
    }

    public function test_escape_with_underscore(): void
    {
        $result = $this->formatter->escape('test_text');

        $this->assertSame('test\_text', $result);
    }

    public function test_escape_with_asterisk(): void
    {
        $result = $this->formatter->escape('test*text');

        $this->assertSame('test\*text', $result);
    }

    public function test_escape_with_square_brackets(): void
    {
        $result = $this->formatter->escape('test[text]');

        $this->assertSame('test\[text\]', $result);
    }

    public function test_escape_with_parentheses(): void
    {
        $result = $this->formatter->escape('test(text)');

        $this->assertSame('test\(text\)', $result);
    }

    public function test_escape_with_tilde(): void
    {
        $result = $this->formatter->escape('test~text');

        $this->assertSame('test\~text', $result);
    }

    public function test_escape_with_backtick(): void
    {
        $result = $this->formatter->escape('test`text`');

        $this->assertSame('test\`text\`', $result);
    }

    public function test_escape_with_greater_than(): void
    {
        $result = $this->formatter->escape('test>text');

        $this->assertSame('test\>text', $result);
    }

    public function test_escape_with_hash(): void
    {
        $result = $this->formatter->escape('test#text');

        $this->assertSame('test\#text', $result);
    }

    public function test_escape_with_plus(): void
    {
        $result = $this->formatter->escape('test+text');

        $this->assertSame('test\+text', $result);
    }

    public function test_escape_with_minus(): void
    {
        $result = $this->formatter->escape('test-text');

        $this->assertSame('test\-text', $result);
    }

    public function test_escape_with_equals(): void
    {
        $result = $this->formatter->escape('test=text');

        $this->assertSame('test\=text', $result);
    }

    public function test_escape_with_pipe(): void
    {
        $result = $this->formatter->escape('test|text');

        $this->assertSame('test\|text', $result);
    }

    public function test_escape_with_curly_braces(): void
    {
        $result = $this->formatter->escape('test{text}');

        $this->assertSame('test\{text\}', $result);
    }

    public function test_escape_with_dot(): void
    {
        $result = $this->formatter->escape('test.text');

        $this->assertSame('test\.text', $result);
    }

    public function test_escape_with_exclamation(): void
    {
        $result = $this->formatter->escape('test!text');

        $this->assertSame('test\!text', $result);
    }

    public function test_escape_with_all_special_chars(): void
    {
        $text = '\\_*[]()~`>#+-=|{}.!';

        $result = $this->formatter->escape($text);

        // All 19 characters should be escaped
        $this->assertSame('\\\\\_\*\[\]\(\)\~\`\>\#\+\-\=\|\{\}\.\!', $result);
    }

    public function test_escape_with_multiple_occurrences_of_same_char(): void
    {
        $result = $this->formatter->escape('test_text_more_text');

        $this->assertSame('test\_text\_more\_text', $result);
    }

    public function test_escape_with_consecutive_special_chars(): void
    {
        $result = $this->formatter->escape('test**text');

        $this->assertSame('test\*\*text', $result);
    }

    public function test_escape_empty_string(): void
    {
        $result = $this->formatter->escape('');

        $this->assertSame('', $result);
    }

    public function test_escape_string_with_no_special_chars(): void
    {
        $result = $this->formatter->escape('normal text 123');

        $this->assertSame('normal text 123', $result);
    }

    public function test_bold_wraps_text_with_asterisks(): void
    {
        $result = $this->formatter->bold('bold text');

        $this->assertSame('*bold text*', $result);
    }

    public function test_bold_escapes_special_chars(): void
    {
        $result = $this->formatter->bold('bold_text');

        $this->assertSame('*bold\_text*', $result);
    }

    public function test_italic_wraps_text_with_underscore(): void
    {
        $result = $this->formatter->italic('italic text');

        $this->assertSame('_italic text_', $result);
    }

    public function test_italic_escapes_special_chars(): void
    {
        $result = $this->formatter->italic('italic*text');

        $this->assertSame('_italic\*text_', $result);
    }

    public function test_underline_wraps_text_with_double_underscore(): void
    {
        $result = $this->formatter->underline('underline text');

        $this->assertSame('__underline text__', $result);
    }

    public function test_underline_escapes_special_chars(): void
    {
        $result = $this->formatter->underline('under_line');

        $this->assertSame('__under\_line__', $result);
    }

    public function test_strikethrough_wraps_text_with_tilde(): void
    {
        $result = $this->formatter->strikethrough('strikethrough text');

        $this->assertSame('~strikethrough text~', $result);
    }

    public function test_strikethrough_escapes_special_chars(): void
    {
        $result = $this->formatter->strikethrough('strike~text');

        $this->assertSame('~strike\~text~', $result);
    }

    public function test_code_wraps_text_with_backtick(): void
    {
        $result = $this->formatter->code('code');

        $this->assertSame('`code`', $result);
    }

    public function test_code_escapes_special_chars(): void
    {
        $result = $this->formatter->code('code_text');

        $this->assertSame('`code\_text`', $result);
    }

    public function test_pre_wraps_text_with_triple_backtick(): void
    {
        $result = $this->formatter->pre('pre formatted');

        $this->assertSame("```\npre formatted\n```", $result);
    }

    public function test_pre_does_not_escape_regular_special_chars(): void
    {
        $result = $this->formatter->pre('var _test = "value";');

        $this->assertSame("```\nvar _test = \"value\";\n```", $result);
    }

    public function test_pre_escapes_backticks_and_backslashes(): void
    {
        $result = $this->formatter->pre('echo `hello`; path\\file');

        $this->assertSame("```\necho \\`hello\\`; path\\\\file\n```", $result);
    }

    public function test_link_creates_markdown_link(): void
    {
        $result = $this->formatter->link('Google', 'https://google.com');

        // Note: The implementation escapes dots in URLs
        $this->assertSame('[Google](https://google\.com)', $result);
    }

    public function test_link_escapes_special_chars_in_text_and_url(): void
    {
        $result = $this->formatter->link('Test_Link', 'https://example.com/test_path');

        // Note: The implementation escapes dots and underscores in URLs
        $this->assertSame('[Test\_Link](https://example\.com/test\_path)', $result);
    }

    public function test_mention_creates_telegram_mention(): void
    {
        $result = $this->formatter->mention('User Name', '123456');

        $this->assertSame('[User Name](tg://user?id=123456)', $result);
    }

    public function test_mention_escapes_special_chars_in_name(): void
    {
        $result = $this->formatter->mention('User_Name', '123456');

        $this->assertSame('[User\_Name](tg://user?id=123456)', $result);
    }

    public function test_hashtag_adds_hash_if_missing(): void
    {
        $result = $this->formatter->hashtag('test');

        $this->assertSame('#test', $result);
    }

    public function test_hashtag_does_not_duplicate_hash(): void
    {
        $result = $this->formatter->hashtag('#test');

        $this->assertSame('#test', $result);
    }

    public function test_hashtag_with_multiple_hashes(): void
    {
        $result = $this->formatter->hashtag('###test');

        $this->assertSame('#test', $result);
    }

    public function test_hashtag_escapes_special_chars_in_tag(): void
    {
        $result = $this->formatter->hashtag('my_tag');

        $this->assertSame('#my\_tag', $result);
    }

    public function test_chaining_multiple_formatting_methods(): void
    {
        $bold = $this->formatter->bold('bold');
        $italic = $this->formatter->italic('italic');

        $this->assertSame('*bold*', $bold);
        $this->assertSame('_italic_', $italic);
    }

    public function test_complex_nested_formatting(): void
    {
        $link = $this->formatter->link('Click Here', 'https://example.com/path?query=value');

        // Note: The implementation escapes dots, equals, and backslashes in URLs
        $this->assertSame('[Click Here](https://example\.com/path?query\=value)', $link);
    }

    public function test_escape_escapes_backslash(): void
    {
        $result = $this->formatter->escape('test\\text');

        // Backslash must be escaped to \\
        $this->assertSame('test\\\\text', $result);
    }

    public function test_escape_escapes_backslash_correctly(): void
    {
        $result = $this->formatter->escape('\\');

        // Single backslash should become double backslash
        $this->assertSame('\\\\', $result);
    }

    public function test_escape_escapes_backslash_in_sequence(): void
    {
        $result = $this->formatter->escape('path\\to\\file.txt');

        // Multiple backslashes should all be escaped
        $this->assertSame('path\\\\to\\\\file\.txt', $result);
    }

    public function test_escape_escapes_backslash_with_special_chars(): void
    {
        $result = $this->formatter->escape('\\*_');

        // Backslash + special chars should all be escaped
        // Note: Underscore is also escaped
        $this->assertSame('\\\\\*\_', $result);
    }

    public function test_escape_with_unicode_characters(): void
    {
        $result = $this->formatter->escape('测试_text');

        $this->assertSame('测试\_text', $result);
    }

    public function test_escape_with_emoji(): void
    {
        $result = $this->formatter->escape('🎉_test');

        $this->assertSame('🎉\_test', $result);
    }

    public function test_escape_with_backslash_and_unicode(): void
    {
        $result = $this->formatter->escape('测试\\text');

        // Unicode characters should be preserved, backslash escaped
        $this->assertSame('测试\\\\text', $result);
    }

    public function test_escape_with_backslash_and_emoji(): void
    {
        $result = $this->formatter->escape('🎉\\test');

        // Emoji should be preserved, backslash escaped
        $this->assertSame('🎉\\\\test', $result);
    }

    public function test_formatting_methods_with_empty_string(): void
    {
        $this->assertSame('**', $this->formatter->bold(''));
        $this->assertSame('__', $this->formatter->italic(''));
        $this->assertSame('____', $this->formatter->underline(''));
        $this->assertSame('~~', $this->formatter->strikethrough(''));
        $this->assertSame('``', $this->formatter->code(''));
    }

    public function test_pre_with_empty_string(): void
    {
        $result = $this->formatter->pre('');

        $this->assertSame("```\n\n```", $result);
    }

    public function test_special_chars_are_correctly_defined(): void
    {
        $reflection = new \ReflectionClass(MarkdownV2Formatter::class);
        $constant = $reflection->getConstant('SPECIAL_CHARS');

        // Note: Backslash is NOT in SPECIAL_CHARS - it's handled separately
        $expected = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+',
            '-', '=', '|', '{', '}', '.', '!'];

        $this->assertSame($expected, $constant);
    }

    /**
     * @dataProvider realWorldExampleProvider
     */
    public function test_real_world_examples(string $input, string $expected): void
    {
        $result = $this->formatter->escape($input);

        $this->assertSame($expected, $result);
    }

    public static function realWorldExampleProvider(): array
    {
        return [
            'username with underscore' => ['@user_name', '@user\_name'],
            'url with query params' => ['https://example.com?param=value', 'https://example\.com?param\=value'],
            'path with dots' => ['/path/to/file.txt', '/path/to/file\.txt'],
            'code reference' => ['Class.method()', 'Class\.method\(\)'],
            'price' => ['$9.99', '$9\.99'],
            'email' => ['user@example.com', 'user@example\.com'],
            'markdown-like text' => ['*not bold*', '\*not bold\*'],
        ];
    }

    public function test_formatter_is_immutable_stateless(): void
    {
        // Formatter should not maintain state between calls
        $result1 = $this->formatter->bold('first');
        $result2 = $this->formatter->italic('second');

        $this->assertSame('*first*', $result1);
        $this->assertSame('_second_', $result2);
    }

    /**
     * @dataProvider realWorldExampleWithBackslashProvider
     */
    public function test_real_world_examples_with_backslash(string $input, string $expected): void
    {
        $result = $this->formatter->escape($input);

        $this->assertSame($expected, $result);
    }

    public static function realWorldExampleWithBackslashProvider(): array
    {
        return [
            'windows path' => ['C:\\Users\\name', 'C:\\\\Users\\\\name'],
            'unix path' => ['/path/to/file.txt', '/path/to/file\.txt'],
            'regex pattern' => ['\\d+', '\\\\d\+'],
            'backslash only' => ['\\', '\\\\'],
        ];
    }
}
