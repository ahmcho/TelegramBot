<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\InviteLinksService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class InviteLinksServiceTest extends TestCase
{
    private InviteLinksService $inviteLinksService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->inviteLinksService = new InviteLinksService($apiService);
    }

    private function inviteLinkFixture(string $link = 'https://t.me/+abc123'): array
    {
        return [
            'invite_link' => $link,
            'creator' => ['id' => 1, 'is_bot' => false, 'first_name' => 'Admin'],
            'creates_join_request' => false,
            'is_primary' => false,
            'is_revoked' => false,
        ];
    }

    public function test_create_returns_invite_link(): void
    {
        $expected = $this->inviteLinkFixture();
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->create(['chat_id' => 123]);

        $this->assertSame($expected, $result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_create_sends_correct_params(): void
    {
        $this->mockClient->setResponse($this->inviteLinkFixture());

        $this->inviteLinksService->create([
            'chat_id' => 456,
            'name' => 'My Link',
            'member_limit' => 100,
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(456, $lastRequest['params']['chat_id']);
        $this->assertSame('My Link', $lastRequest['params']['name']);
    }

    public function test_edit_returns_updated_link(): void
    {
        $expected = $this->inviteLinkFixture('https://t.me/+updated');
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->edit([
            'chat_id' => 123,
            'invite_link' => 'https://t.me/+abc123',
            'name' => 'Updated Name',
        ]);

        $this->assertSame($expected, $result);
    }

    public function test_revoke_returns_revoked_link(): void
    {
        $expected = array_merge($this->inviteLinkFixture(), ['is_revoked' => true]);
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->revoke([
            'chat_id' => 123,
            'invite_link' => 'https://t.me/+abc123',
        ]);

        $this->assertSame($expected, $result);
        $this->assertTrue($result['is_revoked']);
    }

    public function test_export_returns_link_string(): void
    {
        $this->mockClient->setStringResponse('https://t.me/+exported');

        $result = $this->inviteLinksService->export(['chat_id' => 123]);

        $this->assertSame('https://t.me/+exported', $result);
    }

    public function test_get_returns_link_info(): void
    {
        $expected = $this->inviteLinkFixture();
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->get([
            'chat_id' => 123,
            'invite_link' => 'https://t.me/+abc123',
        ]);

        $this->assertSame($expected, $result);
    }

    public function test_getCounts_returns_stats(): void
    {
        $expected = [
            ['invite_link' => 'https://t.me/+abc123', 'member_count' => 5, 'pending_join_request_count' => 2],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->getCounts(['chat_id' => 123]);

        $this->assertSame($expected, $result);
    }

    public function test_getMembers_returns_member_list(): void
    {
        $expected = [
            'total_count' => 2,
            'members' => [
                ['user' => ['id' => 10, 'first_name' => 'Alice'], 'join_date' => 1700000000],
                ['user' => ['id' => 11, 'first_name' => 'Bob'], 'join_date' => 1700000001],
            ],
        ];
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->getMembers([
            'chat_id' => 123,
            'invite_link' => 'https://t.me/+abc123',
        ]);

        $this->assertSame(2, $result['total_count']);
        $this->assertCount(2, $result['members']);
    }

    public function test_editSubscription_returns_updated_link(): void
    {
        $expected = $this->inviteLinkFixture();
        $this->mockClient->setResponse($expected);

        $result = $this->inviteLinksService->editSubscription([
            'chat_id' => 123,
            'invite_link' => 'https://t.me/+abc123',
            'name' => 'Sub Link',
        ]);

        $this->assertSame($expected, $result);
    }
}
