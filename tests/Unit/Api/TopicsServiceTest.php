<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\TopicsService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class TopicsServiceTest extends TestCase
{
    private TopicsService $topicsService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->topicsService = new TopicsService($apiService);
    }

    private function topicFixture(int $threadId = 5): array
    {
        return [
            'message_thread_id' => $threadId,
            'name' => 'Test Topic',
            'icon_color' => 7322096,
            'is_closed' => false,
            'is_hidden' => false,
        ];
    }

    public function test_create_returns_topic(): void
    {
        $expected = $this->topicFixture();
        $this->mockClient->setResponse($expected);

        $result = $this->topicsService->create(['chat_id' => 123, 'name' => 'Test Topic']);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_create_sends_correct_params(): void
    {
        $this->mockClient->setResponse($this->topicFixture());

        $this->topicsService->create([
            'chat_id' => 456,
            'name' => 'My Topic',
            'icon_color' => 7322096,
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(456, $lastRequest['params']['chat_id']);
        $this->assertSame('My Topic', $lastRequest['params']['name']);
    }

    public function test_edit_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->edit([
            'chat_id' => 123,
            'message_thread_id' => 5,
            'name' => 'Updated Topic',
        ]);

        $this->assertTrue($result);
    }

    public function test_close_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->close(['chat_id' => 123, 'message_thread_id' => 5]);

        $this->assertTrue($result);
    }

    public function test_reopen_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->reopen(['chat_id' => 123, 'message_thread_id' => 5]);

        $this->assertTrue($result);
    }

    public function test_delete_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->delete(['chat_id' => 123, 'message_thread_id' => 5]);

        $this->assertTrue($result);
    }

    public function test_unpinAll_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->unpinAll(['chat_id' => 123, 'message_thread_id' => 5]);

        $this->assertTrue($result);
    }

    public function test_editGeneral_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->editGeneral(['chat_id' => 123, 'name' => 'General']);

        $this->assertTrue($result);
    }

    public function test_closeGeneral_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->closeGeneral(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_reopenGeneral_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->reopenGeneral(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_hideGeneral_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->hideGeneral(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_unhideGeneral_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->topicsService->unhideGeneral(['chat_id' => 123]);

        $this->assertTrue($result);
    }

    public function test_get_returns_topic(): void
    {
        $expected = $this->topicFixture(7);
        $this->mockClient->setResponse($expected);

        $result = $this->topicsService->get(['chat_id' => 123, 'message_thread_id' => 7]);

        $this->assertSame($expected, $result);
        $this->assertSame(7, $result['message_thread_id']);
    }

    public function test_getAll_returns_topic_list(): void
    {
        $expected = [
            'total_count' => 2,
            'topics' => [
                $this->topicFixture(1),
                $this->topicFixture(2),
            ],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->topicsService->getAll(['chat_id' => 123]);

        $this->assertSame(2, $result['total_count']);
        $this->assertCount(2, $result['topics']);
    }

    public function test_getIconStickers_returns_sticker_list(): void
    {
        $expected = [
            ['file_id' => 'sticker1', 'type' => 'custom_emoji'],
            ['file_id' => 'sticker2', 'type' => 'custom_emoji'],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->topicsService->getIconStickers();

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertEmpty($lastRequest['params']);
    }
}
