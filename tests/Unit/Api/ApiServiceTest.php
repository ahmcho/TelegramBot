<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * API Service Tests
 *
 * Tests call() method with mock HTTP client, getBulkManager(),
 * getConfig(), and URL building for different API methods.
 */
final class ApiServiceTest extends TestCase
{
    private BotConfig $config;
    private MockHttpClient $mockClient;
    private BulkOperationManager $bulkManager;
    private ApiService $apiService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new BotConfig('test_token');
        $this->mockClient = new MockHttpClient();
        $this->bulkManager = new BulkOperationManager($this->mockClient, $this->config);
        $this->apiService = new ApiService($this->mockClient, $this->config, $this->bulkManager);
    }

    public function test_call_makes_http_request(): void
    {
        $this->mockClient->setResponse(['message_id' => 123]);

        $result = $this->apiService->call(ApiMethod::SEND_MESSAGE, ['chat_id' => 123, 'text' => 'test']);

        $this->assertSame(['message_id' => 123], $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_call_builds_correct_url(): void
    {
        $this->mockClient->setResponse([]);

        $this->apiService->call(ApiMethod::GET_ME, []);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://api.telegram.org/bottest_token/getMe', $request['url']);
    }

    public function test_getBulkManager_returns_manager(): void
    {
        $manager = $this->apiService->getBulkManager();

        $this->assertSame($this->bulkManager, $manager);
    }

    public function test_getConfig_returns_config(): void
    {
        $config = $this->apiService->getConfig();

        $this->assertSame($this->config, $config);
    }

    public function test_call_with_different_methods(): void
    {
        $methods = [
            ApiMethod::SEND_MESSAGE,
            ApiMethod::GET_ME,
            ApiMethod::GET_CHAT,
            ApiMethod::SEND_PHOTO,
        ];

        foreach ($methods as $method) {
            $this->mockClient->setResponse([]);
            $this->apiService->call($method, []);
        }

        $this->assertSame(count($methods), $this->mockClient->getRequestCount());
    }
}
