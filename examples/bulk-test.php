<?php
/**
 * Bulk Messaging Test Script
 *
 * This script demonstrates the bulk messaging functionality
 * using curl_multi_exec for parallel requests.
 */

require_once __DIR__ . '/../src/TelegramBot.php';

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
$results = $bot->sendMessagesBulk([
    ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 1'],
    ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 2'],
    ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Bulk Test Message 3'],
]);

echo "Sent: {$results['successful']}/{$results['total']} messages\n";
if ($results['failed'] > 0) {
    echo "Failed: " . implode(', ', $results['errors']) . "\n";
}
echo "\n";

// Test 2: Broadcast same message to multiple chats
echo "Test 2: Broadcasting to multiple chats...\n";
$chatIds = [
    getenv('TEST_CHAT_ID') ?: '162592443',
    // Add more chat IDs here for testing
];

$results = $bot->broadcastMessage(
    $chatIds,
    'This is a broadcast test message!',
    ['parse_mode' => 'Markdown']
);

echo "Broadcast: {$results['successful']}/{$results['total']} delivered\n";
foreach ($results['results'] as $result) {
    if ($result['success']) {
        echo "  ✅ Sent to {$result['chat_id']} (message_id: {$result['message_id']})\n";
    } else {
        echo "  ❌ Failed for {$result['chat_id']}: {$result['error']}\n";
    }
}
echo "\n";

// Test 3: Error handling - include invalid chat_id
echo "Test 3: Testing error handling with invalid chat_id...\n";
$bot->throwExceptions(false);  // Disable exceptions to test error handling
$results = $bot->sendMessagesBulk([
    ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Valid message'],
    ['chat_id' => '999999999', 'text' => 'Invalid chat (will fail)'],
]);
$bot->throwExceptions(true);  // Re-enable exceptions for subsequent tests

echo "Results: {$results['successful']}/{$results['total']} successful\n";
foreach ($results['results'] as $index => $result) {
    if ($result['success']) {
        echo "  Message $index: ✅ Sent to {$result['chat_id']}\n";
    } else {
        echo "  Message $index: ❌ Failed - {$result['error']}\n";
    }
}
echo "\n";

// Test 4: Rate limiting with delay
echo "Test 4: Testing with rate limiting (delay between batches)...\n";
$results = $bot->sendMessagesBulk(
    [
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 1'],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 2'],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 3'],
        ['chat_id' => getenv('TEST_CHAT_ID') ?: '162592443', 'text' => 'Rate limited message 4'],
    ],
    ['max_concurrent' => 2, 'delay_ms' => 500]
);

echo "With rate limiting: {$results['successful']}/{$results['total']} sent\n";
echo "\n";

// Test 5: Empty array handling
echo "Test 5: Testing empty array...\n";
$results = $bot->sendMessagesBulk([]);
echo "Empty result: " . json_encode($results) . "\n";
echo "\n";

echo "=== All Tests Complete ===\n";
