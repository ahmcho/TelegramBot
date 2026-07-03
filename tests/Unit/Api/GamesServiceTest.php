<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\GamesService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class GamesServiceTest extends TestCase
{
    private GamesService $gamesService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->gamesService = new GamesService($apiService);
    }

    public function test_sendGame_returns_message(): void
    {
        $expected = [
            'message_id' => 42,
            'chat' => ['id' => 123],
            'game' => ['title' => 'Test Game', 'description' => 'A game'],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->gamesService->sendGame([
            'chat_id' => 123,
            'game_short_name' => 'test_game',
        ]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_sendGame_records_request_params(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->gamesService->sendGame([
            'chat_id' => 456,
            'game_short_name' => 'my_game',
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(456, $lastRequest['params']['chat_id']);
        $this->assertSame('my_game', $lastRequest['params']['game_short_name']);
    }

    public function test_setGameScore_returns_message(): void
    {
        $expected = [
            'message_id' => 42,
            'game' => ['title' => 'Test Game'],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->gamesService->setGameScore([
            'user_id' => 111,
            'score' => 100,
            'chat_id' => 123,
            'message_id' => 42,
        ]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_setGameScore_returns_true_for_inline_message(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->gamesService->setGameScore([
            'user_id' => 111,
            'score' => 100,
            'inline_message_id' => 'inline123',
        ]);

        $this->assertTrue($result);
    }

    public function test_setGameScore_records_request_params(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->gamesService->setGameScore([
            'user_id' => 222,
            'score' => 50,
            'chat_id' => 789,
            'message_id' => 5,
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(222, $lastRequest['params']['user_id']);
        $this->assertSame(50, $lastRequest['params']['score']);
    }

    public function test_getGameHighScores_returns_array_of_scores(): void
    {
        $expected = [
            ['position' => 1, 'user' => ['id' => 111], 'score' => 100],
            ['position' => 2, 'user' => ['id' => 222], 'score' => 50],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->gamesService->getGameHighScores([
            'user_id' => 111,
            'chat_id' => 123,
            'message_id' => 42,
        ]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_getGameHighScores_records_request_params(): void
    {
        $this->mockClient->setResponse([]);

        $this->gamesService->getGameHighScores([
            'user_id' => 333,
            'inline_message_id' => 'inline456',
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(333, $lastRequest['params']['user_id']);
        $this->assertSame('inline456', $lastRequest['params']['inline_message_id']);
    }
}
