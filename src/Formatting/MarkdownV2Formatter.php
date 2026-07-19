<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Formatting;

/**
 * MarkdownV2 Formatter
 *
 * Formats text using Telegram's MarkdownV2 format
 */
final class MarkdownV2Formatter implements TextFormatterInterface
{
    // Backslash is included so it escapes itself, same as every other special char
    private const ESCAPE_MAP = [
        '\\' => '\\\\', '_' => '\\_', '*' => '\\*', '[' => '\\[', ']' => '\\]',
        '(' => '\\(', ')' => '\\)', '~' => '\\~', '`' => '\\`', '>' => '\\>',
        '#' => '\\#', '+' => '\\+', '-' => '\\-', '=' => '\\=', '|' => '\\|',
        '{' => '\\{', '}' => '\\}', '.' => '\\.', '!' => '\\!',
    ];

    public function escape(string $text): string
    {
        return strtr($text, self::ESCAPE_MAP);
    }

    public function bold(string $text): string
    {
        return '*' . $this->escape($text) . '*';
    }

    public function italic(string $text): string
    {
        return '_' . $this->escape($text) . '_';
    }

    public function underline(string $text): string
    {
        return '__' . $this->escape($text) . '__';
    }

    public function strikethrough(string $text): string
    {
        return '~' . $this->escape($text) . '~';
    }

    public function code(string $text): string
    {
        return '`' . $this->escape($text) . '`';
    }

    /**
     * Pre-formatted code block
     *
     * Note: Inside pre blocks, only ` and \ need to be escaped.
     * All other characters are treated as literal text.
     */
    public function pre(string $text): string
    {
        $escaped = str_replace(['\\', '`'], ['\\\\', '\`'], $text);
        return "```\n" . $escaped . "\n```";
    }

    public function link(string $text, string $url): string
    {
        return '[' . $this->escape($text) . '](' . $this->escape($url) . ')';
    }

    public function mention(string $text, string $username): string
    {
        return '[' . $this->escape($text) . '](tg://user?id=' . $username . ')';
    }

    public function hashtag(string $tag): string
    {
        return '#' . $this->escape(ltrim($tag, '#'));
    }
}
