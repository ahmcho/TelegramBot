<?php

declare(strict_types=1);

/**
 * Media Bot Example - Modern API
 *
 * This example demonstrates media file handling including
 * sending photos, videos, audio, documents, and editing captions.
 *
 * Modern features showcased:
 * - Service-oriented API ($bot->media(), $bot->messages())
 * - Auto-escaping for captions with MarkdownV2
 * - PHP 8.1+ features (strict types, proper typing)
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

require_once __DIR__ . '/../autoload.php';

// Load environment variables (using the modern EnvLoader)
require_once __DIR__ . '/../src/Config/EnvLoader.php';

$loader = new \AhmCho\Telegram\Config\EnvLoader();
$loader->load();

// Helper function to safely extract file ID from API response
function getFileId(array $result, string $mediaType): ?string
{
    return $result[$mediaType]['file_id'] ?? null;
}

// Command handlers
function handleStart(TelegramBot $bot, int $chatId): void
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
            Button::callback('📄 Document', 'media:document'),
            Button::callback('🎮 Animation', 'media:animation')
        )
        ->addRow(
            Button::callback('📍 Location', 'media:location'),
            Button::callback('📊 Poll', 'media:poll')
        )
        ->addRow(
            Button::callback('❓ Help', 'media:help')
        );

    // Using formatter for styled text - auto-escaped!
    $text = $bot->formatter()
        ->bold('🎬 Media Bot')
        . "\n\n"
        . 'I demonstrate how to send various types of media files.'
        . "\n\n"
        . "Commands:\n"
        . "/photo - Send a photo\n"
        . "/video - Send a video\n"
        . "/audio - Send audio\n"
        . "/voice - Send a voice message\n"
        . "/document - Send a document\n"
        . "/animation - Send a GIF\n"
        . "/sticker - Send a sticker\n"
        . "/location - Send a location\n"
        . "/venue - Send a venue\n"
        . "/contact - Send a contact\n"
        . "/poll - Create a poll\n"
        . "/dice - Roll a dice\n"
        . "/action - Show typing action\n\n"
        . 'Or use the buttons below:';

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'MarkdownV2',
        'reply_markup' => $keyboard->build()
    ]);
}

function handlePhoto(TelegramBot $bot, int $chatId, bool $fromUrl = true): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_photo']);

        if ($fromUrl) {
            // Send photo from URL - caption auto-escaped!
            $result = $bot->media()->sendPhoto([
                'chat_id' => $chatId,
                'photo' => 'https://picsum.photos/800/600',
                'caption' => '📷 Beautiful photo from Lorem Picsum' . "\n\n"
                    . 'You can send photos using:' . "\n"
                    . '• URL (like this example)' . "\n"
                    . '• File ID (from previous uploads)' . "\n"
                    . '• Local file path (using CURLFile)',
                'parse_mode' => 'MarkdownV2'  // Auto-escaping enabled!
            ]);

            // Save file ID for demonstration
            $fileId = $result['photo'][0]['file_id'] ?? null;
            if ($fileId !== null) {
                $bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => '💾 File ID saved: `' . $fileId . '`' . "\n\n"
                        . 'You can use this file ID to send this photo again without re-uploading.',
                    'parse_mode' => 'MarkdownV2'
                ]);
            }
        }
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending photo: ' . $e->getMessage()
        ]);
    }
}

function handleVideo(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_video']);

        // Send video from URL - caption auto-escaped!
        $result = $bot->media()->sendVideo([
            'chat_id' => $chatId,
            'video' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'caption' => '🎥 Sample video' . "\n\nThis is a short video clip.",
            'parse_mode' => 'MarkdownV2',
            'supports_streaming' => true,
            'width' => 400,
            'height' => 300,
            'duration' => 10
        ]);

        $fileId = getFileId($result, 'video');
        if ($fileId !== null) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '💾 File ID: `' . $fileId . '`',
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending video: ' . $e->getMessage()
        ]);
    }
}

function handleAudio(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_audio']);

        // Send audio from URL - caption auto-escaped!
        $result = $bot->media()->sendAudio([
            'chat_id' => $chatId,
            'audio' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'caption' => '🎵 Sample audio file',
            'performer' => 'Unknown Artist',
            'title' => 'Sample Song'
        ]);

        $fileId = getFileId($result, 'audio');
        if ($fileId !== null) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '💾 File ID: `' . $fileId . '`',
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending audio: ' . $e->getMessage()
        ]);
    }
}

function handleVoice(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_voice']);

        // Send voice from URL (using an audio file as voice)
        $result = $bot->media()->sendVoice([
            'chat_id' => $chatId,
            'voice' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
            'caption' => '🎤 Voice message example'  // Auto-escaped!
        ]);

        $fileId = getFileId($result, 'voice');
        if ($fileId !== null) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '💾 File ID: `' . $fileId . '`',
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending voice: ' . $e->getMessage()
        ]);
    }
}

function handleDocument(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_document']);

        // Create a simple text file
        $content = "Sample Document\n\nThis is a sample document file created by the Media Bot.\n";
        $tempFile = sys_get_temp_dir() . '/sample_document.txt';
        file_put_contents($tempFile, $content);

        // Send document - caption auto-escaped!
        $result = $bot->media()->sendDocument([
            'chat_id' => $chatId,
            'document' => new CURLFile($tempFile),
            'caption' => '📄 Sample document file' . "\n\nYou can send any type of file as a document.",
            'parse_mode' => 'MarkdownV2'
        ]);

        // Clean up
        unlink($tempFile);

        $fileId = getFileId($result, 'document');
        if ($fileId !== null) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '💾 File ID: `' . $fileId . "`\n\nDocument sent successfully!",
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending document: ' . $e->getMessage()
        ]);
    }
}

function handleAnimation(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => 'upload_video']);

        // Send GIF from URL - caption auto-escaped!
        $result = $bot->media()->sendAnimation([
            'chat_id' => $chatId,
            'animation' => 'https://media.giphy.com/media/v1.Y2lkPTc5MGI3NjEx/giphy.gif', // Sample GIF URL
            'caption' => '🎮 Animation/GIF example',
            'width' => 400,
            'height' => 400
        ]);

        $fileId = getFileId($result, 'animation');
        if ($fileId !== null) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '💾 File ID: `' . $fileId . "`\n\nAnimation sent successfully!",
                'parse_mode' => 'MarkdownV2'
            ]);
        }
    } catch (\Throwable $e) {
        // Fallback if GIF URL doesn't work
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'ℹ️ Animation/GIF URLs require valid sources. Try using a direct GIF URL.'
        ]);
    }
}

function handleSticker(TelegramBot $bot, int $chatId): void
{
    try {
        // Send a sticker using a known sticker file ID
        // Note: You would typically get file IDs from previous sticker uploads
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'ℹ️ To send stickers, you need a sticker file ID.' . "\n\n"
                . 'Stickers must be uploaded through the official Telegram app or using @Stickers bot.' . "\n\n"
                . 'Once you have a sticker file ID, you can send it with:' . "\n"
                . '`$bot->media()->sendSticker([\'chat_id\' => $chatId, \'sticker\' => \'FILE_ID\']);`',
            'parse_mode' => 'MarkdownV2'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error: ' . $e->getMessage()
        ]);
    }
}

function handleLocation(TelegramBot $bot, int $chatId): void
{
    try {
        // Send a location (example: Eiffel Tower, Paris)
        $bot->media()->sendLocation([
            'chat_id' => $chatId,
            'latitude' => 48.8584,
            'longitude' => 2.2945,
            'horizontal_accuracy' => 10.0
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '📍 Location sent!' . "\n\nThis is the location of the Eiffel Tower in Paris."
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending location: ' . $e->getMessage()
        ]);
    }
}

function handleVenue(TelegramBot $bot, int $chatId): void
{
    try {
        // Send a venue
        $bot->media()->sendVenue([
            'chat_id' => $chatId,
            'latitude' => 48.8584,
            'longitude' => 2.2945,
            'title' => 'Eiffel Tower',
            'address' => 'Champ de Mars, 5 Avenue Anatole France, 75007 Paris, France'
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '🏛️ Venue sent!' . "\n\nA venue includes location, title, and address."
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending venue: ' . $e->getMessage()
        ]);
    }
}

function handleContact(TelegramBot $bot, int $chatId): void
{
    try {
        // Send a contact
        $bot->media()->sendContact([
            'chat_id' => $chatId,
            'phone_number' => '+1234567890',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '👤 Contact sent!' . "\n\nUsers can save this contact to their phone."
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending contact: ' . $e->getMessage()
        ]);
    }
}

function handlePoll(TelegramBot $bot, int $chatId): void
{
    try {
        // Create a poll
        $bot->media()->sendPoll([
            'chat_id' => $chatId,
            'question' => 'What is your favorite programming language?',
            'options' => json_encode(['PHP', 'Python', 'JavaScript', 'Java', 'C++']),
            'is_anonymous' => false,
            'allows_multiple_answers' => false
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error creating poll: ' . $e->getMessage()
        ]);
    }
}

function handleDice(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->media()->sendDice([
            'chat_id' => $chatId
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error sending dice: ' . $e->getMessage()
        ]);
    }
}

function handleChatAction(TelegramBot $bot, int $chatId): void
{
    $actions = [
        'typing' => '⌨️ Typing...',
        'upload_photo' => '📷 Uploading photo...',
        'record_video' => '🎥 Recording video...',
        'upload_video' => '🎥 Uploading video...',
        'record_audio' => '🎤 Recording audio...',
        'upload_audio' => '🎵 Uploading audio...',
        'upload_document' => '📄 Uploading document...',
        'find_location' => '📍 Finding location...',
        'record_video_note' => '📹 Recording video note...',
        'upload_video_note' => '📹 Uploading video note...'
    ];

    foreach ($actions as $action => $message) {
        try {
            $bot->chats()->sendAction(['chat_id' => $chatId, 'action' => $action]);

            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => $message
            ]);

            sleep(1);
        } catch (\Throwable $e) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '❌ Error with action \'' . $action . '\': ' . $e->getMessage()
            ]);
        }
    }

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => '✅ All chat actions demonstrated!'
    ]);
}

function handleMediaGroup(TelegramBot $bot, int $chatId): void
{
    try {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'ℹ️ Media groups allow sending multiple photos/videos as an album.' . "\n\n"
                . 'However, this requires an array of media objects with proper attachment IDs.' . "\n\n"
                . 'For simplicity, check the Telegram Bot API documentation for sendMediaGroup.'
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error: ' . $e->getMessage()
        ]);
    }
}

function handleEditCaption(TelegramBot $bot, int $chatId): void
{
    try {
        // Send a photo first
        $result = $bot->media()->sendPhoto([
            'chat_id' => $chatId,
            'photo' => 'https://picsum.photos/800/600',
            'caption' => 'Original caption'  // Auto-escaped!
        ]);

        $messageId = $result['message_id'];

        // Wait a moment
        sleep(1);

        // Edit the caption - auto-escaped!
        $bot->media()->editMessageCaption([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => '✏️ Edited caption!' . "\n\nYou can edit captions of photos, videos, documents, etc."
        ]);
    } catch (\Throwable $e) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '❌ Error editing caption: ' . $e->getMessage()
        ]);
    }
}

// Main bot loop
try {
    $bot = new TelegramBot();

    echo "Media Bot started...\n";
    echo "Press Ctrl+C to stop\n\n";

    $offset = 0;

    while (true) {
        try {
            $updates = $bot->getUpdates([
                'offset' => $offset,
                'timeout' => 30
            ]);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                // Handle callback queries
                if (isset($update['callback_query'])) {
                    $callbackQuery = $update['callback_query'];
                    $chatId = $callbackQuery['message']['chat']['id'];
                    $data = $callbackQuery['data'];
                    $queryId = $callbackQuery['id'];

                    $bot->chats()->answerCallbackQuery(['callback_query_id' => $queryId]);

                    $parts = explode(':', $data);
                    $mediaType = $parts[1] ?? '';

                    switch ($mediaType) {
                        case 'photo':
                            handlePhoto($bot, $chatId);
                            break;
                        case 'video':
                            handleVideo($bot, $chatId);
                            break;
                        case 'audio':
                            handleAudio($bot, $chatId);
                            break;
                        case 'voice':
                            handleVoice($bot, $chatId);
                            break;
                        case 'document':
                            handleDocument($bot, $chatId);
                            break;
                        case 'animation':
                            handleAnimation($bot, $chatId);
                            break;
                        case 'location':
                            handleLocation($bot, $chatId);
                            break;
                        case 'poll':
                            handlePoll($bot, $chatId);
                            break;
                        case 'help':
                            $bot->messages()->send([
                                'chat_id' => $chatId,
                                'text' => '❓ *Media Bot Help*' . "\n\n"
                                    . 'This bot demonstrates sending various media types:' . "\n\n"
                                    . '• Photos (from URL, file ID, or local file)' . "\n"
                                    . '• Videos with captions' . "\n"
                                    . '• Audio files with metadata' . "\n"
                                    . '• Voice messages' . "\n"
                                    . '• Documents (any file type)' . "\n"
                                    . '• Animations/GIFs' . "\n"
                                    . '• Stickers' . "\n"
                                    . '• Locations and venues' . "\n"
                                    . '• Contacts' . "\n"
                                    . '• Polls' . "\n"
                                    . '• Dice' . "\n"
                                    . '• Chat actions' . "\n\n"
                                    . 'All with captions and formatting!',
                                'parse_mode' => 'MarkdownV2'
                            ]);
                            break;
                    }

                    continue;
                }

                // Handle messages
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';

                    // Handle commands
                    if (strpos($text, '/') === 0) {
                        $command = explode(' ', $text)[0];

                        switch ($command) {
                            case '/start':
                                handleStart($bot, $chatId);
                                break;

                            case '/photo':
                                handlePhoto($bot, $chatId);
                                break;

                            case '/video':
                                handleVideo($bot, $chatId);
                                break;

                            case '/audio':
                                handleAudio($bot, $chatId);
                                break;

                            case '/voice':
                                handleVoice($bot, $chatId);
                                break;

                            case '/document':
                                handleDocument($bot, $chatId);
                                break;

                            case '/animation':
                                handleAnimation($bot, $chatId);
                                break;

                            case '/sticker':
                                handleSticker($bot, $chatId);
                                break;

                            case '/location':
                                handleLocation($bot, $chatId);
                                break;

                            case '/venue':
                                handleVenue($bot, $chatId);
                                break;

                            case '/contact':
                                handleContact($bot, $chatId);
                                break;

                            case '/poll':
                                handlePoll($bot, $chatId);
                                break;

                            case '/dice':
                                handleDice($bot, $chatId);
                                break;

                            case '/action':
                                handleChatAction($bot, $chatId);
                                break;

                            case '/edit':
                                handleEditCaption($bot, $chatId);
                                break;

                            case '/group':
                                handleMediaGroup($bot, $chatId);
                                break;

                            case '/help':
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => '❓ *Available Commands*' . "\n\n"
                                        . '/photo - Send a photo' . "\n"
                                        . '/video - Send a video' . "\n"
                                        . '/audio - Send audio' . "\n"
                                        . '/voice - Send a voice message' . "\n"
                                        . '/document - Send a document' . "\n"
                                        . '/animation - Send a GIF' . "\n"
                                        . '/sticker - Send a sticker' . "\n"
                                        . '/location - Send a location' . "\n"
                                        . '/venue - Send a venue' . "\n"
                                        . '/contact - Send a contact' . "\n"
                                        . '/poll - Create a poll' . "\n"
                                        . '/dice - Roll a dice' . "\n"
                                        . '/action - Show all chat actions' . "\n"
                                        . '/edit - Edit caption demo' . "\n"
                                        . '/group - Media group info' . "\n"
                                        . '/start - Show main menu',
                                    'parse_mode' => 'MarkdownV2'
                                ]);
                                break;

                            default:
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => 'Unknown command: ' . $command . "\nType /help to see available commands."
                                ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }
} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
