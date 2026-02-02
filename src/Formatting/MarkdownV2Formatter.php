<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Formatting;

/**
 * MarkdownV2 Formatter
 *
 * Formats text using Telegram's MarkdownV2 format
 */
class MarkdownV2Formatter implements TextFormatterInterface
{
    private const SPECIAL_CHARS = [
        '_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+',
        '-', '=', '|', '{', '}', '.', '!'
    ];

    public function escape(string $text): string
    {
        foreach (self::SPECIAL_CHARS as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }

        return $text;
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

    public function pre(string $text): string
    {
        return "```" . "\n" . $text . "\n" . "```";
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
        return '#' . ltrim($tag, '#');
    }
}
