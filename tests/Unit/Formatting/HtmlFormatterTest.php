<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Formatting;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Formatting\HtmlFormatter;

/**
 * HTML Formatter Tests
 *
 * Tests HTML tag escaping, formatting methods (bold, italic, etc.),
 * and special character handling.
 */
final class HtmlFormatterTest extends TestCase
{
    private HtmlFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new HtmlFormatter();
    }

    public function test_escape_converts_less_than_sign(): void
    {
        $result = $this->formatter->escape('test < value');

        $this->assertSame('test &lt; value', $result);
    }

    public function test_escape_converts_greater_than_sign(): void
    {
        $result = $this->formatter->escape('test > value');

        $this->assertSame('test &gt; value', $result);
    }

    public function test_escape_converts_ampersand(): void
    {
        $result = $this->formatter->escape('A & B');

        $this->assertSame('A &amp; B', $result);
    }

    public function test_escape_converts_double_quotes(): void
    {
        $result = $this->formatter->escape('test "value"');

        $this->assertSame('test &quot;value&quot;', $result);
    }

    public function test_escape_converts_single_quotes(): void
    {
        $result = $this->formatter->escape("test 'value'");

        // htmlspecialchars uses &apos; in HTML5 mode
        $this->assertSame('test &apos;value&apos;', $result);
    }

    public function test_escape_with_all_special_chars(): void
    {
        $result = $this->formatter->escape('<>&"\'');

        // htmlspecialchars uses &apos; in HTML5 mode
        $this->assertSame('&lt;&gt;&amp;&quot;&apos;', $result);
    }

    public function test_escape_with_multiple_special_chars(): void
    {
        $result = $this->formatter->escape('<a href="test">Link</a>');

        $this->assertSame('&lt;a href=&quot;test&quot;&gt;Link&lt;/a&gt;', $result);
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

    public function test_escape_preserves_unicode(): void
    {
        $result = $this->formatter->escape('测试 <text>');

        $this->assertSame('测试 &lt;text&gt;', $result);
    }

    public function test_escape_preserves_emoji(): void
    {
        $result = $this->formatter->escape('🎉 <emoji>');

        $this->assertSame('🎉 &lt;emoji&gt;', $result);
    }

    public function test_bold_wraps_with_b_tag(): void
    {
        $result = $this->formatter->bold('bold text');

        $this->assertSame('<b>bold text</b>', $result);
    }

    public function test_bold_escapes_content(): void
    {
        $result = $this->formatter->bold('bold <text>');

        $this->assertSame('<b>bold &lt;text&gt;</b>', $result);
    }

    public function test_italic_wraps_with_i_tag(): void
    {
        $result = $this->formatter->italic('italic text');

        $this->assertSame('<i>italic text</i>', $result);
    }

    public function test_italic_escapes_content(): void
    {
        $result = $this->formatter->italic('italic & text');

        $this->assertSame('<i>italic &amp; text</i>', $result);
    }

    public function test_underline_wraps_with_u_tag(): void
    {
        $result = $this->formatter->underline('underline text');

        $this->assertSame('<u>underline text</u>', $result);
    }

    public function test_underline_escapes_content(): void
    {
        $result = $this->formatter->underline('under "line"');

        $this->assertSame('<u>under &quot;line&quot;</u>', $result);
    }

    public function test_strikethrough_wraps_with_s_tag(): void
    {
        $result = $this->formatter->strikethrough('strikethrough text');

        $this->assertSame('<s>strikethrough text</s>', $result);
    }

    public function test_strikethrough_escapes_content(): void
    {
        $result = $this->formatter->strikethrough('strike <through>');

        $this->assertSame('<s>strike &lt;through&gt;</s>', $result);
    }

    public function test_code_wraps_with_code_tag(): void
    {
        $result = $this->formatter->code('code');

        $this->assertSame('<code>code</code>', $result);
    }

    public function test_code_escapes_content(): void
    {
        $result = $this->formatter->code('code <tag>');

        $this->assertSame('<code>code &lt;tag&gt;</code>', $result);
    }

    public function test_pre_wraps_with_pre_tag(): void
    {
        $result = $this->formatter->pre('pre formatted');

        $this->assertSame('<pre>pre formatted</pre>', $result);
    }

    public function test_pre_does_not_escape_content(): void
    {
        $result = $this->formatter->pre('<div>content</div>');

        $this->assertSame('<pre><div>content</div></pre>', $result);
    }

    public function test_link_creates_html_link(): void
    {
        $result = $this->formatter->link('Google', 'https://google.com');

        $this->assertSame('<a href="https://google.com">Google</a>', $result);
    }

    public function test_link_escapes_text_and_url(): void
    {
        $result = $this->formatter->link('Click & Here', 'https://example.com?param=value&other=test');

        $this->assertSame(
            '<a href="https://example.com?param=value&amp;other=test">Click &amp; Here</a>',
            $result
        );
    }

    public function test_link_with_quotes_in_url(): void
    {
        $result = $this->formatter->link('Link', 'https://example.com?param="value"');

        $this->assertSame('<a href="https://example.com?param=&quot;value&quot;">Link</a>', $result);
    }

    public function test_mention_creates_telegram_mention_link(): void
    {
        $result = $this->formatter->mention('User Name', '123456');

        $this->assertSame('<a href="tg://user?id=123456">User Name</a>', $result);
    }

    public function test_mention_escapes_username(): void
    {
        $result = $this->formatter->mention('User & Name', '123456');

        $this->assertSame('<a href="tg://user?id=123456">User &amp; Name</a>', $result);
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

        // ltrim only removes one character, so multiple hashes are preserved
        $this->assertSame('#test', $result);
    }

    public function test_formatting_methods_with_empty_string(): void
    {
        $this->assertSame('<b></b>', $this->formatter->bold(''));
        $this->assertSame('<i></i>', $this->formatter->italic(''));
        $this->assertSame('<u></u>', $this->formatter->underline(''));
        $this->assertSame('<s></s>', $this->formatter->strikethrough(''));
        $this->assertSame('<code></code>', $this->formatter->code(''));
        $this->assertSame('<pre></pre>', $this->formatter->pre(''));
    }

    public function test_combined_formatting(): void
    {
        $bold = $this->formatter->bold('Important');
        $italic = $this->formatter->italic('Note');

        $this->assertSame('<b>Important</b> <i>Note</i>', "$bold $italic");
    }

    public function test_nested_formatting_simulation(): void
    {
        // HTML doesn't support true nesting in Telegram, but we can simulate it
        $boldLink = $this->formatter->link('Click Here', 'https://example.com');
        $result = '<b>' . $boldLink . '</b>';

        $this->assertSame('<b><a href="https://example.com">Click Here</a></b>', $result);
    }

    public function test_special_html_entities(): void
    {
        $result = $this->formatter->escape('&copy; 2024 &gt; Company &lt; Inc');

        // htmlspecialchars escapes the & in entities
        $this->assertSame('&amp;copy; 2024 &amp;gt; Company &amp;lt; Inc', $result);
    }

    public function test_newline_characters_are_preserved(): void
    {
        $result = $this->formatter->escape("line1\nline2\rline3");

        $this->assertSame("line1\nline2\rline3", $result);
    }

    public function test_tabs_are_preserved(): void
    {
        $result = $this->formatter->escape("text\twith\ttabs");

        $this->assertSame("text\twith\ttabs", $result);
    }

    /**
     * @dataProvider htmlInjectionProvider
     */
    public function test_escape_prevents_html_injection(string $input, string $expected): void
    {
        $result = $this->formatter->escape($input);

        $this->assertSame($expected, $result);
    }

    public static function htmlInjectionProvider(): array
    {
        return [
            'script tag' => ['<script>alert("xss")</script>', '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'],
            'img tag' => ['<img src=x onerror=alert(1)>', '&lt;img src=x onerror=alert(1)&gt;'],
            'div with style' => ['<div style="color:red">text</div>', '&lt;div style=&quot;color:red&quot;&gt;text&lt;/div&gt;'],
            'comment' => ['<!-- comment -->', '&lt;!-- comment --&gt;'],
            'entity' => ['&nbsp;', '&amp;nbsp;'],
        ];
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
            'email' => ['user@example.com', 'user@example.com'],
            'url' => ['https://example.com/path?query=value', 'https://example.com/path?query=value'],
            'quotes' => ['John said "Hello"', 'John said &quot;Hello&quot;'],
            'ampersand in text' => ['Tom & Jerry', 'Tom &amp; Jerry'],
            'less greater than' => ['2 < 5 && 5 > 2', '2 &lt; 5 &amp;&amp; 5 &gt; 2'],
            'mixed html' => ['<b>bold</b> and <i>italic</i>', '&lt;b&gt;bold&lt;/b&gt; and &lt;i&gt;italic&lt;/i&gt;'],
        ];
    }

    public function test_formatter_is_stateless(): void
    {
        // Formatter should not maintain state between calls
        $result1 = $this->formatter->bold('first');
        $result2 = $this->formatter->italic('second');

        $this->assertSame('<b>first</b>', $result1);
        $this->assertSame('<i>second</i>', $result2);
    }

    public function test_pre_with_multiline_content(): void
    {
        $result = $this->formatter->pre("line1\nline2\nline3");

        $this->assertSame("<pre>line1\nline2\nline3</pre>", $result);
    }

    public function test_code_with_backticks(): void
    {
        $result = $this->formatter->code('`code`');

        $this->assertSame('<code>`code`</code>', $result);
    }

    public function test_link_with_angle_brackets(): void
    {
        $result = $this->formatter->link('Link <text>', 'https://example.com');

        $this->assertSame('<a href="https://example.com">Link &lt;text&gt;</a>', $result);
    }
}
