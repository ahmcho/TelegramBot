<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Enums;

/**
 * Parse Mode Enumeration
 *
 * Defines supported text parse modes for Telegram messages
 */
enum ParseMode: string
{
    case MARKDOWN = 'Markdown';
    case MARKDOWN_V2 = 'MarkdownV2';
    case HTML = 'HTML';

    /**
     * Check if this parse mode requires escaping
     */
    public function requiresEscaping(): bool
    {
        return $this === self::MARKDOWN_V2;
    }

    /**
     * Get the formatter class for this parse mode
     */
    public function getFormatterClass(): string
    {
        return match($this) {
            self::HTML => \AhmCho\Telegram\Formatting\HtmlFormatter::class,
            self::MARKDOWN_V2 => \AhmCho\Telegram\Formatting\MarkdownV2Formatter::class,
            self::MARKDOWN => \AhmCho\Telegram\Formatting\MarkdownV2Formatter::class,
        };
    }
}
