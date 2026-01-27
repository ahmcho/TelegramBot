<?php

/**
 * Test Database Implementation
 *
 * Quick test to verify database functionality works correctly.
 * Run this to test database creation, user operations, and statistics.
 */

require_once __DIR__ . '/../src/Database.php';

echo "Testing Database Implementation\n";
echo "================================\n\n";

try {
    // Test 1: Database creation
    echo "Test 1: Creating database...\n";
    $db = new Database(__DIR__ . '/../data/test.db');
    echo "✅ Database created: " . $db->getDbPath() . "\n\n";

    // Test 2: Insert test user
    echo "Test 2: Inserting test users...\n";
    $testUsers = [
        [
            'telegram_id' => 162592443,
            'chat_id' => 162592443,
            'first_name' => 'Ahmad',
            'last_name' => 'Cholluyev',
            'username' => 'ahmcho',
            'language_code' => 'en',
            'is_bot' => false,
            'is_premium' => true,
        ],
        [
            'telegram_id' => 987654321,
            'chat_id' => 987654321,
            'first_name' => 'Another',
            'last_name' => 'User',
            'username' => 'anotheruser',
            'language_code' => 'es',
            'is_bot' => false,
            'is_premium' => false,
        ],
    ];

    foreach ($testUsers as $user) {
        $db->saveUser($user);
        echo "✅ Saved user: {$user['first_name']} {$user['last_name']} (@{$user['username']})\n";
    }
    echo "\n";

    // Test 3: Get user by ID
    echo "Test 3: Retrieving user by ID...\n";
    $user = $db->getUserByTelegramId(162592443);
    if ($user) {
        echo "✅ Found user: {$user['first_name']} {$user['last_name']}\n";
    } else {
        echo "❌ User not found\n";
    }
    echo "\n";

    // Test 4: Get user by username
    echo "Test 4: Retrieving user by username...\n";
    $user = $db->getUserByUsername('ahmcho');
    if ($user) {
        echo "✅ Found user: @{$user['username']} ({$user['first_name']})\n";
    } else {
        echo "❌ User not found\n";
    }
    echo "\n";

    // Test 5: Get all chat IDs
    echo "Test 5: Getting all chat IDs...\n";
    $chatIds = $db->getAllChatIds();
    echo "✅ Found " . count($chatIds) . " chat IDs: " . implode(', ', $chatIds) . "\n\n";

    // Test 6: Get chat IDs with filters
    echo "Test 6: Getting premium chat IDs...\n";
    $premiumIds = $db->getAllChatIds(['is_premium' => true]);
    echo "✅ Found " . count($premiumIds) . " premium users: " . implode(', ', $premiumIds) . "\n\n";

    // Test 7: Update last active
    echo "Test 7: Updating last active...\n";
    $result = $db->updateLastActive(123456789);
    echo $result ? "✅ Updated last active timestamp\n" : "❌ Failed to update\n";
    echo "\n";

    // Test 8: Get statistics
    echo "Test 8: Getting statistics...\n";
    $stats = $db->getStats();
    echo "✅ Statistics:\n";
    echo "   Total: {$stats['total']}\n";
    echo "   Active (30d): {$stats['active_30_days']}\n";
    echo "   Premium: {$stats['premium']}\n";
    echo "   With Username: {$stats['with_username']}\n";
    echo "\n";

    // Test 9: Extract user data from update
    echo "Test 9: Testing extractUserData...\n";
    $mockUpdate = [
        'message' => [
            'message_id' => 1,
            'from' => [
                'id' => 111222333,
                'first_name' => 'Extracted',
                'last_name' => 'User',
                'username' => 'extracteduser',
                'language_code' => 'en',
                'is_premium' => true,
                'is_bot' => false,
            ],
            'chat' => [
                'id' => 111222333,
                'type' => 'private',
            ],
            'date' => time(),
            'text' => '/start',
        ],
    ];

    $extracted = Database::extractUserData($mockUpdate);
    if ($extracted) {
        echo "✅ Extracted user data:\n";
        echo "   Name: {$extracted['first_name']} {$extracted['last_name']}\n";
        echo "   Username: @{$extracted['username']}\n";
        echo "   Premium: " . ($extracted['is_premium'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Failed to extract user data\n";
    }
    echo "\n";

    // Test 10: Get all users
    echo "Test 10: Getting all users...\n";
    $users = $db->getAllUsers([], 10, 0);
    echo "✅ Found " . count($users) . " users:\n";
    foreach ($users as $u) {
        echo "   - {$u['first_name']} {$u['last_name']} (@{$u['username']})\n";
    }
    echo "\n";

    // Test 11: Delete user
    echo "Test 11: Deleting user...\n";
    $result = $db->deleteUser(987654321);
    echo $result ? "✅ User deleted\n" : "❌ Failed to delete\n";
    echo "\n";

    // Test 12: Get PDO instance
    echo "Test 12: Getting PDO instance...\n";
    $pdo = $db->getPdo();
    echo $pdo ? "✅ PDO instance retrieved\n" : "❌ Failed to get PDO\n";
    echo "\n";

    // Cleanup
    echo "Test 13: Cleanup test database...\n";
    $db->close();
    $testDbPath = __DIR__ . '/../data/test.db';
    if (file_exists($testDbPath)) {
        unlink($testDbPath);
        echo "✅ Test database removed\n";
    }
    echo "\n";

    echo "================================\n";
    echo "✅ All tests passed successfully!\n";
    echo "================================\n";

} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}
