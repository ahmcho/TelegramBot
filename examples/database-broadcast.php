<?php

/**
 * Database Broadcast Example - Bulk Messaging with Filters
 *
 * This example demonstrates various ways to send bulk messages to users
 * stored in the database using filters and targeting options.
 *
 * Usage:
 *   php database-broadcast.php
 *
 * Features:
 * - Broadcast to all users
 * - Broadcast to active users (custom time range)
 * - Broadcast to premium users
 * - Broadcast to users with usernames
 * - Combine multiple filters
 * - Rate limiting and error handling
 */

// Load .env file if it exists
require_once __DIR__ . '/../src/dotenv.php';

require_once __DIR__ . '/../src/TelegramBot.php';
require_once __DIR__ . '/../src/Database.php';

// Check for bot token
if (!getenv('TELEGRAM_BOT_TOKEN') && empty($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die("Error: TELEGRAM_BOT_TOKEN environment variable not set.\n" .
        "Please create a .env file with: TELEGRAM_BOT_TOKEN=your_token_here\n" .
        "Or set it with: export TELEGRAM_BOT_TOKEN='your_token_here'\n");
}

// Initialize
$bot = new TelegramBot();
$database = new Database(__DIR__ . '/../data/bot.db');
$bot->setDatabase($database);

echo "Telegram Bot Database Broadcast Tool\n";
echo "====================================\n\n";

// Get current stats
$stats = $database->getStats();
echo "Current Database Stats:\n";
echo "  Total Users: {$stats['total']}\n";
echo "  Active (30d): {$stats['active_30_days']}\n";
echo "  Premium: {$stats['premium']}\n";
echo "  With Username: {$stats['with_username']}\n\n";

// Menu
echo "Broadcast Options:\n";
echo "1. Broadcast to ALL users\n";
echo "2. Broadcast to active users (last 7 days)\n";
echo "3. Broadcast to active users (last 30 days)\n";
echo "4. Broadcast to premium users\n";
echo "5. Broadcast to users with username\n";
echo "6. Broadcast to active premium users\n";
echo "7. Custom broadcast with filters\n";
echo "8. Exit\n\n";

while (true) {
    echo "\nSelect option (1-8): ";
    $option = trim(fgets(STDIN));

    switch ($option) {
        case '1':
            broadcastAll($bot);
            break;
        case '2':
            broadcastActive($bot, 7);
            break;
        case '3':
            broadcastActive($bot, 30);
            break;
        case '4':
            broadcastPremium($bot);
            break;
        case '5':
            broadcastWithUsername($bot);
            break;
        case '6':
            broadcastActivePremium($bot);
            break;
        case '7':
            broadcastCustom($bot, $database);
            break;
        case '8':
            echo "Exiting...\n";
            exit(0);
        default:
            echo "Invalid option. Please select 1-8.\n";
    }
}

/**
 * Broadcast to all users
 */
function broadcastAll(TelegramBot $bot): void
{
    echo "\n--- Broadcast to ALL Users ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $results = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        [],
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($results);
}

/**
 * Broadcast to active users
 */
function broadcastActive(TelegramBot $bot, int $days): void
{
    echo "\n--- Broadcast to Active Users (Last $days Days) ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $results = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        ['active_since' => date('Y-m-d H:i:s', strtotime("-$days days"))],
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($results);
}

/**
 * Broadcast to premium users
 */
function broadcastPremium(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Premium Users ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $results = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        ['is_premium' => true],
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($results);
}

/**
 * Broadcast to users with username
 */
function broadcastWithUsername(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Users With Username ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $results = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        ['has_username' => true],
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($results);
}

/**
 * Broadcast to active premium users
 */
function broadcastActivePremium(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Active Premium Users (Last 30 Days) ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $results = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        [
            'is_premium' => true,
            'active_since' => date('Y-m-d H:i:s', strtotime('-30 days'))
        ],
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($results);
}

/**
 * Custom broadcast with user-defined filters
 */
function broadcastCustom(TelegramBot $bot, Database $database): void
{
    echo "\n--- Custom Broadcast ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = [];

    // Active since
    echo "Active since (days ago, or 0 to skip): ";
    $days = (int)trim(fgets(STDIN));
    if ($days > 0) {
        $filters['active_since'] = date('Y-m-d H:i:s', strtotime("-$days days"));
    }

    // Premium only
    echo "Premium only? (y/n): ";
    $premium = strtolower(trim(fgets(STDIN)));
    if ($premium === 'y') {
        $filters['is_premium'] = true;
    }

    // Has username
    echo "Has username only? (y/n): ";
    $username = strtolower(trim(fgets(STDIN)));
    if ($username === 'y') {
        $filters['has_username'] = true;
    }

    // Limit
    echo "Limit (0 for unlimited): ";
    $limit = (int)trim(fgets(STDIN));
    if ($limit > 0) {
        $filters['limit'] = $limit;
    }

    // Show filter summary
    echo "\nFilter Summary:\n";
    if (!empty($filters)) {
        foreach ($filters as $key => $value) {
            echo "  $key: $value\n";
        }

        // Estimate recipients
        $chatIds = $database->getAllChatIds($filters);
        echo "\nEstimated recipients: " . count($chatIds) . "\n";
    } else {
        echo "  No filters (all users)\n";
    }

    echo "\nSend broadcast? (y/n): ";
    $confirm = strtolower(trim(fgets(STDIN)));

    if ($confirm === 'y') {
        $results = $bot->broadcastToDatabase(
            $message,
            ['parse_mode' => $parseMode],
            $filters,
            ['max_concurrent' => 30, 'delay_ms' => 1000]
        );

        displayResults($results);
    } else {
        echo "Broadcast cancelled.\n";
    }
}

/**
 * Get message from user input
 */
function getMessageFromUser(): string
{
    echo "Enter your message (use 'MESSAGE_END' on a new line to finish):\n";
    $message = '';
    while (true) {
        $line = fgets(STDIN);
        if (trim($line) === 'MESSAGE_END') {
            break;
        }
        $message .= $line;
    }
    return trim($message);
}

/**
 * Get parse mode from user
 */
function getParseMode(): string
{
    echo "Parse mode (1=None, 2=Markdown, 3=HTML) [1]: ";
    $mode = trim(fgets(STDIN)) ?: '1';

    return match ($mode) {
        '2' => 'Markdown',
        '3' => 'HTML',
        default => '',
    };
}

/**
 * Display broadcast results
 */
function displayResults(array $results): void
{
    echo "\n--- Broadcast Results ---\n";
    echo "Total messages: {$results['total']}\n";
    echo "Successful: {$results['successful']}\n";
    echo "Failed: {$results['failed']}\n";

    if ($results['failed'] > 0 && !empty($results['errors'])) {
        echo "\nErrors:\n";
        foreach (array_unique($results['errors']) as $error) {
            echo "  - $error\n";
        }
    }

    if ($results['failed'] > 0) {
        echo "\nFailed chat IDs:\n";
        foreach ($results['results'] as $result) {
            if (!$result['success']) {
                echo "  - Chat ID: {$result['chat_id']}, Error: {$result['error']}\n";
            }
        }
    }
}
