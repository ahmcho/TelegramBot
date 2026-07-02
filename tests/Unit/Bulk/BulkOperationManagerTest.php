<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Bulk;

use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Bulk\BulkResult;
use AhmCho\Telegram\Bulk\BulkSendException;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for BulkOperationManager
 */
class BulkOperationManagerTest extends TestCase
{
    private MockHttpClient $mockClient;
    private BotConfig $config;
    private BulkOperationManager $manager;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
        $this->config = new BotConfig(token: 'test:token', throwExceptions: false);
        $this->manager = new BulkOperationManager($this->mockClient, $this->config);
    }

    public function test_sendBulk_with_empty_array_returns_empty_result(): void
    {
        $result = $this->manager->sendBulk(ApiMethod::SEND_MESSAGE, []);

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $result->total);
        $this->assertEquals(0, $result->successful);
        $this->assertEquals(0, $result->failed);
        $this->assertEmpty($result->results);
    }

    public function test_sendBulk_with_single_request_succeeds(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 123,
            'chat' => ['id' => 123]
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello']
        ];

        $result = $this->manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(1, $result->total);
        $this->assertEquals(1, $result->successful);
        $this->assertEquals(0, $result->failed);
        $this->assertCount(1, $result->results);
        $this->assertTrue($result->results[0]['success']);
        $this->assertEquals(123, $result->results[0]['chat_id']);
        $this->assertEquals(123, $result->results[0]['message_id']);
    }

    public function test_sendBulk_with_multiple_requests_all_succeed(): void
    {
        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 2, 'chat' => ['id' => 456]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 3, 'chat' => ['id' => 789]], 'exception' => null, 'http_code' => 200],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello 123'],
            ['chat_id' => 456, 'text' => 'Hello 456'],
            ['chat_id' => 789, 'text' => 'Hello 789'],
        ];

        $result = $this->manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $result->total);
        $this->assertEquals(3, $result->successful);
        $this->assertEquals(0, $result->failed);
    }

    public function test_sendBulk_with_mixed_results_calculates_correctly(): void
    {
        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => null, 'exception' => new \Exception('Chat not found'), 'http_code' => 0],
            ['response' => ['message_id' => 3, 'chat' => ['id' => 789]], 'exception' => null, 'http_code' => 200],
            ['response' => null, 'exception' => new \Exception('Forbidden'), 'http_code' => 0],
            ['response' => ['message_id' => 5, 'chat' => ['id' => 555]], 'exception' => null, 'http_code' => 200],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
            ['chat_id' => 789, 'text' => 'Hello'],
            ['chat_id' => 999, 'text' => 'Hello'],
            ['chat_id' => 555, 'text' => 'Hello'],
        ];

        $result = $this->manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(5, $result->total);
        $this->assertEquals(3, $result->successful);
        $this->assertEquals(2, $result->failed);
    }

    public function test_sendBulk_does_not_throw_on_partial_failure_when_throw_exceptions_enabled(): void
    {
        $configWithThrow = new BotConfig(token: 'test:token', throwExceptions: true);
        $manager = new BulkOperationManager($this->mockClient, $configWithThrow);

        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => null, 'exception' => new \Exception('Chat not found'), 'http_code' => 0],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
        ];

        // Partial failure (1 success, 1 failure) must NOT throw — only total failure throws
        $result = $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(2, $result->total);
        $this->assertEquals(1, $result->successful);
        $this->assertEquals(1, $result->failed);
    }

    public function test_sendBulk_throws_on_total_failure_when_throw_exceptions_enabled(): void
    {
        $configWithThrow = new BotConfig(token: 'test:token', throwExceptions: true);
        $manager = new BulkOperationManager($this->mockClient, $configWithThrow);

        $this->mockClient->setResponses([
            ['response' => null, 'exception' => new \Exception('Chat not found'), 'http_code' => 0],
            ['response' => null, 'exception' => new \Exception('Forbidden'), 'http_code' => 0],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
        ];

        $this->expectException(BulkSendException::class);
        $this->expectExceptionMessage('Bulk operation failed completely: all 2 requests failed');

        $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);
    }

    public function test_sendBulk_with_throw_exceptions_disabled_returns_result_with_failures(): void
    {
        $configNoThrow = new BotConfig(token: 'test:token', throwExceptions: false);
        $manager = new BulkOperationManager($this->mockClient, $configNoThrow);

        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => null, 'exception' => new \Exception('Chat not found'), 'http_code' => 0],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
        ];

        $result = $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals(2, $result->total);
        $this->assertEquals(1, $result->successful);
        $this->assertEquals(1, $result->failed);
        $this->assertCount(2, $result->results);
    }

    public function test_sendBulk_partial_failure_returns_result_not_throws(): void
    {
        $configWithThrow = new BotConfig(token: 'test:token', throwExceptions: true);
        $manager = new BulkOperationManager($this->mockClient, $configWithThrow);

        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => null, 'exception' => new \Exception('Forbidden'), 'http_code' => 0],
            ['response' => null, 'exception' => new \Exception('Not found'), 'http_code' => 0],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
            ['chat_id' => 789, 'text' => 'Hello'],
        ];

        // 1 success + 2 failures = partial failure, must NOT throw
        $result = $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertEquals(3, $result->total);
        $this->assertEquals(1, $result->successful);
        $this->assertEquals(2, $result->failed);
        $this->assertTrue($result->hasFailures());
    }

    public function test_sendBulk_total_failure_exception_contains_bulk_result(): void
    {
        $configWithThrow = new BotConfig(token: 'test:token', throwExceptions: true);
        $manager = new BulkOperationManager($this->mockClient, $configWithThrow);

        $this->mockClient->setResponses([
            ['response' => null, 'exception' => new \Exception('Forbidden'), 'http_code' => 0],
            ['response' => null, 'exception' => new \Exception('Not found'), 'http_code' => 0],
            ['response' => null, 'exception' => new \Exception('Blocked'), 'http_code' => 0],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'Hello'],
            ['chat_id' => 789, 'text' => 'Hello'],
        ];

        try {
            $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);
            $this->fail('Expected BulkSendException to be thrown');
        } catch (BulkSendException $e) {
            $result = $e->getResult();
            $this->assertInstanceOf(BulkResult::class, $result);
            $this->assertEquals(3, $result->total);
            $this->assertEquals(0, $result->successful);
            $this->assertEquals(3, $result->failed);
            $this->assertTrue($result->hasFailures());
        }
    }

    public function test_broadcast_with_empty_chat_ids_returns_empty_result(): void
    {
        $result = $this->manager->broadcast(
            ApiMethod::SEND_MESSAGE,
            [],
            ['text' => 'Hello']
        );

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(0, $result->total);
        $this->assertEmpty($result->results);
    }

    public function test_broadcast_distributes_common_params_to_all_chats(): void
    {
        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 2, 'chat' => ['id' => 456]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 3, 'chat' => ['id' => 789]], 'exception' => null, 'http_code' => 200],
        ]);

        $chatIds = [123, 456, 789];
        $commonParams = [
            'text' => 'Broadcast message',
            'parse_mode' => 'Markdown'
        ];

        $result = $this->manager->broadcast(ApiMethod::SEND_MESSAGE, $chatIds, $commonParams);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $result->total);

        // Verify each request had the correct params
        $requests = $this->mockClient->getRequests();
        $this->assertCount(3, $requests);

        $this->assertEquals(123, $requests[0]['params']['chat_id']);
        $this->assertEquals('Broadcast message', $requests[0]['params']['text']);
        $this->assertEquals('Markdown', $requests[0]['params']['parse_mode']);

        $this->assertEquals(456, $requests[1]['params']['chat_id']);
        $this->assertEquals('Broadcast message', $requests[1]['params']['text']);

        $this->assertEquals(789, $requests[2]['params']['chat_id']);
        $this->assertEquals('Broadcast message', $requests[2]['params']['text']);
    }

    public function test_broadcast_with_various_chat_id_types(): void
    {
        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 123]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 2, 'chat' => ['id' => 'abc']], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 3, 'chat' => ['id' => -100123456789]], 'exception' => null, 'http_code' => 200],
        ]);

        $chatIds = [123, 'abc', -100123456789];
        $commonParams = ['text' => 'Hello'];

        $result = $this->manager->broadcast(ApiMethod::SEND_MESSAGE, $chatIds, $commonParams);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $result->total);

        $requests = $this->mockClient->getRequests();
        $this->assertEquals(123, $requests[0]['params']['chat_id']);
        $this->assertEquals('abc', $requests[1]['params']['chat_id']);
        $this->assertEquals(-100123456789, $requests[2]['params']['chat_id']);
    }

    public function test_sendBulk_uses_correct_api_url_from_config(): void
    {
        $customConfig = new BotConfig(
            token: 'test:token',
            apiUrl: 'https://custom.api.telegram.org/bot',
            throwExceptions: false
        );
        $manager = new BulkOperationManager($this->mockClient, $customConfig);

        $this->mockClient->setResponse([
            'message_id' => 1,
            'chat' => ['id' => 123]
        ]);

        $requests = [['chat_id' => 123, 'text' => 'Hello']];
        $manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('custom.api.telegram.org', $request['url']);
        $this->assertStringEndsWith('sendMessage', $request['url']);
    }

    public function test_sendBulk_with_different_api_methods(): void
    {
        $this->mockClient->setResponses([
            ['response' => true, 'exception' => null, 'http_code' => 200],
            ['response' => true, 'exception' => null, 'http_code' => 200],
        ]);

        $requests = [
            ['chat_id' => 123, 'text' => 'Hello'],
            ['chat_id' => 456, 'text' => 'World'],
        ];

        // Test with SEND_MESSAGE
        $result1 = $this->manager->sendBulk(ApiMethod::SEND_MESSAGE, $requests);
        $this->assertTrue($result1->isSuccess());

        $this->mockClient->reset();

        // Test with COPY_MESSAGE
        $this->mockClient->setResponses([
            ['response' => ['message_id' => 1], 'exception' => null, 'http_code' => 200],
        ]);

        $copyRequests = [['chat_id' => 123, 'from_chat_id' => 456, 'message_id' => 789]];
        $result2 = $this->manager->sendBulk(ApiMethod::COPY_MESSAGE, $copyRequests);
        $this->assertTrue($result2->isSuccess());
    }
}
