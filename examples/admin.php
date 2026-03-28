<?php

declare(strict_types=1);

/**
 * Admin Bot Example - Modern API
 *
 * This example demonstrates group administration features
 * including ban/unban, restrict, promote, and group statistics.
 *
 * NOTE: Add this bot to a group as an administrator to test these features.
 *
 * Modern features showcased:
 * - Service-oriented API ($bot->chats(), $bot->messages())
 * - Auto-escaping for MarkdownV2 with special characters
 * - PHP 8.1+ features (strict types, proper typing)
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

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

// Command handlers
function handleStart(TelegramBot $bot, int $chatId): void
{
    $keyboard = InlineKeyboardBuilder::create()
        ->addRow(
            Button::callback('📊 Group Stats', 'admin:stats'),
            Button::callback('👥 Admin List', 'admin:admins')
        )
        ->addRow(
            Button::callback('ℹ️ Chat Info', 'admin:info'),
            Button::callback('🔧 Member Count', 'admin:count')
        )
        ->addRow(
            Button::callback('❓ Help', 'admin:help')
        );

    // Using formatter - auto-escaped!
    $text = $bot->formatter()
        ->bold('👮 Admin Bot')
        . "\n\n"
        . 'I provide group administration features.'
        . "\n\n"
        . "Available commands:\n"
        . "/ban - Ban a user\n"
        . "/kick - Kick a user\n"
        . "/unban - Unban a user\n"
        . "/restrict - Restrict user permissions\n"
        . "/promote - Promote to admin\n"
        . "/demote - Demote from admin\n"
        . "/mute - Mute user for time\n"
        . "/info - Get user info\n"
        . "/stats - Group statistics\n\n"
        . 'Or use the buttons below:';

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'MarkdownV2',
        'reply_markup' => $keyboard->build()
    ]);
}

function handleStats(TelegramBot $bot, int $chatId): void
{
    try {
        $memberCount = $bot->chats()->getMemberCount(['chat_id' => $chatId]);
        $chat = $bot->chats()->getChat(['chat_id' => $chatId]);

        $stats = $bot->formatter()->bold('📊 Group Statistics') . "\n\n";

        if (isset($chat['title'])) {
            $stats .= '📛 Name: ' . $chat['title'] . "\n";
        }

        if (isset($chat['username'])) {
            $stats .= '🔗 Username: @' . $chat['username'] . "\n";
        }

        if (isset($chat['type'])) {
            $stats .= '📁 Type: ' . ucfirst($chat['type']) . "\n";
        }

        $stats .= '👥 Members: ' . $memberCount . "\n";

        if (isset($chat['description'])) {
            $stats .= "\n📝 Description:\n" . $chat['description'] . "\n";
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $stats,
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error getting stats: ' . $e->getMessage()
        ]);
    }
}

function handleAdminList(TelegramBot $bot, int $chatId): void
{
    try {
        $admins = $bot->chats()->getAdministrators(['chat_id' => $chatId]);

        $text = $bot->formatter()->bold('👥 Administrators') . "\n\n";

        foreach ($admins as $admin) {
            $user = $admin['user'];
            $name = $user['first_name'] ?? 'Unknown';
            if (isset($user['last_name'])) {
                $name .= ' ' . $user['last_name'];
            }
            if (isset($user['username'])) {
                $name .= ' (@' . $user['username'] . ')';
            }

            $status = $admin['status'];
            $statusEmoji = [
                'creator' => '👑',
                'administrator' => '👮'
            ];

            $text .= ($statusEmoji[$status] ?? '👤') . ' ' . $name . "\n";

            if ($status === 'administrator' && isset($admin['can_be_edited'])) {
                $text .= '  └ Can be edited: ' . ($admin['can_be_edited'] ? '✅' : '❌') . "\n";
            }
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error getting admin list: ' . $e->getMessage()
        ]);
    }
}

function handleChatInfo(TelegramBot $bot, int $chatId): void
{
    try {
        $chat = $bot->chats()->getChat(['chat_id' => $chatId]);

        $info = $bot->formatter()->bold('ℹ️ Chat Information') . "\n\n";
        $info .= '🆔 ID: `' . $chat['id'] . "`\n";
        $info .= '📁 Type: ' . ucfirst($chat['type']) . "\n";

        if (isset($chat['title'])) {
            $info .= '📛 Title: ' . $chat['title'] . "\n";
        }

        if (isset($chat['username'])) {
            $info .= '🔗 Username: @' . $chat['username'] . "\n";
        }

        if (isset($chat['full_name'])) {
            $info .= '👤 Name: ' . $chat['full_name'] . "\n";
        }

        if (isset($chat['description'])) {
            $info .= "\n📝 *Description:*\n" . $chat['description'] . "\n";
        }

        if (isset($chat['invite_link'])) {
            $info .= "\n🔗 *Invite Link:* " . $chat['invite_link'] . "\n";
        }

        if (isset($chat['pinned_message'])) {
            $pinned = $chat['pinned_message'];
            $pinnedText = $pinned['text'] ?? '[Media or other content]';
            $info .= "\n📌 *Pinned Message:*\n" . $pinnedText . "\n";
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $info,
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error getting chat info: ' . $e->getMessage()
        ]);
    }
}

function handleMemberCount(TelegramBot $bot, int $chatId): void
{
    try {
        $count = $bot->chats()->getMemberCount(['chat_id' => $chatId]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $bot->formatter()
                ->bold('👥 Member Count')
                . "\n\n"
                . 'This chat has '
                . $bot->formatter()->bold((string)$count)
                . ' members.',
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error getting member count: ' . $e->getMessage()
        ]);
    }
}

function handleUserInfo(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $member = $bot->chats()->getMember([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);

        $user = $member['user'];
        $name = $user['first_name'] ?? 'Unknown';
        if (isset($user['last_name'])) {
            $name .= ' ' . $user['last_name'];
        }

        $info = $bot->formatter()->bold('👤 User Information') . "\n\n";
        $info .= '🆔 ID: `' . $user['id'] . "`\n";
        $info .= '👤 Name: ' . $name . "\n";

        if (isset($user['username'])) {
            $info .= '🔗 Username: @' . $user['username'] . "\n";
        }

        if (isset($user['language_code'])) {
            $info .= '🌐 Language: ' . $user['language_code'] . "\n";
        }

        $info .= "\n📋 Status: " . ucfirst($member['status']) . "\n";

        if ($member['status'] === 'administrator' || $member['status'] === 'creator') {
            if (isset($member['can_be_edited'])) {
                $info .= '✏️ Can be edited: ' . ($member['can_be_edited'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_change_info'])) {
                $info .= 'ℹ️ Can change info: ' . ($member['can_change_info'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_delete_messages'])) {
                $info .= '🗑️ Can delete messages: ' . ($member['can_delete_messages'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_invite_users'])) {
                $info .= '➕ Can invite users: ' . ($member['can_invite_users'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_restrict_members'])) {
                $info .= '⛔ Can restrict members: ' . ($member['can_restrict_members'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_pin_messages'])) {
                $info .= '📌 Can pin messages: ' . ($member['can_pin_messages'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_promote_members'])) {
                $info .= '⬆️ Can promote members: ' . ($member['can_promote_members'] ? 'Yes' : 'No') . "\n";
            }
        }

        if (isset($member['until_date'])) {
            $info .= "\n⏰ Restricted until: " . date('Y-m-d H:i:s', $member['until_date']) . "\n";
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $info,
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error getting user info: ' . $e->getMessage()
        ]);
    }
}

function handleBan(TelegramBot $bot, int $chatId, int $userId, ?int $untilDate = null): void
{
    try {
        $params = [
            'chat_id' => $chatId,
            'user_id' => $userId
        ];

        if ($untilDate !== null) {
            $params['until_date'] = $untilDate;
        }

        $result = $bot->chats()->banMember($params);

        $text = '⛔ User banned successfully!';
        if ($untilDate !== null) {
            $text .= "\n\n⏰ Until: " . date('Y-m-d H:i:s', $untilDate);
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error banning user: ' . $e->getMessage()
        ]);
    }
}

function handleUnban(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $bot->chats()->unbanMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'only_if_banned' => true
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '✅ User unbanned successfully!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error unbanning user: ' . $e->getMessage()
        ]);
    }
}

function handleRestrict(TelegramBot $bot, int $chatId, int $userId, array $permissions): void
{
    try {
        $bot->chats()->restrictMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'permissions' => $permissions
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '⛔ User restricted successfully!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error restricting user: ' . $e->getMessage()
        ]);
    }
}

function handlePromote(TelegramBot $bot, int $chatId, int $userId, array $rights): void
{
    try {
        $bot->chats()->promoteMember(array_merge([
            'chat_id' => $chatId,
            'user_id' => $userId
        ], $rights));

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '⬆️ User promoted to administrator!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error promoting user: ' . $e->getMessage()
        ]);
    }
}

function handleKick(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $bot->chats()->banMember([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '👢 User kicked from the chat!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error kicking user: ' . $e->getMessage()
        ]);
    }
}

function handleMute(TelegramBot $bot, int $chatId, int $userId, int $duration): void
{
    try {
        $untilDate = time() + $duration;

        // Restrict all permissions
        $permissions = [
            'can_send_messages' => false,
            'can_send_media_messages' => false,
            'can_send_polls' => false,
            'can_send_other_messages' => false,
            'can_add_web_page_previews' => false,
            'can_change_info' => false,
            'can_invite_users' => false,
            'can_pin_messages' => false
        ];

        $bot->chats()->restrictMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'permissions' => $permissions,
            'until_date' => $untilDate
        ]);

        $durationText = $duration >= 3600
            ? round($duration / 3600) . ' hours'
            : round($duration / 60) . ' minutes';

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '🔇 User muted for ' . $durationText . '!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error muting user: ' . $e->getMessage()
        ]);
    }
}

function handlePinMessage(TelegramBot $bot, int $chatId, int $messageId, bool $disableNotification = false): void
{
    try {
        $bot->chats()->pinMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'disable_notification' => $disableNotification
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '📌 Message pinned successfully!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error pinning message: ' . $e->getMessage()
        ]);
    }
}

function handleUnpinMessage(TelegramBot $bot, int $chatId, ?int $messageId = null): void
{
    try {
        if ($messageId !== null) {
            $bot->chats()->unpinMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId
            ]);
            $text = '📍 Message unpinned successfully!';
        } else {
            $bot->chats()->unpinAllMessages(['chat_id' => $chatId]);
            $text = '📍 All messages unpinned successfully!';
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error unpinning: ' . $e->getMessage()
        ]);
    }
}

function handleSetChatTitle(TelegramBot $bot, int $chatId, string $title): void
{
    try {
        $bot->chats()->setTitle([
            'chat_id' => $chatId,
            'title' => $title
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '✅ Chat title changed to: ' . $title
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error changing title: ' . $e->getMessage()
        ]);
    }
}

function handleSetChatDescription(TelegramBot $bot, int $chatId, string $description): void
{
    try {
        $bot->chats()->setDescription([
            'chat_id' => $chatId,
            'description' => $description
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '✅ Chat description updated!'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error updating description: ' . $e->getMessage()
        ]);
    }
}

// Main bot loop
try {
    $bot = new TelegramBot();

    echo "Admin Bot started...\n";
    echo "Add this bot to a group as an administrator to test features.\n";
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

                // Handle callback queries
                if (isset($update['callback_query'])) {
                    $callbackQuery = $update['callback_query'];
                    $chatId = $callbackQuery['message']['chat']['id'];
                    $messageId = $callbackQuery['message']['message_id'];
                    $data = $callbackQuery['data'];
                    $queryId = $callbackQuery['id'];

                    $bot->api()->call(
                        ApiMethod::ANSWER_CALLBACK_QUERY,
                        ['callback_query_id' => $queryId]
                    );

                    $parts = explode(':', $data);
                    $action = $parts[1] ?? '';

                    switch ($action) {
                        case 'stats':
                            handleStats($bot, $chatId);
                            break;
                        case 'admins':
                            handleAdminList($bot, $chatId);
                            break;
                        case 'info':
                            handleChatInfo($bot, $chatId);
                            break;
                        case 'count':
                            handleMemberCount($bot, $chatId);
                            break;
                        case 'help':
                            $bot->messages()->editText([
                                'chat_id' => $chatId,
                                'message_id' => $messageId,
                                'text' => '❓ *Admin Commands Help*' . "\n\n"
                                    . '/ban @user \[time\] - Ban user \(optional: time in seconds\)' . "\n"
                                    . '/unban @user - Unban user' . "\n"
                                    . '/kick @user - Kick user' . "\n"
                                    . '/mute @user \[minutes\] - Mute user \(default: 60 minutes\)' . "\n"
                                    . '/info @user - Get user info' . "\n"
                                    . '/promote @user - Promote to admin' . "\n"
                                    . '/demote @user - Demote from admin' . "\n"
                                    . '/pin - Pin the replied message' . "\n"
                                    . '/unpin - Unpin the replied message' . "\n"
                                    . '/unpinall - Unpin all messages' . "\n"
                                    . '/title <text> - Change chat title' . "\n"
                                    . '/desc <text> - Change chat description' . "\n"
                                    . '/stats - Show group statistics' . "\n"
                                    . '/admins - List administrators',
                                'parse_mode' => 'MarkdownV2'
                            ]);
                            break;
                    }

                    continue;
                }

                // Handle messages
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';
                    $replyToMessage = $message['reply_to_message'] ?? null;

                    // Check for commands
                    if (strpos($text, '/') === 0) {
                        $parts = explode(' ', $text);
                        $command = $parts[0];
                        $args = array_slice($parts, 1);

                        switch ($command) {
                            case '/start':
                                handleStart($bot, $chatId);
                                break;

                            case '/stats':
                                handleStats($bot, $chatId);
                                break;

                            case '/admins':
                                handleAdminList($bot, $chatId);
                                break;

                            case '/info':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handleUserInfo($bot, $chatId, $userId);
                                } elseif (!empty($args[0]) && strpos($args[0], '@') === 0) {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to get their info.'
                                    ]);
                                } else {
                                    handleChatInfo($bot, $chatId);
                                }
                                break;

                            case '/ban':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    $duration = isset($args[0]) ? (int)$args[0] : null;
                                    if ($duration && $duration > 0) {
                                        handleBan($bot, $chatId, $userId, time() + $duration);
                                    } else {
                                        handleBan($bot, $chatId, $userId);
                                    }
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to ban them.'
                                    ]);
                                }
                                break;

                            case '/unban':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handleUnban($bot, $chatId, $userId);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to unban them.'
                                    ]);
                                }
                                break;

                            case '/kick':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handleKick($bot, $chatId, $userId);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to kick them.'
                                    ]);
                                }
                                break;

                            case '/mute':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    $duration = isset($args[0]) ? (int)$args[0] * 60 : 3600; // Default 1 hour
                                    handleMute($bot, $chatId, $userId, $duration);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to mute them.'
                                    ]);
                                }
                                break;

                            case '/promote':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handlePromote($bot, $chatId, $userId, [
                                        'can_change_info' => true,
                                        'can_delete_messages' => true,
                                        'can_invite_users' => true,
                                        'can_restrict_members' => true,
                                        'can_pin_messages' => true,
                                        'can_promote_members' => false
                                    ]);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to promote them.'
                                    ]);
                                }
                                break;

                            case '/demote':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handlePromote($bot, $chatId, $userId, [
                                        'can_change_info' => false,
                                        'can_delete_messages' => false,
                                        'can_invite_users' => false,
                                        'can_restrict_members' => false,
                                        'can_pin_messages' => false
                                    ]);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a user\'s message to demote them.'
                                    ]);
                                }
                                break;

                            case '/pin':
                                if ($replyToMessage !== null) {
                                    $messageId = $replyToMessage['message_id'];
                                    handlePinMessage($bot, $chatId, $messageId, false);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a message to pin it.'
                                    ]);
                                }
                                break;

                            case '/unpin':
                                if ($replyToMessage !== null) {
                                    $messageId = $replyToMessage['message_id'];
                                    handleUnpinMessage($bot, $chatId, $messageId);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please reply to a message to unpin it, or use /unpinall to unpin all.'
                                    ]);
                                }
                                break;

                            case '/unpinall':
                                handleUnpinMessage($bot, $chatId);
                                break;

                            case '/title':
                                $title = implode(' ', $args);
                                if (!empty($title)) {
                                    handleSetChatTitle($bot, $chatId, $title);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please provide a title. Usage: /title <new title>'
                                    ]);
                                }
                                break;

                            case '/desc':
                                $description = implode(' ', $args);
                                if (!empty($description)) {
                                    handleSetChatDescription($bot, $chatId, $description);
                                } else {
                                    $bot->messages()->send([
                                        'chat_id' => $chatId,
                                        'text' => '⚠️ Please provide a description. Usage: /desc <new description>'
                                    ]);
                                }
                                break;

                            default:
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => 'Unknown command: ' . $command . "\nType /help to see available commands."
                                ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            sleep(5);
        }
    }
} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
