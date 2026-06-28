<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\PollsService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class PollsServiceTest extends TestCase
{
    private PollsService $pollsService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->pollsService = new PollsService($apiService);
    }

    public function test_send_returns_message(): void
    {
        $expected = [
            'message_id' => 42,
            'chat' => ['id' => 123],
            'poll' => [
                'id' => 'poll123',
                'question' => 'What is your favourite colour?',
                'options' => [
                    ['text' => 'Red', 'voter_count' => 0],
                    ['text' => 'Blue', 'voter_count' => 0],
                ],
                'is_closed' => false,
            ],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->pollsService->send([
            'chat_id' => 123,
            'question' => 'What is your favourite colour?',
            'options' => ['Red', 'Blue'],
        ]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_send_with_quiz_type(): void
    {
        $expected = [
            'message_id' => 99,
            'poll' => ['id' => 'quiz1', 'type' => 'quiz', 'correct_option_id' => 1],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->pollsService->send([
            'chat_id' => 456,
            'question' => 'PHP stands for?',
            'options' => ['Hypertext Preprocessor', 'Personal Home Page', 'Pre-Hypertext Processor'],
            'type' => 'quiz',
            'correct_option_id' => 0,
        ]);

        $this->assertSame($expected, $result);
    }

    public function test_stop_returns_updated_poll(): void
    {
        $expected = [
            'message_id' => 42,
            'poll' => ['id' => 'poll123', 'is_closed' => true],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->pollsService->stop(['chat_id' => 123, 'message_id' => 42]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_close_returns_updated_poll(): void
    {
        $expected = [
            'message_id' => 55,
            'poll' => ['id' => 'poll456', 'is_closed' => true],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->pollsService->close(['chat_id' => 123, 'message_id' => 55]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_send_records_request_params(): void
    {
        $this->mockClient->setResponse(['message_id' => 1, 'poll' => ['id' => 'p1']]);

        $this->pollsService->send([
            'chat_id' => 789,
            'question' => 'Test?',
            'options' => ['Yes', 'No'],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(789, $lastRequest['params']['chat_id']);
        $this->assertSame('Test?', $lastRequest['params']['question']);
    }
}
