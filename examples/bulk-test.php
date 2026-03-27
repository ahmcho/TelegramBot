<?php

declare(strict_types=1);

/**
 * Bulk Messaging Test Script - Modern API
 *
 * This script demonstrates the bulk messaging functionality
 * using the modern service-oriented API.
 *
 * Modern features showcased:
 * - Service-oriented API ($bot->messages())
 * - New bulk operations with auto-escaping for MarkdownV2
 * - BulkResult object for detailed results
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

// Create bot instance
$bot = new TelegramBot();

echo "=== Bulk Messaging Test ===\n\n";

// Test 1: Basic bulk send with different messages
echo "Test 1: Sending different messages to the same chat...\n";
try {
    $results = $bot->messages()->sendBulk([
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 1'],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 2'],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 3'],
    ]);

    echo "Sent: {$results->successful}/{$results->total} messages\n";
    if ($results->failed > 0) {
        echo "Failed: " . implode(', ', $results->errors) . "\n";
    }
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

// Test 2: Broadcast same message to multiple chats
echo "Test 2: Broadcasting to multiple chats...\n";
$chatIds = [
    getenv('TEST_CHAT_ID') ?: '162592443',
    // Add more chat IDs here for testing
];

try {
    $results = $bot->messages()->broadcast(
        $chatIds,
        'This is a broadcast test message!',
        ['parse_mode' => 'MarkdownV2']  // Auto-escaping enabled!
    );

    echo "Broadcast: {$results->successful}/{$results->total} delivered\n";
    foreach ($results->results as $result) {
        if ($result['success']) {
            echo "  ✅ Sent to {$result['chat_id']} (message_id: {$result['message_id']})\n";
        } else {
            echo "  ❌ Failed for {$result['chat_id']}: {$result['error']}\n";
        }
    }
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

// Test 3: Error handling - include invalid chat_id
echo "Test 3: Testing error handling with invalid chat_id...\n";
try {
    // Bulk operations handle errors gracefully via BulkResult object
    $results = $bot->messages()->sendBulk([
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Valid message'],
        ['chat_id' => '999999999', 'text' => 'Invalid chat (will fail)'],
    ]);

    echo "Results: {$results->successful}/{$results->total} successful\n";
    foreach ($results->results as $index => $result) {
        if ($result['success']) {
            echo "  Message $index: ✅ Sent to {$result['chat_id']}\n";
        } else {
            echo "  Message $index: ❌ Failed - {$result['error']}\n";
        }
    }
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

// Test 4: Rate limiting with delay
echo "Test 4: Testing with rate limiting (delay between batches)...\n";
try {
    $results = $bot->messages()->sendBulk(
        [
            ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 1'],
            ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 2'],
            ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 3'],
            ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 4'],
        ],
        ['max_concurrent' => 2, 'delay_ms' => 500]
    );

    echo "With rate limiting: {$results->successful}/{$results->total} sent\n";
    echo "\n";
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

// Test 5: Bulk with MarkdownV2 auto-escaping
echo "Test 5: Testing bulk with MarkdownV2 auto-escaping...\n";
try {
    $results = $bot->messages()->sendBulk([
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443',
                'text' => 'Message with *bold* and _italic_!',  // Special chars auto-escaped!
                'parse_mode' => 'MarkdownV2'
            ],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443',
                'text' => 'Another message with `code` and [links](https://example.com)!',
                'parse_mode' => 'MarkdownV2'
            ],
    ]);

    echo "MarkdownV2 bulk: {$results->successful}/{$results->total} sent (auto-escaped!)\n";
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

// Test 6: Empty array handling
echo "Test 6: Testing empty array...\n";
try {
    $results = $bot->messages()->sendBulk([]);
    echo "Empty result: " . json_encode($results) . "\n";
} catch (AhmCho\Telegram\Bulk\BulkSendException $e) {
    echo "❌ Bulk operation exception: {$e->getMessage()}\n";
    echo "Results: " . json_encode($e->getResult()) . "\n";
}
echo "\n";

echo "=== All Tests Complete ===\n";
echo "\nKey Modern Features Demonstrated:\n";
echo "- Service-oriented API with \$bot->messages()->sendBulk()\n";
echo "- Auto-escaping for MarkdownV2 in bulk operations\n";
echo "- Detailed BulkResult object with success/failure tracking\n";
echo "- Rate limiting support with max_concurrent and delay_ms\n";
echo "- Proper error handling for individual message failures\n";
