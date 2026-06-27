<?php

/**
 * Webhook Setup Script
 *
 * Use this script to set up or delete a webhook for your bot.
 *
 * Usage:
 * php examples/setup-webhook.php set <webhook-url>
 * php examples/setup-webhook.php delete
 * php examples/setup-webhook.php info
 */

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

// Load environment variables (using the modern EnvLoader)
require_once __DIR__ . '/../src/Config/EnvLoader.php';

$loader = new \AhmCho\Telegram\Config\EnvLoader();
$loader->load();

// Show usage
function showUsage(): void
{
    echo "Telegram Bot Webhook Setup\n\n";
    echo "Usage:\n";
    echo "  php setup-webhook.php set <webhook-url> [secret-token]\n";
    echo "  php setup-webhook.php delete\n";
    echo "  php setup-webhook.php info\n\n";
    echo "Examples:\n";
    echo "  php setup-webhook.php set https://example.com/webhook.php\n";
    echo "  php setup-webhook.php set https://example.com/webhook.php my_secret_token\n";
    echo "  php setup-webhook.php delete\n";
    echo "  php setup-webhook.php info\n\n";
}

try {
    $bot = new TelegramBot();

    // Check command line arguments
    global $argc, $argv;
    if ($argc < 2) {
        showUsage();
        exit(1);
    }

    $command = strtolower($argv[1]);

    switch ($command) {
        case 'set':
            if ($argc < 3) {
                echo "Error: Webhook URL is required.\n\n";
                showUsage();
                exit(1);
            }

            $webhookUrl = $argv[2];
            $secretToken = $argv[3] ?? null;

            echo "Setting webhook...\n";
            echo "URL: $webhookUrl\n";
            if ($secretToken) {
                echo "Secret Token: $secretToken\n";
            }
            echo "\n";

            $params = [
                'url' => $webhookUrl,
                'drop_pending_updates' => true
            ];

            if ($secretToken) {
                $params['secret_token'] = $secretToken;
            }

            $result = $bot->webhooks()->set($params);

            if ($result) {
                echo "✅ Webhook set successfully!\n\n";

                // Verify webhook
                $info = $bot->webhooks()->getInfo();
                echo "Current webhook info:\n";
                echo "  URL: " . ($info['url'] ?: 'Not set') . "\n";
                echo "  Has custom certificate: " . ($info['has_custom_certificate'] ? 'Yes' : 'No') . "\n";
                echo "  Pending updates: " . $info['pending_update_count'] . "\n";

                if ($info['last_error_date']) {
                    echo "  Last error: " . $info['last_error_message'] . "\n";
                    echo "  Last error date: " . date('Y-m-d H:i:s', $info['last_error_date']) . "\n";
                }
            } else {
                echo "❌ Failed to set webhook.\n";
                exit(1);
            }

            break;

        case 'delete':
            echo "Deleting webhook...\n\n";

            $result = $bot->webhooks()->delete([
                'drop_pending_updates' => true
            ]);

            if ($result) {
                echo "✅ Webhook deleted successfully!\n";
                echo "Your bot is now back to long polling mode.\n";
            } else {
                echo "❌ Failed to delete webhook.\n";
                exit(1);
            }

            break;

        case 'info':
            echo "Getting webhook info...\n\n";

            $info = $bot->webhooks()->getInfo();

            echo "Webhook Information:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "  URL: " . ($info['url'] ?: 'Not set (using long polling)') . "\n";
            echo "  Has custom certificate: " . ($info['has_custom_certificate'] ? 'Yes' : 'No') . "\n";
            echo "  Pending updates: " . $info['pending_update_count'] . "\n";
            echo "  Max connections: " . ($info['max_connections'] ?: 'Default') . "\n";
            echo "  Allowed updates: " . (empty($info['allowed_updates']) ? 'All' : implode(', ', $info['allowed_updates'])) . "\n";

            if ($info['last_error_date']) {
                echo "\nLast Error:\n";
                echo "  Message: " . $info['last_error_message'] . "\n";
                echo "  Date: " . date('Y-m-d H:i:s', $info['last_error_date']) . "\n";
            }

            echo "\n";

            if (empty($info['url'])) {
                echo "ℹ️  No webhook is set. The bot is using long polling.\n";
            } else {
                echo "✅ Webhook is active.\n";
            }

            break;

        default:
            echo "Error: Unknown command '$command'.\n\n";
            showUsage();
            exit(1);
    }
} catch (\Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
