<?php

declare(strict_types=1);

/**
 * Echo Bot Example - Modern API
 *
 * A simple bot that repeats received messages.
 * This example demonstrates long polling with the modern API.
 *
 * Modern features showcased:
 * - Service-oriented API with $bot->messages()
 * - sendRaw() method to preserve Markdown formatting from user input
 * - PHP 8.1+ features (strict types, proper typing)
 */

use AhmCho\Telegram\Bot\TelegramBot;

require_once __DIR__ . '/../autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

try {
    $bot = new TelegramBot();

    echo "Echo Bot started...\n";
    echo "Press Ctrl+C to stop\n\n";

    $offset = 0;

    while (true) {
        try {
            // Get updates using long polling
            $updates = $bot->getUpdates([
                'offset' => $offset,
                'timeout' => 30  // Wait up to 30 seconds for new updates
            ]);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                // Handle message
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';

                    // Skip empty messages
                    if (empty($text)) {
                        continue;
                    }

                    echo "[{$chatId}] $text\n";

                    // Echo the message back with sendRaw to preserve formatting!
                    // Use sendRaw() when you want to preserve Markdown formatting from user input
                    $bot->messages()->send([
                        'chat_id' => $chatId,
                        'text' => "You said: $text",
                        'parse_mode' => 'MarkdownV2'
                    ]);
                }

                // Handle edited messages
                if (isset($update['edited_message'])) {
                    $message = $update['edited_message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';

                    echo "[{$chatId}] Edited: $text\n";

                    $bot->messages()->sendRaw([
                        'chat_id' => $chatId,
                        'text' => "You edited to: $text",
                        'parse_mode' => 'MarkdownV2'
                    ]);
                }
            }
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            sleep(5);  // Wait before retrying
        }
    }
} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
