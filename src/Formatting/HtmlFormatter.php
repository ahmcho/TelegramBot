<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Formatting;

use Override;

/**
 * HTML Formatter
 *
 * Formats text using Telegram's HTML parse mode
 */
final class HtmlFormatter implements TextFormatterInterface
{
    #[Override]
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    #[Override]
    public function bold(string $text): string
    {
        return '<b>' . $this->escape($text) . '</b>';
    }

    #[Override]
    public function italic(string $text): string
    {
        return '<i>' . $this->escape($text) . '</i>';
    }

    #[Override]
    public function underline(string $text): string
    {
        return '<u>' . $this->escape($text) . '</u>';
    }

    #[Override]
    public function strikethrough(string $text): string
    {
        return '<s>' . $this->escape($text) . '</s>';
    }

    #[Override]
    public function code(string $text): string
    {
        return '<code>' . $this->escape($text) . '</code>';
    }

    #[Override]
    public function pre(string $text): string
    {
        return '<pre>' . $text . '</pre>';
    }

    #[Override]
    public function link(string $text, string $url): string
    {
        return '<a href="' . $this->escape($url) . '">' . $this->escape($text) . '</a>';
    }

    #[Override]
    public function mention(string $text, string $username): string
    {
        return '<a href="tg://user?id=' . $username . '">' . $this->escape($text) . '</a>';
    }

    #[Override]
    public function hashtag(string $tag): string
    {
        return '#' . ltrim($tag, '#');
    }
}
