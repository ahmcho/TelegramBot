<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Inline Service
 *
 * Handles inline mode functionality for Telegram bots.
 */
class InlineService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Answer an inline query
     *
     * @param array{inline_query_id: string, results: array<array<string, mixed>>, cache_time?: int, is_personal?: bool, next_offset?: string, switch_pm_text?: string, switch_pm_parameter?: string} $params
     * @return mixed
     */
    public function answer(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::ANSWER_INLINE_QUERY,
            $params
        );
    }

    /**
     * Create an inline article result
     *
     * @param string $id Unique identifier
     * @param string $title Title of the result
     * @param string $message_text Text of the message
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createArticle(
        string $id,
        string $title,
        string $message_text,
        array $options = []
    ): array {
        return $this->buildResult('article', $id, [
            'title' => $title,
            'input_message_content' => [
                'message_text' => $message_text
            ]
        ], $options);
    }

    /**
     * Create an inline photo result
     *
     * @param string $id Unique identifier
     * @param string $photo_url URL of the photo
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createPhoto(
        string $id,
        string $photo_url,
        array $options = []
    ): array {
        return $this->buildResult('photo', $id, [
            'photo_url' => $photo_url
        ], $options);
    }

    /**
     * Create an inline video result
     *
     * @param string $id Unique identifier
     * @param string $video_url URL of the video
     * @param string $mime_type MIME type of the video
     * @param string $thumb_url URL of the thumbnail
     * @param string $title Title of the video
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createVideo(
        string $id,
        string $video_url,
        string $mime_type,
        string $thumb_url,
        string $title,
        array $options = []
    ): array {
        return $this->buildResult('video', $id, [
            'video_url' => $video_url,
            'mime_type' => $mime_type,
            'thumb_url' => $thumb_url,
            'title' => $title
        ], $options);
    }

    /**
     * Create an inline audio result
     *
     * @param string $id Unique identifier
     * @param string $audio_url URL of the audio
     * @param string $title Title of the audio
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createAudio(
        string $id,
        string $audio_url,
        string $title,
        array $options = []
    ): array {
        return $this->buildResult('audio', $id, [
            'audio_url' => $audio_url,
            'title' => $title
        ], $options);
    }

    /**
     * Create an inline document result
     *
     * @param string $id Unique identifier
     * @param string $document_url URL of the document
     * @param string $title Title of the document
     * @param string $mime_type MIME type of the document
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createDocument(
        string $id,
        string $document_url,
        string $title,
        string $mime_type,
        array $options = []
    ): array {
        return $this->buildResult('document', $id, [
            'document_url' => $document_url,
            'title' => $title,
            'mime_type' => $mime_type
        ], $options);
    }

    /**
     * Create an inline location result
     *
     * @param string $id Unique identifier
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $title Location title
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createLocation(
        string $id,
        float $latitude,
        float $longitude,
        string $title,
        array $options = []
    ): array {
        return $this->buildResult('location', $id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'title' => $title
        ], $options);
    }

    /**
     * Create an inline venue result
     *
     * @param string $id Unique identifier
     * @param float $latitude Latitude
     * @param float $longitude Longitude
     * @param string $title Venue name
     * @param string $address Venue address
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createVenue(
        string $id,
        float $latitude,
        float $longitude,
        string $title,
        string $address,
        array $options = []
    ): array {
        return $this->buildResult('venue', $id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'title' => $title,
            'address' => $address
        ], $options);
    }

    /**
     * Create an inline contact result
     *
     * @param string $id Unique identifier
     * @param string $phone_number Contact phone number
     * @param string $first_name Contact first name
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createContact(
        string $id,
        string $phone_number,
        string $first_name,
        array $options = []
    ): array {
        return $this->buildResult('contact', $id, [
            'phone_number' => $phone_number,
            'first_name' => $first_name
        ], $options);
    }

    /**
     * Create an inline game result
     *
     * @param string $id Unique identifier
     * @param string $game_short_name Short name of the game
     * @param array<string, mixed> $options Additional options
     * @return array<string, mixed>
     */
    public function createGame(
        string $id,
        string $game_short_name,
        array $options = []
    ): array {
        return $this->buildResult('game', $id, [
            'game_short_name' => $game_short_name
        ], $options);
    }

    /**
     * Shape a single inline query result: type + id + type-specific fields,
     * with caller-supplied options merged last so they can override defaults.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildResult(string $type, string $id, array $fields, array $options): array
    {
        return array_merge([
            'type' => $type,
            'id' => $id,
        ], $fields, $options);
    }
}
