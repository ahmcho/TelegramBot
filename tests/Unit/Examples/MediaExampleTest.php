<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ChatAction;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/media.php
 *
 * Verifies the media handling patterns demonstrated in the example:
 * - Send photo by URL, file ID, local file
 * - Send video, audio, voice, document, animation, sticker
 * - Send location, venue, contact
 * - Send poll, dice
 * - Edit message caption with auto-escaping
 * - Send chat action (typing)
 * - Inline keyboard navigation for media types
 */
final class MediaExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_send_photo_by_url(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'photo' => [['file_id' => 'generated_id']],
        ]);

        $result = $this->bot->media()->sendPhoto([
            'chat_id' => 100,
            'photo' => 'https://via.placeholder.com/300x300.png',
            'caption' => 'A sample photo',
        ]);

        $this->assertArrayHasKey('message_id', $result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://via.placeholder.com/300x300.png', $request['params']['photo']);
        $this->assertSame('A sample photo', $request['params']['caption']);
    }

    public function test_send_photo_by_file_id(): void
    {
        $this->mockClient->setResponse(['message_id' => 2, 'photo' => [['file_id' => 'existing_id']]]);

        $this->bot->media()->sendPhoto([
            'chat_id' => 100,
            'photo' => 'existing_file_id_123',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('existing_file_id_123', $request['params']['photo']);
    }

    public function test_send_video_by_url(): void
    {
        $this->mockClient->setResponse(['message_id' => 3]);

        $this->bot->media()->sendVideo([
            'chat_id' => 100,
            'video' => 'https://example.com/video.mp4',
            'caption' => 'Sample video',
            'width' => 1280,
            'height' => 720,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://example.com/video.mp4', $request['params']['video']);
        $this->assertSame(1280, $request['params']['width']);
        $this->assertSame(720, $request['params']['height']);
    }

    public function test_send_audio(): void
    {
        $this->mockClient->setResponse(['message_id' => 4]);

        $this->bot->media()->sendAudio([
            'chat_id' => 100,
            'audio' => 'audio_file_id_abc',
            'caption' => 'Some music',
            'title' => 'My Track',
            'performer' => 'Artist Name',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('audio_file_id_abc', $request['params']['audio']);
        $this->assertSame('My Track', $request['params']['title']);
    }

    public function test_send_voice(): void
    {
        $this->mockClient->setResponse(['message_id' => 5]);

        $this->bot->media()->sendVoice([
            'chat_id' => 100,
            'voice' => 'voice_file_id_xyz',
            'duration' => 30,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('voice_file_id_xyz', $request['params']['voice']);
        $this->assertSame(30, $request['params']['duration']);
    }

    public function test_send_document(): void
    {
        $this->mockClient->setResponse(['message_id' => 6]);

        $this->bot->media()->sendDocument([
            'chat_id' => 100,
            'document' => 'https://example.com/report.pdf',
            'caption' => 'Monthly report',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://example.com/report.pdf', $request['params']['document']);
    }

    public function test_send_animation(): void
    {
        $this->mockClient->setResponse(['message_id' => 7]);

        $this->bot->media()->sendAnimation([
            'chat_id' => 100,
            'animation' => 'animation_file_id',
            'caption' => 'Funny GIF',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('animation_file_id', $request['params']['animation']);
    }

    public function test_send_sticker(): void
    {
        $this->mockClient->setResponse(['message_id' => 8]);

        $this->bot->media()->sendSticker([
            'chat_id' => 100,
            'sticker' => 'sticker_file_id_abc',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('sticker_file_id_abc', $request['params']['sticker']);
    }

    public function test_send_location(): void
    {
        $this->mockClient->setResponse(['message_id' => 9]);

        $this->bot->media()->sendLocation([
            'chat_id' => 100,
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame(48.8566, $request['params']['latitude']);
        $this->assertSame(2.3522, $request['params']['longitude']);
    }

    public function test_send_venue(): void
    {
        $this->mockClient->setResponse(['message_id' => 10]);

        $this->bot->media()->sendVenue([
            'chat_id' => 100,
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'title' => 'London Eye',
            'address' => 'Lambeth, London',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('London Eye', $request['params']['title']);
        $this->assertSame('Lambeth, London', $request['params']['address']);
    }

    public function test_send_contact(): void
    {
        $this->mockClient->setResponse(['message_id' => 11]);

        $this->bot->media()->sendContact([
            'chat_id' => 100,
            'phone_number' => '+1-555-123-4567',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('+1-555-123-4567', $request['params']['phone_number']);
        $this->assertSame('John', $request['params']['first_name']);
    }

    public function test_send_poll(): void
    {
        $this->mockClient->setResponse(['message_id' => 12, 'poll' => ['id' => 'poll1']]);

        $this->bot->polls()->send([
            'chat_id' => 100,
            'question' => 'What is your favorite color?',
            'options' => ['Red', 'Blue', 'Green'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('What is your favorite color?', $request['params']['question']);
        $this->assertCount(3, $request['params']['options']);
    }

    public function test_send_dice(): void
    {
        $this->mockClient->setResponse(['message_id' => 13, 'dice' => ['value' => 4]]);

        $this->bot->media()->sendDice([
            'chat_id' => 100,
            'emoji' => '🎲',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('🎲', $request['params']['emoji']);
    }

    public function test_edit_caption_with_auto_escaping(): void
    {
        $this->mockClient->setResponse(['message_id' => 14]);

        $this->bot->messages()->editCaption([
            'chat_id' => 100,
            'message_id' => 14,
            'caption' => 'Updated caption! Great deal.',
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $caption = $request['params']['caption'];
        $this->assertStringContainsString('\!', $caption);
        $this->assertStringContainsString('\.', $caption);
    }

    public function test_media_keyboard_builder_inline_buttons(): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('📷 Photo', 'media:photo'),
                Button::callback('🎥 Video', 'media:video')
            )
            ->addRow(
                Button::callback('🎵 Audio', 'media:audio'),
                Button::callback('🎤 Voice', 'media:voice')
            )
            ->addRow(
                Button::callback('❓ Help', 'media:help')
            )
            ->toArray();

        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $this->assertCount(3, $keyboard['inline_keyboard']);

        $firstRow = $keyboard['inline_keyboard'][0];
        $this->assertSame('media:photo', $firstRow[0]['callback_data']);
        $this->assertSame('media:video', $firstRow[1]['callback_data']);
    }

    public function test_chat_action_enum_typing_value(): void
    {
        $this->assertSame('typing', ChatAction::TYPING->value);
    }
}
