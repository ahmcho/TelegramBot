<?php

declare(strict_types=1);

/**
 * Command Handler Demo
 *
 * This example demonstrates the built-in command handler system
 * that makes it easy to register and handle bot commands.
 *
 * Features demonstrated:
 * - Simple command registration
 * - Command with arguments
 * - Middleware
 * - Default handler
 * - Help generation
 *
 * Usage:
 * php examples/commands-demo.php
 */

use AhmCho\Telegram\Bot\TelegramBot;

require_once __DIR__ . '/../autoload.php';

// Load environment variables
require_once __DIR__ . '/../src/Config/EnvLoader.php';

$loader = new \AhmCho\Telegram\Config\EnvLoader();
$loader->load();

try {
    $bot = new TelegramBot();

    echo "=== Command Handler Demo ===\n\n";

    // Register commands using the command handler
    $bot->commands()
        ->register('start', function ($bot, $chatId, $args) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '👋 Welcome! Use /help to see available commands.',
                'parse_mode' => 'MarkdownV2'
            ]);
        }, 'Start the bot and see this message')

        ->register('help', function ($bot, $chatId, $args) {
            $bot->commands()->sendHelp($chatId);
        }, 'Show this help message')

        ->register('ping', function ($bot, $chatId, $args) {
            $start = microtime(true);
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '🏓 Pong! Took ' . number_format((microtime(true) - $start) * 1000, 2) . 'ms'
            ]);
        }, 'Check bot response time')

        ->register('echo', function ($bot, $chatId, $args) {
            $text = implode(' ', $args);
            if (empty($text)) {
                $bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => 'Usage: /echo <text to echo>'
                ]);
                return;
            }
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "🔊 You said: $text",
                'parse_mode' => 'MarkdownV2'
            ]);
        }, 'Echo back your message')

        ->register('info', function ($bot, $chatId, $args) {
            $me = $bot->api()->call(\AhmCho\Telegram\Enums\ApiMethod::GET_ME);
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "ℹ️ *Bot Information*\n\n"
                    . "Name: {$me['first_name']}\n"
                    . "Username: @{$me['username']}\n"
                    . "Can join groups: " . ($me['can_join_groups'] ? 'Yes' : 'No') . "\n"
                    . "Can read all group messages: " . ($me['can_read_all_group_messages'] ? 'Yes' : 'No'),
                'parse_mode' => 'MarkdownV2'
            ]);
        }, 'Get bot information')

        ->register('time', function ($bot, $chatId, $args) {
            $timezone = $args[0] ?? 'UTC';
            try {
                $date = new \DateTime('now', new \DateTimeZone($timezone));
                $bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => "🕐 Current time in $timezone:\n" . $date->format('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                $bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Invalid timezone: $timezone"
                ]);
            }
        }, 'Get current time (usage: /time [timezone])')

        // Add middleware for logging
        ->addMiddleware('logging', function ($bot, $chatId, $command, $args) {
            echo "[$chatId] Command: /$command, Args: " . json_encode($args) . "\n";
            return true; // Continue to next middleware/command
        })

        // Set default handler for unknown commands
        ->setDefault(function ($bot, $chatId, $command, $args) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "❓ Unknown command: /$command\n\nType /help to see available commands."
            ]);
        });

    echo "Registered commands:\n";
    foreach ($bot->commands()->getRegisteredCommands() as $cmd) {
        echo "  /$cmd\n";
    }
    echo "\n";

    // Main loop
    echo "Bot started. Send commands to your bot to test.\n";
    echo "Press Ctrl+C to stop\n\n";

    $offset = 0;

    while (true) {
        try {
            $updates = $bot->getUpdates([
                'offset' => $offset,
                'timeout' => 30
            ]);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                // Let the command handler process the update
                $handled = $bot->commands()->handleUpdate($update);

                if (!$handled && isset($update['message'])) {
                    // Not a command, handle regular messages
                    $chatId = $update['message']['chat']['id'];
                    $text = $update['message']['text'] ?? '';

                    if (!empty($text)) {
                        echo "[{$chatId}] Message: $text\n";
                        $bot->messages()->send([
                            'chat_id' => $chatId,
                            'text' => 'Send /help to see available commands.'
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "Error: {$e->getMessage()}\n";
            sleep(5);
        }
    }

} catch (\Throwable $e) {
    echo "Fatal error: {$e->getMessage()}\n";
    exit(1);
}
