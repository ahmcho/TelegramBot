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
}
