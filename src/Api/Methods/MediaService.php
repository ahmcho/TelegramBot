<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Traits\MarkdownV2EscapeTrait;

/**
 * Media Service
 *
 * Handles all media-related Telegram API operations
 */
class MediaService
{
    use MarkdownV2EscapeTrait;

    public function __construct(
        private readonly ApiService $apiService
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendPhoto(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_PHOTO, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendDocument(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_DOCUMENT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendVideo(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_VIDEO, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendAudio(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_AUDIO, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendVoice(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_VOICE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendAnimation(array $params): array
    {
        $params = $this->escapeForMarkdownV2($params);
        return $this->apiService->call(ApiMethod::SEND_ANIMATION, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendSticker(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_STICKER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendLocation(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_LOCATION, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendVenue(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_VENUE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendContact(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_CONTACT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendPoll(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_POLL, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function sendDice(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_DICE, $params);
    }

    /**
     * Get information about custom emoji stickers
     *
     * @param array{custom_emoji_ids: array<string>} $params
     * @return array<string, mixed>
     */
    public function getCustomEmojiStickers(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_CUSTOM_EMOJI_STICKERS,
            $params
        );
    }

    /**
     * Send a group of photos, videos, documents, or audios as an album.
     * Returns an array of Message objects (one per media item).
     *
     * Each element of $params['media'] must be an InputMedia array:
     *   ['type' => 'photo'|'video'|'audio'|'document', 'media' => <file_id|url|CURLFile>, ...]
     *
     * Local uploads: pass a `CURLFile` as an item's `media` (or `thumbnail`) value.
     * It is extracted into a top-level `attach://` field before the request is sent,
     * since Telegram requires `media` itself to reference local files by name rather
     * than embed them inline.
     *
     * @param array{chat_id: int|string, media: array<int, array<string, mixed>>, message_thread_id?: int, disable_notification?: bool, protect_content?: bool, reply_to_message_id?: int} $params
     * @return array<int, array<string, mixed>> Array of sent Message objects
     */
    public function sendMediaGroup(array $params): array
    {
        $params = $this->prepareMediaGroupAttachments($params);
        return $this->apiService->call(ApiMethod::SEND_MEDIA_GROUP, $params);
    }

    /**
     * Extract any `CURLFile` values embedded in $params['media'] items into
     * top-level `attach://` fields, and JSON-encode the media array once any
     * local file is found (required by Telegram for multipart requests).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function prepareMediaGroupAttachments(array $params): array
    {
        if (!isset($params['media']) || !is_array($params['media'])) {
            return $params;
        }

        $hasLocalFile = false;
        $attachIndex = 0;

        foreach ($params['media'] as &$item) {
            if (!is_array($item)) {
                continue;
            }

            foreach (['media', 'thumbnail'] as $field) {
                if (isset($item[$field]) && $item[$field] instanceof \CURLFile) {
                    $attachName = 'media_attach_' . $attachIndex++;
                    $params[$attachName] = $item[$field];
                    $item[$field] = 'attach://' . $attachName;
                    $hasLocalFile = true;
                }
            }
        }
        unset($item);

        if ($hasLocalFile) {
            $params['media'] = json_encode($params['media']);
        }

        return $params;
    }

    /**
     * Get basic information about a file and prepare it for downloading.
     * The returned file_path can be passed to getFileDownloadUrl().
     *
     * @param array{file_id: string} $params
     * @return array{file_id: string, file_unique_id: string, file_size?: int, file_path?: string}
     */
    public function getFile(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_FILE, $params);
    }

    /**
     * Build the full HTTPS download URL for a file_path returned by getFile().
     *
     * @param string $filePath The file_path field from the getFile() response
     * @return string Full download URL including the bot token
     */
    public function getFileDownloadUrl(string $filePath): string
    {
        $config = $this->apiService->getConfig();
        $base = rtrim($config->getApiUrl(), '/');
        $token = $config->getToken();

        return "{$base}/file/bot{$token}/{$filePath}";
    }
}
