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

    public function requiresEscaping(): bool
    {
        return $this === self::MARKDOWN_V2;
    }
}
