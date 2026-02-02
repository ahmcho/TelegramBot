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

require_once __DIR__ . '/../vendor/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Database\SqliteUserRepository;
use AhmCho\Telegram\Database\UserFilters;

// Load .env file
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

// Check for bot token
if (!getenv('TELEGRAM_BOT_TOKEN') && empty($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die("Error: TELEGRAM_BOT_TOKEN environment variable not set.\n" .
        "Please create a .env file with: TELEGRAM_BOT_TOKEN=your_token_here\n" .
        "Or set it with: export TELEGRAM_BOT_TOKEN='your_token_here'\n");
}

// Initialize
$bot = new TelegramBot();
$userRepo = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
$bot->setUserRepository($userRepo);

echo "Telegram Bot Database Broadcast Tool\n";
echo "====================================\n\n";

// Get current stats
$stats = $userRepo->getStats();
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
            broadcastCustom($bot, $userRepo);
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

    $result = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        null,
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($result);
}

/**
 * Broadcast to active users
 */
function broadcastActive(TelegramBot $bot, int $days): void
{
    echo "\n--- Broadcast to Active Users (Last $days Days) ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = UserFilters::create()
        ->withActiveSince(date('Y-m-d H:i:s', strtotime("-$days days")));

    $result = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        $filters,
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($result);
}

/**
 * Broadcast to premium users
 */
function broadcastPremium(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Premium Users ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = UserFilters::create()
        ->withIsPremium(true);

    $result = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        $filters,
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($result);
}

/**
 * Broadcast to users with username
 */
function broadcastWithUsername(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Users With Username ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = UserFilters::create()
        ->withHasUsername(true);

    $result = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        $filters,
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($result);
}

/**
 * Broadcast to active premium users
 */
function broadcastActivePremium(TelegramBot $bot): void
{
    echo "\n--- Broadcast to Active Premium Users (Last 30 Days) ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = UserFilters::create()
        ->withIsPremium(true)
        ->withActiveSince(date('Y-m-d H:i:s', strtotime('-30 days')));

    $result = $bot->broadcastToDatabase(
        $message,
        ['parse_mode' => $parseMode],
        $filters,
        ['max_concurrent' => 30, 'delay_ms' => 1000]
    );

    displayResults($result);
}

/**
 * Custom broadcast with user-defined filters
 */
function broadcastCustom(TelegramBot $bot, SqliteUserRepository $userRepo): void
{
    echo "\n--- Custom Broadcast ---\n";
    $message = getMessageFromUser();
    $parseMode = getParseMode();

    $filters = UserFilters::create();

    // Active since
    echo "Active since (days ago, or 0 to skip): ";
    $days = (int)trim(fgets(STDIN));
    if ($days > 0) {
        $filters = $filters->withActiveSince(date('Y-m-d H:i:s', strtotime("-$days days")));
    }

    // Premium only
    echo "Premium only? (y/n): ";
    $premium = strtolower(trim(fgets(STDIN)));
    if ($premium === 'y') {
        $filters = $filters->withIsPremium(true);
    }

    // Has username
    echo "Has username only? (y/n): ";
    $username = strtolower(trim(fgets(STDIN)));
    if ($username === 'y') {
        $filters = $filters->withHasUsername(true);
    }

    // Limit
    echo "Limit (0 for unlimited): ";
    $limit = (int)trim(fgets(STDIN));
    if ($limit > 0) {
        $filters = $filters->withLimit($limit);
    }

    // Show filter summary
    echo "\nFilter Summary:\n";
    if ($filters->activeSince !== null) {
        echo "  active_since: {$filters->activeSince}\n";
    }
    if ($filters->isPremium !== null) {
        echo "  is_premium: " . ($filters->isPremium ? 'true' : 'false') . "\n";
    }
    if ($filters->hasUsername !== null) {
        echo "  has_username: " . ($filters->hasUsername ? 'true' : 'false') . "\n";
    }
    if ($filters->limit !== null) {
        echo "  limit: {$filters->limit}\n";
    }
    if (
        $filters->activeSince === null && $filters->isPremium === null &&
        $filters->hasUsername === null && $filters->limit === null
    ) {
        echo "  No filters (all users)\n";
    }

    // Estimate recipients
    $chatIds = $userRepo->getAllChatIds($filters);
    echo "\nEstimated recipients: " . count($chatIds) . "\n";

    echo "\nSend broadcast? (y/n): ";
    $confirm = strtolower(trim(fgets(STDIN)));

    if ($confirm === 'y') {
        $result = $bot->broadcastToDatabase(
            $message,
            ['parse_mode' => $parseMode],
            $filters,
            ['max_concurrent' => 30, 'delay_ms' => 1000]
        );

        displayResults($result);
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
function displayResults($result): void
{
    // Handle both BulkResult (modern) and array (legacy/other)
    if (is_array($result)) {
        $total = $result['total'] ?? 0;
        $successful = $result['successful'] ?? 0;
        $failed = $result['failed'] ?? 0;
        $errors = $result['errors'] ?? [];
        $results = $result['results'] ?? [];
    } else {
        // BulkResult object
        $total = $result->total;
        $successful = $result->successful;
        $failed = $result->failed;
        $results = $result->results;
        $errors = array_filter(array_map(fn($r) => $r->error ?? null, $results));
    }

    echo "\n--- Broadcast Results ---\n";
    echo "Total messages: $total\n";
    echo "Successful: $successful\n";
    echo "Failed: $failed\n";

    if ($failed > 0 && !empty($errors)) {
        echo "\nErrors:\n";
        foreach (array_unique($errors) as $error) {
            echo "  - $error\n";
        }
    }

    if ($failed > 0) {
        echo "\nFailed chat IDs:\n";
        foreach ($results as $r) {
            $chatId = is_array($r) ? $r['chat_id'] : ($r->chatId ?? 'unknown');
            $success = is_array($r) ? $r['success'] : ($r->success ?? false);
            $error = is_array($r) ? $r['error'] : ($r->error ?? 'unknown');

            if (!$success) {
                echo "  - Chat ID: $chatId, Error: $error\n";
            }
        }
    }
}
