<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Api\Methods\MediaService;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bulk\BulkOperationManager;

/**
 * Media Service Tests
 *
 * Tests all media sending operations with MarkdownV2 auto-escaping
 */
final class MediaServiceTest extends TestCase
{
    private MediaService $mediaService;
    private MockHttpClient $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig('test_token');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->mediaService = new MediaService($apiService);
    }

    public function test_sendPhoto_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 123,
            'photo' => [
                ['file_id' => 'abc123', 'file_size' => 1234]
            ]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendPhoto([
            'chat_id' => 123456789,
            'photo' => 'https://example.com/photo.jpg'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertSame(123, $result['message_id']);
    }

    public function test_sendPhoto_with_markdown_v2_escapes_caption(): void
    {
        $expectedResponse = [
            'message_id' => 123,
            'photo' => [['file_id' => 'abc123']]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $this->mediaService->sendPhoto([
            'chat_id' => 123456789,
            'photo' => 'https://example.com/photo.jpg',
            'caption' => 'Hello_world',
            'parse_mode' => 'MarkdownV2'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Hello\_world', $request['params']['caption']);
    }

    public function test_sendDocument_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 124,
            'document' => ['file_id' => 'def456', 'file_name' => 'test.pdf']
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendDocument([
            'chat_id' => 123456789,
            'document' => 'https://example.com/doc.pdf'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendVideo_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 125,
            'video' => ['file_id' => 'ghi789', 'duration' => 30]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendVideo([
            'chat_id' => 123456789,
            'video' => 'https://example.com/video.mp4'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendVideo_with_markdown_v2_escapes_caption(): void
    {
        $expectedResponse = ['message_id' => 125, 'video' => ['file_id' => 'ghi789']];
        $this->mockClient->setResponse($expectedResponse);

        $this->mediaService->sendVideo([
            'chat_id' => 123456789,
            'video' => 'https://example.com/video.mp4',
            'caption' => 'Test*caption',
            'parse_mode' => 'MarkdownV2'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Test\*caption', $request['params']['caption']);
    }

    public function test_sendAudio_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 126,
            'audio' => ['file_id' => 'jkl012', 'duration' => 180]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendAudio([
            'chat_id' => 123456789,
            'audio' => 'https://example.com/audio.mp3'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendVoice_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 127,
            'voice' => ['file_id' => 'mno345', 'duration' => 15]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendVoice([
            'chat_id' => 123456789,
            'voice' => 'https://example.com/voice.ogg'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendAnimation_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 128,
            'animation' => ['file_id' => 'pqr678', 'duration' => 2]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendAnimation([
            'chat_id' => 123456789,
            'animation' => 'https://example.com/anim.gif'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendSticker_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 129,
            'sticker' => ['file_id' => 'stu901', 'width' => 512, 'height' => 512]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendSticker([
            'chat_id' => 123456789,
            'sticker' => 'https://example.com/sticker.webp'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendLocation_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 130,
            'location' => ['latitude' => 51.5074, 'longitude' => -0.1278]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendLocation([
            'chat_id' => 123456789,
            'latitude' => 51.5074,
            'longitude' => -0.1278
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendVenue_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 131,
            'venue' => [
                'location' => ['latitude' => 51.5074, 'longitude' => -0.1278],
                'title' => 'Test Venue',
                'address' => '123 Test St'
            ]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendVenue([
            'chat_id' => 123456789,
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'title' => 'Test Venue',
            'address' => '123 Test St'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendContact_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 132,
            'contact' => [
                'phone_number' => '+1234567890',
                'first_name' => 'John'
            ]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendContact([
            'chat_id' => 123456789,
            'phone_number' => '+1234567890',
            'first_name' => 'John'
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendPoll_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 133,
            'poll' => [
                'id' => 'poll123',
                'question' => 'Test Question?',
                'options' => [
                    ['text' => 'Option 1', 'voter_count' => 0],
                    ['text' => 'Option 2', 'voter_count' => 0]
                ]
            ]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendPoll([
            'chat_id' => 123456789,
            'question' => 'Test Question?',
            'options' => ['Option 1', 'Option 2']
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendDice_returns_message_object(): void
    {
        $expectedResponse = [
            'message_id' => 134,
            'dice' => ['value' => 5]
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendDice([
            'chat_id' => 123456789
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
    }

    public function test_sendDocument_with_markdown_v2_escapes_text(): void
    {
        $expectedResponse = ['message_id' => 124, 'document' => ['file_id' => 'def456']];
        $this->mockClient->setResponse($expectedResponse);

        $this->mediaService->sendDocument([
            'chat_id' => 123456789,
            'document' => 'https://example.com/doc.pdf',
            'caption' => 'Document_with_underscore',
            'parse_mode' => 'MarkdownV2'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Document\_with\_underscore', $request['params']['caption']);
    }

    public function test_sendAudio_with_html_parse_mode_does_not_escape(): void
    {
        $expectedResponse = ['message_id' => 126, 'audio' => ['file_id' => 'jkl012']];
        $this->mockClient->setResponse($expectedResponse);

        $this->mediaService->sendAudio([
            'chat_id' => 123456789,
            'audio' => 'https://example.com/audio.mp3',
            'caption' => 'Audio_with_special_chars',
            'parse_mode' => 'HTML'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Audio_with_special_chars', $request['params']['caption']);
    }

    public function test_getFile_returns_file_object(): void
    {
        $expectedResponse = [
            'file_id' => 'BQACAgIAAxkBAAIBY2',
            'file_unique_id' => 'AQADkgADhZ4xS3I',
            'file_size' => 14253,
            'file_path' => 'documents/file_0.pdf',
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->getFile(['file_id' => 'BQACAgIAAxkBAAIBY2']);

        $this->assertSame($expectedResponse, $result);
        $this->assertArrayHasKey('file_path', $result);
        $this->assertSame('documents/file_0.pdf', $result['file_path']);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame('BQACAgIAAxkBAAIBY2', $lastRequest['params']['file_id']);
    }

    public function test_getFile_passes_file_id_to_api(): void
    {
        $this->mockClient->setResponse(['file_id' => 'abc', 'file_unique_id' => 'xyz']);

        $this->mediaService->getFile(['file_id' => 'abc']);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('getFile', $lastRequest['url']);
        $this->assertSame('abc', $lastRequest['params']['file_id']);
    }

    public function test_getFileDownloadUrl_builds_correct_url(): void
    {
        $url = $this->mediaService->getFileDownloadUrl('photos/file_17.jpg');

        $this->assertSame(
            'https://api.telegram.org/file/bottest_token/photos/file_17.jpg',
            $url
        );
    }

    public function test_getFileDownloadUrl_includes_token(): void
    {
        $url = $this->mediaService->getFileDownloadUrl('voice/file_0.oga');

        $this->assertStringContainsString('test_token', $url);
        $this->assertStringContainsString('voice/file_0.oga', $url);
    }

    public function test_getFileDownloadUrl_with_custom_api_url(): void
    {
        $config = new BotConfig('mytoken', apiUrl: 'https://custom.tg-api.example.com/');
        $bulkManager = new BulkOperationManager($this->mockClient, $config);
        $apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $mediaService = new MediaService($apiService);

        $url = $mediaService->getFileDownloadUrl('documents/report.pdf');

        $this->assertSame(
            'https://custom.tg-api.example.com/file/botmytoken/documents/report.pdf',
            $url
        );
    }

    public function test_sendMediaGroup_returns_array_of_messages(): void
    {
        $expectedResponse = [
            ['message_id' => 200, 'photo' => [['file_id' => 'ph1']]],
            ['message_id' => 201, 'photo' => [['file_id' => 'ph2']]],
        ];
        $this->mockClient->setResponse($expectedResponse);

        $result = $this->mediaService->sendMediaGroup([
            'chat_id' => 123,
            'media' => [
                ['type' => 'photo', 'media' => 'file_id_1'],
                ['type' => 'photo', 'media' => 'file_id_2'],
            ],
        ]);

        $this->assertSame($expectedResponse, $result);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_sendMediaGroup_passes_media_array_to_api(): void
    {
        $this->mockClient->setResponse([['message_id' => 300]]);

        $media = [
            ['type' => 'photo',    'media' => 'photo_file_id',    'caption' => 'First'],
            ['type' => 'video',    'media' => 'video_file_id',    'width' => 1280, 'height' => 720],
            ['type' => 'document', 'media' => 'document_file_id'],
        ];

        $this->mediaService->sendMediaGroup(['chat_id' => 456, 'media' => $media]);

        $lastRequest = $this->mockClient->getLastRequest();
        $this->assertSame(456, $lastRequest['params']['chat_id']);
        $this->assertSame($media, $lastRequest['params']['media']);
        $this->assertStringContainsString('sendMediaGroup', $lastRequest['url']);
    }

    public function test_sendMediaGroup_with_mixed_types(): void
    {
        $this->mockClient->setResponse([
            ['message_id' => 301, 'photo' => []],
            ['message_id' => 302, 'video' => []],
            ['message_id' => 303, 'audio' => []],
        ]);

        $result = $this->mediaService->sendMediaGroup([
            'chat_id' => 789,
            'media' => [
                ['type' => 'photo', 'media' => 'ph_id'],
                ['type' => 'video', 'media' => 'vid_id'],
                ['type' => 'audio', 'media' => 'aud_id', 'title' => 'Track', 'performer' => 'Artist'],
            ],
        ]);

        $this->assertCount(3, $result);
        $this->assertSame(301, $result[0]['message_id']);
        $this->assertSame(302, $result[1]['message_id']);
        $this->assertSame(303, $result[2]['message_id']);
    }
}
