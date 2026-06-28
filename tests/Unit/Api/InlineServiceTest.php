<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\InlineService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

final class InlineServiceTest extends TestCase
{
    private InlineService $inlineService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->inlineService = new InlineService($apiService);
    }

    public function test_answer_returns_true(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->inlineService->answer([
            'inline_query_id' => 'query123',
            'results' => [],
        ]);

        $this->assertTrue($result);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_answer_with_results_sends_correct_params(): void
    {
        $this->mockClient->setBoolResponse(true);

        $results = [
            ['type' => 'article', 'id' => '1', 'title' => 'Test'],
        ];

        $this->inlineService->answer([
            'inline_query_id' => 'qid1',
            'results' => $results,
            'cache_time' => 300,
        ]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('qid1', $lastRequest['params']['inline_query_id']);
        $this->assertSame(300, $lastRequest['params']['cache_time']);
    }

    public function test_createArticle_returns_correct_structure(): void
    {
        $result = $this->inlineService->createArticle('id1', 'My Title', 'Message text');

        $this->assertSame('article', $result['type']);
        $this->assertSame('id1', $result['id']);
        $this->assertSame('My Title', $result['title']);
        $this->assertSame('Message text', $result['input_message_content']['message_text']);
    }

    public function test_createArticle_merges_options(): void
    {
        $result = $this->inlineService->createArticle('id2', 'Title', 'Text', [
            'description' => 'A description',
            'url' => 'https://example.com',
        ]);

        $this->assertSame('A description', $result['description']);
        $this->assertSame('https://example.com', $result['url']);
    }

    public function test_createPhoto_returns_correct_structure(): void
    {
        $result = $this->inlineService->createPhoto('pid1', 'https://example.com/photo.jpg');

        $this->assertSame('photo', $result['type']);
        $this->assertSame('pid1', $result['id']);
        $this->assertSame('https://example.com/photo.jpg', $result['photo_url']);
    }

    public function test_createVideo_returns_correct_structure(): void
    {
        $result = $this->inlineService->createVideo(
            'vid1',
            'https://example.com/video.mp4',
            'video/mp4',
            'https://example.com/thumb.jpg',
            'My Video'
        );

        $this->assertSame('video', $result['type']);
        $this->assertSame('vid1', $result['id']);
        $this->assertSame('video/mp4', $result['mime_type']);
        $this->assertSame('My Video', $result['title']);
    }

    public function test_createAudio_returns_correct_structure(): void
    {
        $result = $this->inlineService->createAudio('aid1', 'https://example.com/audio.mp3', 'My Track');

        $this->assertSame('audio', $result['type']);
        $this->assertSame('https://example.com/audio.mp3', $result['audio_url']);
        $this->assertSame('My Track', $result['title']);
    }

    public function test_createDocument_returns_correct_structure(): void
    {
        $result = $this->inlineService->createDocument(
            'did1',
            'https://example.com/doc.pdf',
            'My Doc',
            'application/pdf'
        );

        $this->assertSame('document', $result['type']);
        $this->assertSame('application/pdf', $result['mime_type']);
        $this->assertSame('My Doc', $result['title']);
    }

    public function test_createLocation_returns_correct_structure(): void
    {
        $result = $this->inlineService->createLocation('loc1', 48.8566, 2.3522, 'Paris');

        $this->assertSame('location', $result['type']);
        $this->assertSame(48.8566, $result['latitude']);
        $this->assertSame(2.3522, $result['longitude']);
        $this->assertSame('Paris', $result['title']);
    }

    public function test_createVenue_returns_correct_structure(): void
    {
        $result = $this->inlineService->createVenue('ven1', 48.8566, 2.3522, 'Eiffel Tower', 'Champ de Mars');

        $this->assertSame('venue', $result['type']);
        $this->assertSame('Eiffel Tower', $result['title']);
        $this->assertSame('Champ de Mars', $result['address']);
    }

    public function test_createContact_returns_correct_structure(): void
    {
        $result = $this->inlineService->createContact('con1', '+1234567890', 'John');

        $this->assertSame('contact', $result['type']);
        $this->assertSame('+1234567890', $result['phone_number']);
        $this->assertSame('John', $result['first_name']);
    }

    public function test_createGame_returns_correct_structure(): void
    {
        $result = $this->inlineService->createGame('gid1', 'my_game');

        $this->assertSame('game', $result['type']);
        $this->assertSame('gid1', $result['id']);
        $this->assertSame('my_game', $result['game_short_name']);
    }

    public function test_builder_methods_do_not_call_api(): void
    {
        $this->inlineService->createArticle('1', 'T', 'M');
        $this->inlineService->createPhoto('2', 'https://example.com/p.jpg');
        $this->inlineService->createGame('3', 'game');

        $this->assertSame(0, $this->mockClient->getRequestCount());
    }
}
