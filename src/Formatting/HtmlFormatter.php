<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Formatting;

/**
 * HTML Formatter
 *
 * Formats text using Telegram's HTML parse mode
 */
class HtmlFormatter implements TextFormatterInterface
{
    public function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function bold(string $text): string
    {
        return '<b>' . $this->escape($text) . '</b>';
    }

    public function italic(string $text): string
    {
        return '<i>' . $this->escape($text) . '</i>';
    }

    public function underline(string $text): string
    {
        return '<u>' . $this->escape($text) . '</u>';
    }

    public function strikethrough(string $text): string
    {
        return '<s>' . $this->escape($text) . '</s>';
    }

    public function code(string $text): string
    {
        return '<code>' . $this->escape($text) . '</code>';
    }

    public function pre(string $text): string
    {
        return '<pre>' . $text . '</pre>';
    }

    public function link(string $text, string $url): string
    {
        return '<a href="' . $this->escape($url) . '">' . $this->escape($text) . '</a>';
    }

    public function mention(string $text, string $username): string
    {
        return '<a href="tg://user?id=' . $username . '">' . $this->escape($text) . '</a>';
    }

    public function hashtag(string $tag): string
    {
        return '#' . ltrim($tag, '#');
    }
}
