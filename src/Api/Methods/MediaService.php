<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

/**
 * Media Service
 *
 * Handles all media-related Telegram API operations
 */
class MediaService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    /**
     * Auto-escape text and caption for MarkdownV2 format
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function escapeForMarkdownV2(array $params): array
    {
        if (!isset($params['parse_mode']) || $params['parse_mode'] !== 'MarkdownV2') {
            return $params;
        }

        $formatter = new MarkdownV2Formatter();

        if (isset($params['text']) && is_string($params['text'])) {
            $params['text'] = $formatter->escape($params['text']);
        }

        if (isset($params['caption']) && is_string($params['caption'])) {
            $params['caption'] = $formatter->escape($params['caption']);
        }

        return $params;
    }

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
}
