<?php

declare(strict_types=1);

/**
 * Retry Demonstration Example
 *
 * This example demonstrates automatic retry functionality with:
 * - Exponential backoff
 * - Rate limit handling (429 errors)
 * - Retry callbacks for monitoring
 *
 * Usage:
 * php examples/retry-demo.php
 */

use AhmCho\Telegram\Bot\TelegramBot;

require_once __DIR__ . '/../autoload.php';

// Load environment variables (using the modern EnvLoader)
require_once __DIR__ . '/../src/Config/EnvLoader.php';

$loader = new \AhmCho\Telegram\Config\EnvLoader();
$loader->load();

try {
    $bot = new TelegramBot();

    echo "=== Retry Demo ===\n\n";

    // Get chat ID from command line or use a default
    $chatId = (int)($argv[1] ?? 0);

    if ($chatId === 0) {
        echo "Usage: php retry-demo.php <chat_id>\n";
        echo "Send any message to your bot to get your chat_id\n";
        exit(1);
    }

    echo "Sending message to chat ID: $chatId\n\n";

    // Example 1: Simple retry with default settings
    echo "1. Simple message with retry (3 attempts, exponential backoff):\n";
    try {
        $result = $bot->sendMessageWithRetry([
            'chat_id' => $chatId,
            'text' => 'Test message with automatic retry',
            'parse_mode' => 'MarkdownV2'
        ]);
        echo "✓ Message sent successfully!\n";
        echo "  Message ID: {$result['message_id']}\n\n";
    } catch (\Exception $e) {
        echo "✗ Failed after all retries: {$e->getMessage()}\n\n";
    }

    // Example 2: Retry with custom callback
    echo "2. Retry with progress callback:\n";
    try {
        $result = $bot->sendMessageWithRetry(
            [
                'chat_id' => $chatId,
                'text' => 'Test with retry callback',
                'parse_mode' => 'MarkdownV2'
            ],
            [
                'max_retries' => 3,
                'initial_delay_ms' => 500,
                'on_retry' => function ($attempt, $error, $delayMs) {
                    echo "  ⏳ Retry attempt $after, error: {$error->getMessage()}\n";
                    echo "     Waiting {$delayMs}ms before retry...\n";
                }
            ]
        );
        echo "✓ Message sent successfully!\n\n";
    } catch (\Exception $e) {
        echo "✗ Failed: {$e->getMessage()}\n\n";
    }

    // Example 3: Bulk operation with retry
    echo "3. Bulk send with retry:\n";
    try {
        $results = $bot->sendBulkWithRetry(
            [
                ['chat_id' => $chatId, 'text' => 'Bulk message 1'],
                ['chat_id' => $chatId, 'text' => 'Bulk message 2'],
                ['chat_id' => $chatId, 'text' => 'Bulk message 3'],
            ],
            ['max_concurrent' => 10],  // Bulk options
            ['max_retries' => 2]        // Retry options
        );
        echo "✓ Bulk sent: {$results['successful']}/{$results['total']} successful\n\n";
    } catch (\Exception $e) {
        echo "✗ Bulk failed: {$e->getMessage()}\n\n";
    }

    // Example 4: Custom retry options
    echo "4. Custom retry configuration:\n";
    try {
        $result = $bot->executeWithRetry(
            fn() => $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => 'Custom retry test'
            ]),
            [
                'max_retries' => 5,           // More retries
                'initial_delay_ms' => 2000,   // Start with 2 second delay
                'max_delay_ms' => 30000,      // Max 30 second delay
                'on_retry' => function ($attempt, $error, $delayMs) {
                    echo "  ⚠️  Attempt $attempt failed, retrying in {$delayMs}ms...\n";
                }
            ]
        );
        echo "✓ Custom retry successful!\n\n";
    } catch (\Exception $e) {
        echo "✗ Custom retry failed: {$e->getMessage()}\n\n";
    }

    echo "=== Demo Complete ===\n";

} catch (\Throwable $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
