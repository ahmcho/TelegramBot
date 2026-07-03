<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\PaymentsService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class PaymentsServiceTest extends TestCase
{
    private PaymentsService $paymentsService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->paymentsService = new PaymentsService($apiService);
    }

    public function test_sendInvoice_returns_message(): void
    {
        $expected = [
            'message_id' => 42,
            'chat' => ['id' => 123],
            'invoice' => ['title' => 'Test Product', 'total_amount' => 500, 'currency' => 'USD'],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->paymentsService->sendInvoice([
            'chat_id' => 123,
            'title' => 'Test Product',
            'description' => 'A product for testing',
            'payload' => 'test_payload',
            'provider_token' => 'provider_token_abc',
            'currency' => 'USD',
            'prices' => [['label' => 'Test Product', 'amount' => 500]],
        ]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_sendInvoice_supports_telegram_stars(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->paymentsService->sendInvoice([
            'chat_id' => 123,
            'title' => 'Stars Product',
            'description' => 'Paid with Telegram Stars',
            'payload' => 'stars_payload',
            'provider_token' => '',
            'currency' => 'XTR',
            'prices' => [['label' => 'Stars Product', 'amount' => 10]],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('XTR', $lastRequest['params']['currency']);
        $this->assertSame('', $lastRequest['params']['provider_token']);
    }

    public function test_sendInvoice_records_request_params(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->paymentsService->sendInvoice([
            'chat_id' => 456,
            'title' => 'Widget',
            'description' => 'A widget',
            'payload' => 'widget_payload',
            'provider_token' => 'token_xyz',
            'currency' => 'EUR',
            'prices' => [['label' => 'Widget', 'amount' => 1000]],
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(456, $lastRequest['params']['chat_id']);
        $this->assertSame('Widget', $lastRequest['params']['title']);
        $this->assertSame('widget_payload', $lastRequest['params']['payload']);
        $this->assertSame([['label' => 'Widget', 'amount' => 1000]], $lastRequest['params']['prices']);
    }
}
