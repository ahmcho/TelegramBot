<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Api\Methods\MessageService;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Tests\Helpers\MockTelegramResponse;

/**
 * Message Service Tests
 *
 * Tests send() method, MarkdownV2 auto-escaping, batch operations,
 * and edit methods.
 */
final class MessageServiceTest extends TestCase
{
    private BotConfig $config;
    private MockHttpClient $mockClient;
    private ApiService $apiService;
    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new BotConfig('test_token');
        $this->mockClient = new MockHttpClient();
        $bulkManager = new BulkOperationManager($this->mockClient, $this->config);
        $this->apiService = new ApiService($this->mockClient, $this->config, $bulkManager);
        $this->messageService = new MessageService($this->apiService);
    }

    public function test_send_makes_api_call(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'from' => ['id' => 123456789, 'is_bot' => true, 'first_name' => 'TestBot'],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Hello'
        ]);

        $result = $this->messageService->send(['chat_id' => 123456789, 'text' => 'Hello']);

        $this->assertSame(1, $result['message_id']);
        $this->assertSame(123456789, $result['chat']['id']);
        $this->assertSame('Hello', $result['text']);
    }

    public function test_send_with_markdown_v2_auto_escapes(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'from' => ['id' => 123456789, 'is_bot' => true, 'first_name' => 'TestBot'],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Hello_world'
        ]);

        $result = $this->messageService->send([
            'chat_id' => 123456789,
            'text' => 'Hello_world',
            'parse_mode' => 'MarkdownV2'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Hello\_world', $request['params']['text']);
    }

    public function test_send_without_parse_mode_does_not_escape(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'from' => ['id' => 123456789, 'is_bot' => true, 'first_name' => 'TestBot'],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Hello_world'
        ]);

        $result = $this->messageService->send([
            'chat_id' => 123456789,
            'text' => 'Hello_world'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Hello_world', $request['params']['text']);
    }

    public function test_send_with_html_parse_mode_does_not_escape(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'from' => ['id' => 123456789, 'is_bot' => true, 'first_name' => 'TestBot'],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Hello_world'
        ]);

        $this->messageService->send([
            'chat_id' => 123456789,
            'text' => 'Hello_world',
            'parse_mode' => 'HTML'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Hello_world', $request['params']['text']);
    }

    public function test_editText_makes_api_call(): void
    {
        $this->mockClient->setResponse([]);

        $result = $this->messageService->editText([
            'chat_id' => 123456789,
            'message_id' => 1,
            'text' => 'Updated'
        ]);

        $this->assertSame([], $result);
    }

    public function test_editCaption_makes_api_call(): void
    {
        $this->mockClient->setResponse([]);

        $result = $this->messageService->editCaption([
            'chat_id' => 123456789,
            'message_id' => 1,
            'caption' => 'New caption'
        ]);

        $this->assertSame([], $result);
    }

    public function test_delete_makes_api_call(): void
    {
        $this->mockClient->setResponse([]);

        $result = $this->messageService->delete([
            'chat_id' => 123456789,
            'message_id' => 1
        ]);

        $this->assertSame([], $result);
    }

    public function test_forward_makes_api_call(): void
    {
        $this->mockClient->setResponse(['message_id' => 1, 'chat' => ['id' => 123456789]]);

        $result = $this->messageService->forward([
            'chat_id' => 123456789,
            'from_chat_id' => 987654321,
            'message_id' => 1
        ]);

        $this->assertIsArray($result);
    }

    public function test_copy_makes_api_call(): void
    {
        $this->mockClient->setResponse(['message_id' => 2, 'chat' => ['id' => 123456789]]);

        $result = $this->messageService->copy([
            'chat_id' => 123456789,
            'from_chat_id' => 987654321,
            'message_id' => 1
        ]);

        $this->assertSame(2, $result['message_id']);
    }
}
