<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Traits;

use AhmCho\Telegram\Enums\ParseMode;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

/**
 * MarkdownV2 Escape Trait
 *
 * Provides auto-escaping functionality for MarkdownV2 format
 * to prevent API errors from unescaped special characters
 */
trait MarkdownV2EscapeTrait
{
    /**
     * Auto-escape text and caption for MarkdownV2 format
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function escapeForMarkdownV2(array $params): array
    {
        if (!isset($params['parse_mode']) || $params['parse_mode'] !== ParseMode::MARKDOWN_V2->value) {
            return $params;
        }

        $formatter = new MarkdownV2Formatter();

        if (isset($params['text']) && is_string($params['text'])) {
            $params['text'] = $formatter->escape($params['text']);
        }

        if (isset($params['caption']) && is_string($params['caption'])) {
            $params['caption'] = $formatter->escape($params['caption']);
        }

        return $params;
    }
}
