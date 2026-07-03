<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\ChatService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

/**
 * Chat Service Tests
 *
 * Tests all 13 chat-related operations with different return types
 */
final class ChatServiceTest extends TestCase
{
    private ChatService $chatService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->chatService = new ChatService($apiService);
    }

    public function test_sendAction_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->sendAction(['chat_id' => 123, 'action' => 'typing']);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_getChat_returns_chat_object(): void
    {
        $expectedResponse = [
            'id' => 123456789,
            'type' => 'group',
            'title' => 'Test Group'
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->chatService->getChat(['chat_id' => 123456789]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertSame(123456789, $result['id']);
        $this->assertSame('group', $result['type']);
    }

    public function test_getMember_returns_member_object(): void
    {
        $expectedResponse = [
            'user' => ['id' => 123456789, 'is_bot' => false, 'first_name' => 'John'],
            'status' => 'administrator'
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->chatService->getMember(['chat_id' => 123, 'user_id' => 456]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertSame('administrator', $result['status']);
    }

    public function test_getAdministrators_returns_array(): void
    {
        $expectedResponse = [
            [
                'user' => ['id' => 111, 'is_bot' => false, 'first_name' => 'Admin1'],
                'status' => 'administrator'
            ],
            [
                'user' => ['id' => 222, 'is_bot' => false, 'first_name' => 'Admin2'],
                'status' => 'administrator'
            ]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->chatService->getAdministrators(['chat_id' => 123]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_getMemberCount_returns_int(): void
    {
        $this->mockClient->setIntResponse(42);

        $result = $this->chatService->getMemberCount(['chat_id' => 123]);

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function test_banMember_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->banMember(['chat_id' => 123, 'user_id' => 456]);

        $this->assertTrue($result);
    }

    public function test_unbanMember_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->unbanMember(['chat_id' => 123, 'user_id' => 456]);

        $this->assertTrue($result);
    }

    public function test_restrictMember_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->restrictMember([
            'chat_id' => 123,
            'user_id' => 456,
            'permissions' => ['can_send_messages' => true]
        ]);

        $this->assertTrue($result);
    }

    public function test_promoteMember_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->promoteMember([
            'chat_id' => 123,
            'user_id' => 456,
            'can_change_info' => true
        ]);

        $this->assertTrue($result);
    }

    public function test_leave_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->leave(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_pinMessage_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->pinMessage([
            'chat_id' => 123,
            'message_id' => 456
        ]);

        $this->assertTrue($result);
    }

    public function test_unpinMessage_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->unpinMessage([
            'chat_id' => 123,
            'message_id' => 456
        ]);

        $this->assertTrue($result);
    }

    public function test_unpinAllMessages_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->unpinAllMessages(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_answerCallbackQuery_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->answerCallbackQuery(['callback_query_id' => 'cq123']);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_answerCallbackQuery_with_alert(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->answerCallbackQuery([
            'callback_query_id' => 'cq456',
            'text' => 'Action completed!',
            'show_alert' => true,
        ]);

        $this->assertTrue($result);
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('cq456', $lastRequest['params']['callback_query_id']);
        $this->assertTrue($lastRequest['params']['show_alert']);
    }

    public function test_setChatTitle_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->setChatTitle(['chat_id' => 123, 'title' => 'New Title']);

        $this->assertTrue($result);
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('New Title', $lastRequest['params']['title']);
    }

    public function test_setChatDescription_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->setChatDescription([
            'chat_id' => 123,
            'description' => 'A great group',
        ]);

        $this->assertTrue($result);
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('A great group', $lastRequest['params']['description']);
    }

    public function test_setChatDescription_without_description_clears_it(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->setChatDescription(['chat_id' => 123]);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_setChatPhoto_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->setChatPhoto(['chat_id' => 123, 'photo' => 'file_id_xyz']);

        $this->assertTrue($result);
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('file_id_xyz', $lastRequest['params']['photo']);
    }

    public function test_deleteChatPhoto_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->chatService->deleteChatPhoto(['chat_id' => 123]);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_setChatPermissions_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $permissions = [
            'can_send_messages' => true,
            'can_send_polls' => false,
            'can_invite_users' => true,
        ];
        $result = $this->chatService->setChatPermissions([
            'chat_id' => 123,
            'permissions' => $permissions,
        ]);

        $this->assertTrue($result);
        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame($permissions, $lastRequest['params']['permissions']);
    }
}
