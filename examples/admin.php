<?php
/**
 * Admin Bot Example
 *
 * This example demonstrates group administration features
 * including ban/unban, restrict, promote, and group statistics.
 *
 * NOTE: Add this bot to a group as an administrator to test these features.
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

// Command handlers
function handleStart(TelegramBot $bot, int $chatId): void
{
    $keyboard = $bot->buildInlineKeyboard([
        [
            $bot->createCallbackButton('📊 Group Stats', 'admin:stats'),
            $bot->createCallbackButton('👥 Admin List', 'admin:admins')
        ],
        [
            $bot->createCallbackButton('ℹ️ Chat Info', 'admin:info'),
            $bot->createCallbackButton('🔧 Member Count', 'admin:count')
        ],
        [
            $bot->createCallbackButton('❓ Help', 'admin:help')
        ]
    ]);

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "👮 *Admin Bot*\n\n"
            . "I provide group administration features.\n\n"
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
            . "Or use the buttons below:",
        'parse_mode' => 'Markdown',
        'reply_markup' => $keyboard
    ]);
}

function handleStats(TelegramBot $bot, int $chatId): void
{
    try {
        $memberCount = $bot->getChatMemberCount(['chat_id' => $chatId]);
        $chat = $bot->getChat(['chat_id' => $chatId]);

        $stats = "📊 *Group Statistics*\n\n";

        if (isset($chat['title'])) {
            $stats .= "📛 Name: {$chat['title']}\n";
        }

        if (isset($chat['username'])) {
            $stats .= "🔗 Username: @{$chat['username']}\n";
        }

        if (isset($chat['type'])) {
            $stats .= "📁 Type: " . ucfirst($chat['type']) . "\n";
        }

        $stats .= "👥 Members: {$memberCount}\n";

        if (isset($chat['description'])) {
            $stats .= "\n📝 Description:\n{$chat['description']}\n";
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $stats,
            'parse_mode' => 'Markdown'
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error getting stats: " . $e->getMessage()
        ]);
    }
}

function handleAdminList(TelegramBot $bot, int $chatId): void
{
    try {
        $admins = $bot->getChatAdministrators(['chat_id' => $chatId]);

        $text = "👥 *Administrators*\n\n";

        foreach ($admins as $admin) {
            $user = $admin['user'];
            $name = $user['first_name'] ?? 'Unknown';
            if (isset($user['last_name'])) {
                $name .= ' ' . $user['last_name'];
            }
            if (isset($user['username'])) {
                $name .= " (@{$user['username']})";
            }

            $status = $admin['status'];
            $statusEmoji = [
                'creator' => '👑',
                'administrator' => '👮'
            ];

            $text .= ($statusEmoji[$status] ?? '👤') . " {$name}\n";

            if ($status === 'administrator' && isset($admin['can_be_edited'])) {
                $text .= "  └ Can be edited: " . ($admin['can_be_edited'] ? '✅' : '❌') . "\n";
            }
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error getting admin list: " . $e->getMessage()
        ]);
    }
}

function handleChatInfo(TelegramBot $bot, int $chatId): void
{
    try {
        $chat = $bot->getChat(['chat_id' => $chatId]);

        $info = "ℹ️ *Chat Information*\n\n";
        $info .= "🆔 ID: `{$chat['id']}`\n";
        $info .= "📁 Type: " . ucfirst($chat['type']) . "\n";

        if (isset($chat['title'])) {
            $info .= "📛 Title: {$chat['title']}\n";
        }

        if (isset($chat['username'])) {
            $info .= "🔗 Username: @{$chat['username']}\n";
        }

        if (isset($chat['full_name'])) {
            $info .= "👤 Name: {$chat['full_name']}\n";
        }

        if (isset($chat['description'])) {
            $info .= "\n📝 *Description:*\n{$chat['description']}\n";
        }

        if (isset($chat['invite_link'])) {
            $info .= "\n🔗 *Invite Link:* {$chat['invite_link']}\n";
        }

        if (isset($chat['pinned_message'])) {
            $pinned = $chat['pinned_message'];
            $pinnedText = $pinned['text'] ?? '[Media or other content]';
            $info .= "\n📌 *Pinned Message:*\n{$pinnedText}\n";
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $info,
            'parse_mode' => 'Markdown'
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error getting chat info: " . $e->getMessage()
        ]);
    }
}

function handleMemberCount(TelegramBot $bot, int $chatId): void
{
    try {
        $count = $bot->getChatMemberCount(['chat_id' => $chatId]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "👥 *Member Count*\n\n"
                . "This chat has *{$count}* members.",
            'parse_mode' => 'Markdown'
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error getting member count: " . $e->getMessage()
        ]);
    }
}

function handleUserInfo(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $member = $bot->getChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);

        $user = $member['user'];
        $name = $user['first_name'] ?? 'Unknown';
        if (isset($user['last_name'])) {
            $name .= ' ' . $user['last_name'];
        }

        $info = "👤 *User Information*\n\n";
        $info .= "🆔 ID: `{$user['id']}`\n";
        $info .= "👤 Name: {$name}\n";

        if (isset($user['username'])) {
            $info .= "🔗 Username: @{$user['username']}\n";
        }

        if (isset($user['language_code'])) {
            $info .= "🌐 Language: {$user['language_code']}\n";
        }

        $info .= "\n📋 Status: " . ucfirst($member['status']) . "\n";

        if ($member['status'] === 'administrator' || $member['status'] === 'creator') {
            if (isset($member['can_be_edited'])) {
                $info .= "✏️ Can be edited: " . ($member['can_be_edited'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_change_info'])) {
                $info .= "ℹ️ Can change info: " . ($member['can_change_info'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_delete_messages'])) {
                $info .= "🗑️ Can delete messages: " . ($member['can_delete_messages'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_invite_users'])) {
                $info .= "➕ Can invite users: " . ($member['can_invite_users'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_restrict_members'])) {
                $info .= "⛔ Can restrict members: " . ($member['can_restrict_members'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_pin_messages'])) {
                $info .= "📌 Can pin messages: " . ($member['can_pin_messages'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($member['can_promote_members'])) {
                $info .= "⬆️ Can promote members: " . ($member['can_promote_members'] ? 'Yes' : 'No') . "\n";
            }
        }

        if (isset($member['until_date'])) {
            $info .= "\n⏰ Restricted until: " . date('Y-m-d H:i:s', $member['until_date']) . "\n";
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $info,
            'parse_mode' => 'Markdown'
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error getting user info: " . $e->getMessage()
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

        $result = $bot->banChatMember($params);

        $text = "⛔ User banned successfully!";
        if ($untilDate !== null) {
            $text .= "\n\n⏰ Until: " . date('Y-m-d H:i:s', $untilDate);
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error banning user: " . $e->getMessage()
        ]);
    }
}

function handleUnban(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $bot->unbanChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'only_if_banned' => true
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ User unbanned successfully!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error unbanning user: " . $e->getMessage()
        ]);
    }
}

function handleRestrict(TelegramBot $bot, int $chatId, int $userId, array $permissions): void
{
    try {
        $bot->restrictChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'permissions' => $permissions
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "⛔ User restricted successfully!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error restricting user: " . $e->getMessage()
        ]);
    }
}

function handlePromote(TelegramBot $bot, int $chatId, int $userId, array $rights): void
{
    try {
        $bot->promoteChatMember(array_merge([
            'chat_id' => $chatId,
            'user_id' => $userId
        ], $rights));

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "⬆️ User promoted to administrator!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error promoting user: " . $e->getMessage()
        ]);
    }
}

function handleKick(TelegramBot $bot, int $chatId, int $userId): void
{
    try {
        $bot->kickChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "👢 User kicked from the chat!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error kicking user: " . $e->getMessage()
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

        $bot->restrictChatMember([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'permissions' => $permissions,
            'until_date' => $untilDate
        ]);

        $durationText = $duration >= 3600
            ? round($duration / 3600) . ' hours'
            : round($duration / 60) . ' minutes';

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🔇 User muted for {$durationText}!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error muting user: " . $e->getMessage()
        ]);
    }
}

function handlePinMessage(TelegramBot $bot, int $chatId, int $messageId, bool $disableNotification = false): void
{
    try {
        $bot->pinChatMessage([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'disable_notification' => $disableNotification
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "📌 Message pinned successfully!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error pinning message: " . $e->getMessage()
        ]);
    }
}

function handleUnpinMessage(TelegramBot $bot, int $chatId, ?int $messageId = null): void
{
    try {
        if ($messageId !== null) {
            $bot->unpinChatMessage([
                'chat_id' => $chatId,
                'message_id' => $messageId
            ]);
            $text = "📍 Message unpinned successfully!";
        } else {
            $bot->unpinAllChatMessages(['chat_id' => $chatId]);
            $text = "📍 All messages unpinned successfully!";
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $text
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error unpinning: " . $e->getMessage()
        ]);
    }
}

function handleSetChatTitle(TelegramBot $bot, int $chatId, string $title): void
{
    try {
        $bot->setChatTitle([
            'chat_id' => $chatId,
            'title' => $title
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ Chat title changed to: {$title}"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error changing title: " . $e->getMessage()
        ]);
    }
}

function handleSetChatDescription(TelegramBot $bot, int $chatId, string $description): void
{
    try {
        $bot->setChatDescription([
            'chat_id' => $chatId,
            'description' => $description
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "✅ Chat description updated!"
        ]);

    } catch (Exception $e) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "❌ Error updating description: " . $e->getMessage()
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

                    $bot->answerCallbackQuery([
                        'callback_query_id' => $queryId
                    ]);

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
                            $bot->editMessageText([
                                'chat_id' => $chatId,
                                'message_id' => $messageId,
                                'text' => "❓ *Admin Commands Help*\n\n"
                                    . "/ban @user [time] - Ban user (optional: time in seconds)\n"
                                    . "/unban @user - Unban user\n"
                                    . "/kick @user - Kick user\n"
                                    . "/mute @user [minutes] - Mute user (default: 60 minutes)\n"
                                    . "/info @user - Get user info\n"
                                    . "/promote @user - Promote to admin\n"
                                    . "/demote @user - Demote from admin\n"
                                    . "/pin - Pin the replied message\n"
                                    . "/unpin - Unpin the replied message\n"
                                    . "/unpinall - Unpin all messages\n"
                                    . "/title <text> - Change chat title\n"
                                    . "/desc <text> - Change chat description\n"
                                    . "/stats - Show group statistics\n"
                                    . "/admins - List administrators",
                                'parse_mode' => 'Markdown'
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
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to get their info."
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
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to ban them."
                                    ]);
                                }
                                break;

                            case '/unban':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handleUnban($bot, $chatId, $userId);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to unban them."
                                    ]);
                                }
                                break;

                            case '/kick':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    handleKick($bot, $chatId, $userId);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to kick them."
                                    ]);
                                }
                                break;

                            case '/mute':
                                if (isset($message['reply_to_message'])) {
                                    $userId = $message['reply_to_message']['from']['id'];
                                    $duration = isset($args[0]) ? (int)$args[0] * 60 : 3600; // Default 1 hour
                                    handleMute($bot, $chatId, $userId, $duration);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to mute them."
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
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to promote them."
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
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a user's message to demote them."
                                    ]);
                                }
                                break;

                            case '/pin':
                                if ($replyToMessage !== null) {
                                    $messageId = $replyToMessage['message_id'];
                                    handlePinMessage($bot, $chatId, $messageId, false);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a message to pin it."
                                    ]);
                                }
                                break;

                            case '/unpin':
                                if ($replyToMessage !== null) {
                                    $messageId = $replyToMessage['message_id'];
                                    handleUnpinMessage($bot, $chatId, $messageId);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please reply to a message to unpin it, or use /unpinall to unpin all."
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
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please provide a title. Usage: /title <new title>"
                                    ]);
                                }
                                break;

                            case '/desc':
                                $description = implode(' ', $args);
                                if (!empty($description)) {
                                    handleSetChatDescription($bot, $chatId, $description);
                                } else {
                                    $bot->sendMessage([
                                        'chat_id' => $chatId,
                                        'text' => "⚠️ Please provide a description. Usage: /desc <new description>"
                                    ]);
                                }
                                break;

                            default:
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => "Unknown command: $command\nType /help to see available commands."
                                ]);
                        }
                    }
                }
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
