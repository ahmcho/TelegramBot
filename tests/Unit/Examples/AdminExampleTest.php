<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/admin.php
 *
 * Verifies the group administration patterns demonstrated in the example:
 * - getChat() returns chat info
 * - getMemberCount() returns integer
 * - getAdministrators() returns list
 * - getChatMember() for specific user
 * - banMember() / unbanMember()
 * - restrictMember() with permission mask
 * - promoteMember()
 * - pinMessage() / unpinMessage()
 * - answerCallbackQuery()
 * - Admin keyboard with inline buttons
 */
final class AdminExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;
    private int $chatId = -1001234567890;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_get_chat_returns_chat_info(): void
    {
        $this->mockClient->setResponse([
            'id' => $this->chatId,
            'title' => 'Test Group',
            'type' => 'supergroup',
            'username' => 'testgroup',
            'description' => 'A test group',
        ]);

        $chat = $this->bot->chats()->getChat(['chat_id' => $this->chatId]);

        $this->assertSame($this->chatId, $chat['id']);
        $this->assertSame('Test Group', $chat['title']);
        $this->assertSame('supergroup', $chat['type']);
    }

    public function test_get_member_count_returns_integer(): void
    {
        $this->mockClient->setIntResponse(142);

        $count = $this->bot->chats()->getMemberCount(['chat_id' => $this->chatId]);

        $this->assertSame(142, $count);
    }

    public function test_get_administrators_returns_list(): void
    {
        $this->mockClient->setResponse([
            ['user' => ['id' => 1, 'first_name' => 'Admin1'], 'status' => 'administrator'],
            ['user' => ['id' => 2, 'first_name' => 'Admin2'], 'status' => 'creator'],
        ]);

        $admins = $this->bot->chats()->getAdministrators(['chat_id' => $this->chatId]);

        $this->assertCount(2, $admins);
        $this->assertSame('administrator', $admins[0]['status']);
        $this->assertSame('creator', $admins[1]['status']);
    }

    public function test_get_chat_member_returns_member_info(): void
    {
        $this->mockClient->setResponse([
            'user' => ['id' => 999, 'first_name' => 'User'],
            'status' => 'member',
        ]);

        $member = $this->bot->chats()->getMember(['chat_id' => $this->chatId, 'user_id' => 999]);

        $this->assertSame('member', $member['status']);
        $this->assertSame(999, $member['user']['id']);
    }

    public function test_ban_member(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->banMember([
            'chat_id' => $this->chatId,
            'user_id' => 999,
            'revoke_messages' => true,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame($this->chatId, $request['params']['chat_id']);
        $this->assertSame(999, $request['params']['user_id']);
        $this->assertTrue($request['params']['revoke_messages']);
    }

    public function test_unban_member(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->unbanMember([
            'chat_id' => $this->chatId,
            'user_id' => 999,
            'only_if_banned' => true,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame(999, $request['params']['user_id']);
        $this->assertTrue($request['params']['only_if_banned']);
    }

    public function test_restrict_member_with_permissions(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->restrictMember([
            'chat_id' => $this->chatId,
            'user_id' => 999,
            'permissions' => [
                'can_send_messages' => false,
                'can_send_media_messages' => false,
                'can_send_other_messages' => false,
                'can_add_web_page_previews' => false,
            ],
            'until_date' => time() + 3600,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $permissions = $request['params']['permissions'];
        $this->assertFalse($permissions['can_send_messages']);
    }

    public function test_promote_member_to_admin(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->promoteMember([
            'chat_id' => $this->chatId,
            'user_id' => 777,
            'can_manage_chat' => true,
            'can_delete_messages' => true,
            'can_ban_members' => true,
            'can_promote_members' => false,
            'can_change_info' => true,
            'can_invite_users' => true,
            'can_pin_messages' => true,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertTrue($request['params']['can_manage_chat']);
        $this->assertTrue($request['params']['can_delete_messages']);
        $this->assertFalse($request['params']['can_promote_members']);
    }

    public function test_pin_message(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->pinMessage([
            'chat_id' => $this->chatId,
            'message_id' => 500,
            'disable_notification' => true,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame(500, $request['params']['message_id']);
        $this->assertTrue($request['params']['disable_notification']);
    }

    public function test_unpin_message(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->unpinMessage([
            'chat_id' => $this->chatId,
            'message_id' => 500,
        ]);

        $this->assertTrue($result);
    }

    public function test_answer_callback_query(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->chats()->answerCallbackQuery([
            'callback_query_id' => 'cq123',
            'text' => 'Action performed!',
            'show_alert' => false,
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame('cq123', $request['params']['callback_query_id']);
        $this->assertSame('Action performed!', $request['params']['text']);
    }

    public function test_admin_keyboard_has_correct_structure(): void
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
            )
            ->toArray();

        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $rows = $keyboard['inline_keyboard'];
        $this->assertCount(3, $rows);
        $this->assertSame('admin:stats', $rows[0][0]['callback_data']);
        $this->assertSame('admin:admins', $rows[0][1]['callback_data']);
        $this->assertSame('admin:help', $rows[2][0]['callback_data']);
    }

    public function test_group_stats_combines_member_count_and_chat_info(): void
    {
        $this->mockClient->setIntResponse(250);
        $this->mockClient->setResponse([
            'id' => $this->chatId,
            'title' => 'Big Group',
            'type' => 'supergroup',
            'description' => 'Group description',
        ]);
        $this->mockClient->setResponse(['message_id' => 100]);

        $memberCount = $this->bot->chats()->getMemberCount(['chat_id' => $this->chatId]);
        $chat = $this->bot->chats()->getChat(['chat_id' => $this->chatId]);

        $stats = "Members: {$memberCount}\nTitle: {$chat['title']}";

        $this->bot->messages()->send([
            'chat_id' => $this->chatId,
            'text' => $stats,
            'parse_mode' => 'MarkdownV2',
        ]);

        $this->assertSame(3, $this->mockClient->getRequestCount());
        $this->assertSame(250, $memberCount);
        $this->assertSame('Big Group', $chat['title']);
    }

    public function test_formatter_bold_is_escaped_in_stats(): void
    {
        $boldTitle = $this->bot->formatter()->bold('📊 Group Statistics');

        $this->assertStringContainsString('*', $boldTitle);
        $this->assertStringContainsString('Group Statistics', $boldTitle);
    }
}
