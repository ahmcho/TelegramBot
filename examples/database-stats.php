<?php

/**
 * Database Statistics Example - Analytics and Reporting
 *
 * This example demonstrates how to retrieve and display various
 * statistics and reports from the database.
 *
 * Usage:
 *   php database-stats.php
 *
 * Features:
 * - Overall database statistics
 * - User growth over time
 * - Active user breakdown
 * - Premium user statistics
 * - Search users by criteria
 * - Export user data
 */

// Load .env file if it exists
require_once __DIR__ . '/../src/dotenv.php';

require_once __DIR__ . '/../src/TelegramBot.php';
require_once __DIR__ . '/../src/Database.php';

// Initialize
$database = new Database(__DIR__ . '/../data/bot.db');

echo "Telegram Bot Database Statistics\n";
echo "=================================\n\n";

// Main menu
while (true) {
    echo "Available Reports:\n";
    echo "1. Overall Statistics\n";
    echo "2. User Growth (Daily)\n";
    echo "3. Active Users Breakdown\n";
    echo "4. Premium Users Report\n";
    echo "5. Search by Username\n";
    echo "6. Search by Telegram ID\n";
    echo "7. Top Active Users\n";
    echo "8. Recent Users\n";
    echo "9. Export User Data (CSV)\n";
    echo "0. Exit\n\n";

    echo "Select option (0-9): ";
    $option = trim(fgets(STDIN));

    switch ($option) {
        case '1':
            showOverallStats($database);
            break;
        case '2':
            showUserGrowth($database);
            break;
        case '3':
            showActiveUsers($database);
            break;
        case '4':
            showPremiumReport($database);
            break;
        case '5':
            searchByUsername($database);
            break;
        case '6':
            searchByTelegramId($database);
            break;
        case '7':
            showTopActiveUsers($database);
            break;
        case '8':
            showRecentUsers($database);
            break;
        case '9':
            exportUserData($database);
            break;
        case '0':
            echo "Exiting...\n";
            exit(0);
        default:
            echo "Invalid option. Please select 0-9.\n";
    }

    echo "\nPress Enter to continue...";
    fgets(STDIN);
    echo "\n";
}

/**
 * Show overall statistics
 */
function showOverallStats(Database $database): void
{
    echo "\n=== OVERALL STATISTICS ===\n\n";

    $stats = $database->getStats();

    echo "📊 User Counts:\n";
    echo "  Total Users: {$stats['total']}\n";
    echo "  Active (30d): {$stats['active_30_days']}\n";
    echo "  Inactive (30d): " . ($stats['total'] - $stats['active_30_days']) . "\n";
    echo "  Premium Users: {$stats['premium']} (" . round($stats['premium'] / max($stats['total'], 1) * 100, 1) . "%)\n";
    echo "  With Username: {$stats['with_username']} (" . round($stats['with_username'] / max($stats['total'], 1) * 100, 1) . "%)\n";
    echo "  Bots: {$stats['bots']}\n";
    echo "  New Today: {$stats['new_today']}\n";

    // Get additional stats using raw PDO
    $pdo = $database->getPdo();

    // Users by language
    $stmt = $pdo->query("SELECT language_code, COUNT(*) as count FROM users WHERE language_code IS NOT NULL GROUP BY language_code ORDER BY count DESC LIMIT 10");
    $languages = $stmt->fetchAll();

    echo "\n🌍 Top Languages:\n";
    foreach ($languages as $lang) {
        echo "  {$lang['language_code']}: {$lang['count']}\n";
    }
}

/**
 * Show user growth over time
 */
function showUserGrowth(Database $database): void
{
    echo "\n=== USER GROWTH (LAST 30 DAYS) ===\n\n";

    $pdo = $database->getPdo();

    $sql = "SELECT
        date(created_at) as date,
        COUNT(*) as new_users
    FROM users
    WHERE created_at >= date('now', '-30 days')
    GROUP BY date(created_at)
    ORDER BY date DESC";

    $stmt = $pdo->query($sql);
    $growth = $stmt->fetchAll();

    if (empty($growth)) {
        echo "No data available for the last 30 days.\n";
        return;
    }

    echo "Date        | New Users\n";
    echo "------------|----------\n";

    foreach ($growth as $day) {
        $bar = str_repeat('█', min($day['new_users'], 50));
        printf("%s | %d %s\n", $day['date'], $day['new_users'], $bar);
    }

    // Total new users in period
    $total = array_sum(array_column($growth, 'new_users'));
    $avg = round($total / count($growth), 1);

    echo "\nTotal new users (30d): $total\n";
    echo "Average per day: $avg\n";
}

/**
 * Show active users breakdown
 */
function showActiveUsers(Database $database): void
{
    echo "\n=== ACTIVE USERS BREAKDOWN ===\n\n";

    $pdo = $database->getPdo();

    $periods = [
        '1 day' => 1,
        '7 days' => 7,
        '30 days' => 30,
        '90 days' => 90,
    ];

    echo "Period    | Active Users | % of Total\n";
    echo "----------|--------------|-----------\n";

    $totalUsers = $database->getStats()['total'];

    foreach ($periods as $label => $days) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE last_active >= datetime('now', '-$days days')";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        $percentage = round($result['count'] / max($totalUsers, 1) * 100, 1);

        printf("%-9s | %-12d | %s%%\n", $label, $result['count'], $percentage);
    }
}

/**
 * Show premium users report
 */
function showPremiumReport(Database $database): void
{
    echo "\n=== PREMIUM USERS REPORT ===\n\n";

    $stats = $database->getStats();

    echo "Total Premium Users: {$stats['premium']}\n";
    echo "Total Non-Premium: " . ($stats['total'] - $stats['premium'] - $stats['bots']) . "\n";
    echo "Premium Rate: " . round($stats['premium'] / max($stats['total'] - $stats['bots'], 1) * 100, 1) . "%\n\n";

    // Get premium users with pagination
    echo "Listing premium users (first 20):\n\n";

    $users = $database->getAllUsers(['is_premium' => true], 20, 0);

    if (empty($users)) {
        echo "No premium users found.\n";
        return;
    }

    foreach ($users as $user) {
        $name = trim($user['first_name'] . ' ' . $user['last_name']);
        $username = $user['username'] ? "@{$user['username']}" : '';
        printf("  ID: %d | %s %s | Active: %s\n",
            $user['telegram_id'],
            $name,
            $username,
            $user['last_active']
        );
    }
}

/**
 * Search user by username
 */
function searchByUsername(Database $database): void
{
    echo "\n=== SEARCH BY USERNAME ===\n\n";
    echo "Enter username (with or without @): ";
    $username = trim(fgets(STDIN));

    if (empty($username)) {
        echo "Username cannot be empty.\n";
        return;
    }

    $user = $database->getUserByUsername($username);

    if ($user === null) {
        echo "User not found.\n";
        return;
    }

    displayUserDetails($user);
}

/**
 * Search user by Telegram ID
 */
function searchByTelegramId(Database $database): void
{
    echo "\n=== SEARCH BY TELEGRAM ID ===\n\n";
    echo "Enter Telegram ID: ";
    $telegramId = (int)trim(fgets(STDIN));

    if ($telegramId <= 0) {
        echo "Invalid Telegram ID.\n";
        return;
    }

    $user = $database->getUserByTelegramId($telegramId);

    if ($user === null) {
        echo "User not found.\n";
        return;
    }

    displayUserDetails($user);
}

/**
 * Show top active users
 */
function showTopActiveUsers(Database $database): void
{
    echo "\n=== TOP ACTIVE USERS ===\n\n";
    echo "How many to show? [20]: ";
    $limit = (int)trim(fgets(STDIN)) ?: 20;

    $pdo = $database->getPdo();

    $sql = "SELECT * FROM users WHERE is_bot = 0 ORDER BY last_active DESC LIMIT " . (int)$limit;
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "No users found.\n";
        return;
    }

    foreach ($users as $index => $user) {
        $name = trim($user['first_name'] . ' ' . $user['last_name']);
        $username = $user['username'] ? "@{$user['username']}" : '';
        $premium = $user['is_premium'] ? ' ⭐' : '';

        printf("%2d. %s %s | Last active: %s%s\n",
            $index + 1,
            $name,
            $username,
            $user['last_active'],
            $premium
        );
    }
}

/**
 * Show recent users
 */
function showRecentUsers(Database $database): void
{
    echo "\n=== RECENT USERS ===\n\n";
    echo "How many to show? [20]: ";
    $limit = (int)trim(fgets(STDIN)) ?: 20;

    $pdo = $database->getPdo();

    $sql = "SELECT * FROM users WHERE is_bot = 0 ORDER BY created_at DESC LIMIT " . (int)$limit;
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "No users found.\n";
        return;
    }

    foreach ($users as $index => $user) {
        $name = trim($user['first_name'] . ' ' . $user['last_name']);
        $username = $user['username'] ? "@{$user['username']}" : '';

        printf("%2d. %s %s | Joined: %s\n",
            $index + 1,
            $name,
            $username,
            $user['created_at']
        );
    }
}

/**
 * Export user data to CSV
 */
function exportUserData(Database $database): void
{
    echo "\n=== EXPORT USER DATA ===\n\n";

    $pdo = $database->getPdo();

    echo "Filters:\n";
    echo "Premium only? (y/n) [n]: ";
    $premium = strtolower(trim(fgets(STDIN))) === 'y';

    echo "Has username? (y/n) [n]: ";
    $hasUsername = strtolower(trim(fgets(STDIN))) === 'y';

    echo "Active since (days ago, or 0 for all) [0]: ";
    $days = (int)trim(fgets(STDIN));

    // Build query
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];

    if ($premium) {
        $sql .= " AND is_premium = 1";
    }

    if ($hasUsername) {
        $sql .= " AND username IS NOT NULL AND username != ''";
    }

    if ($days > 0) {
        $sql .= " AND last_active >= datetime('now', '-$days days')";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    echo "\nFound " . count($users) . " users.\n";
    echo "Export to filename [users_export.csv]: ";
    $filename = trim(fgets(STDIN)) ?: 'users_export.csv';

    $file = fopen($filename, 'w');

    // CSV header
    fputcsv($file, [
        'telegram_id', 'chat_id', 'first_name', 'last_name',
        'username', 'language_code', 'is_bot', 'is_premium',
        'created_at', 'updated_at', 'last_active'
    ]);

    // CSV data
    foreach ($users as $user) {
        fputcsv($file, [
            $user['telegram_id'],
            $user['chat_id'],
            $user['first_name'],
            $user['last_name'],
            $user['username'],
            $user['language_code'],
            $user['is_bot'],
            $user['is_premium'],
            $user['created_at'],
            $user['updated_at'],
            $user['last_active']
        ]);
    }

    fclose($file);

    echo "✅ Exported to: $filename\n";
}

/**
 * Display detailed user information
 */
function displayUserDetails(array $user): void
{
    echo "\n--- User Details ---\n";
    echo "Telegram ID: {$user['telegram_id']}\n";
    echo "Chat ID: {$user['chat_id']}\n";
    echo "Name: {$user['first_name']} {$user['last_name']}\n";
    echo "Username: " . ($user['username'] ? "@{$user['username']}" : 'none') . "\n";
    echo "Language: " . ($user['language_code'] ?: 'unknown') . "\n";
    echo "Premium: " . ($user['is_premium'] ? 'Yes ⭐' : 'No') . "\n";
    echo "Bot: " . ($user['is_bot'] ? 'Yes 🤖' : 'No') . "\n";
    echo "Registered: {$user['created_at']}\n";
    echo "Updated: {$user['updated_at']}\n";
    echo "Last Active: {$user['last_active']}\n";
}
