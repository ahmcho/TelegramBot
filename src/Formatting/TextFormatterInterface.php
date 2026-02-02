<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Formatting;

/**
 * Text Formatter Interface
 *
 * Contract for formatting text in different Telegram parse modes
 */
interface TextFormatterInterface
{
    public function escape(string $text): string;
    public function bold(string $text): string;
    public function italic(string $text): string;
    public function underline(string $text): string;
    public function strikethrough(string $text): string;
    public function code(string $text): string;
    public function pre(string $text): string;
    public function link(string $text, string $url): string;
    public function mention(string $text, string $username): string;
    public function hashtag(string $tag): string;
}
