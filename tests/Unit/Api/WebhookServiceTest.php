<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\WebhookService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

/**
 * Webhook Service Tests
 *
 * Tests all webhook-related operations with different return types
 */
final class WebhookServiceTest extends TestCase
{
    private WebhookService $webhookService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->webhookService = new WebhookService($apiService);
    }

    public function test_set_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->set([
            'url' => 'https://example.com/webhook'
        ]);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_set_with_all_parameters(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->set([
            'url' => 'https://example.com/webhook',
            'max_connections' => 40,
            'allowed_updates' => ['message', 'callback_query']
        ]);

        $this->assertTrue($result);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://example.com/webhook', $request['params']['url']);
        $this->assertSame(40, $request['params']['max_connections']);
        $this->assertSame(['message', 'callback_query'], $request['params']['allowed_updates']);
    }

    public function test_set_with_empty_url_deletes_webhook(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->set(['url' => '']);

        $this->assertTrue($result);
    }

    public function test_getInfo_returns_webhook_info(): void
    {
        $expectedResponse = [
            'url' => 'https://example.com/webhook',
            'has_custom_certificate' => false,
            'pending_update_count' => 0,
            'max_connections' => 40,
            'allowed_updates' => ['message', 'callback_query']
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->webhookService->getInfo();

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertSame('https://example.com/webhook', $result['url']);
        $this->assertSame(0, $result['pending_update_count']);
    }

    public function test_getInfo_when_no_webhook_set(): void
    {
        $expectedResponse = [
            'url' => '',
            'has_custom_certificate' => false,
            'pending_update_count' => 0,
            'allowed_updates' => []
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->webhookService->getInfo();

        $this->assertSame($expectedResponse, $result);
        $this->assertEmpty($result['url']);
    }

    public function test_delete_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->delete();

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_delete_with_drop_pending_updates(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->delete(['drop_pending_updates' => true]);

        $this->assertTrue($result);

        $request = $this->mockClient->getLastRequest();
        $this->assertTrue($request['params']['drop_pending_updates']);
    }

    public function test_set_with_certificate(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->set([
            'url' => 'https://example.com/webhook',
            'certificate' => 'path/to/certificate.pem'
        ]);

        $this->assertTrue($result);

        $request = $this->mockClient->getLastRequest();
        $this->assertArrayHasKey('certificate', $request['params']);
    }

    public function test_set_with_secret_token(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->webhookService->set([
            'url' => 'https://example.com/webhook',
            'secret_token' => 'my_secret_token_123'
        ]);

        $this->assertTrue($result);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('my_secret_token_123', $request['params']['secret_token']);
    }

    public function test_multiple_webhook_operations_in_sequence(): void
    {
        // Set webhook
        $this->mockClient->setBoolResponse(true);
        $this->webhookService->set(['url' => 'https://example.com/webhook']);

        // Get webhook info
        $expectedInfo = [
            'url' => 'https://example.com/webhook',
            'has_custom_certificate' => false,
            'pending_update_count' => 0
        ];
        $this->mockClient->setResponse($expectedInfo);
        $info = $this->webhookService->getInfo();
        $this->assertSame($expectedInfo, $info);

        // Delete webhook
        $this->mockClient->setBoolResponse(true);
        $result = $this->webhookService->delete();
        $this->assertTrue($result);

        $this->assertSame(3, $this->mockClient->getRequestCount());
    }
}
