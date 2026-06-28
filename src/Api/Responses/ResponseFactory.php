<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Responses;

use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Response Factory
 *
 * Creates typed response objects from raw API responses
 */
class ResponseFactory
{
    /**
     * Map of API methods to their response types
     *
     * @var array<string, class-string<ApiResponse>>
     */
    private static array $typeMap = [
        ApiMethod::SEND_MESSAGE->value => MessageResponse::class,
        ApiMethod::EDIT_MESSAGE_TEXT->value => MessageResponse::class,
        ApiMethod::EDIT_MESSAGE_CAPTION->value => MessageResponse::class,
        ApiMethod::SEND_PHOTO->value => MessageResponse::class,
        ApiMethod::SEND_DOCUMENT->value => MessageResponse::class,
        ApiMethod::SEND_VIDEO->value => MessageResponse::class,
        ApiMethod::SEND_AUDIO->value => MessageResponse::class,
        ApiMethod::SEND_VOICE->value => MessageResponse::class,
        ApiMethod::SEND_ANIMATION->value => MessageResponse::class,
        ApiMethod::SEND_STICKER->value => MessageResponse::class,
        ApiMethod::SEND_LOCATION->value => MessageResponse::class,
        ApiMethod::SEND_VENUE->value => MessageResponse::class,
        ApiMethod::SEND_CONTACT->value => MessageResponse::class,
        ApiMethod::SEND_POLL->value => MessageResponse::class,
        ApiMethod::GET_ME->value => UserResponse::class,
        ApiMethod::GET_CHAT->value => ChatResponse::class,
    ];

    /**
     * Create a typed response from raw API data
     *
     * @param ApiMethod $method
     * @param array<string, mixed> $data
     * @return ApiResponse
     */
    public static function create(ApiMethod $method, array $data): ApiResponse
    {
        $type = self::$typeMap[$method->value] ?? ApiResponse::class;

        // Use a generic response if no specific type is defined
        if ($type === ApiResponse::class) {
            return new class($data) extends ApiResponse {};
        }

        return new $type($data);
    }

    /**
     * Register a custom response type for an API method
     *
     * @param ApiMethod $method
     * @param class-string<ApiResponse> $type
     * @return void
     */
    public static function registerType(ApiMethod $method, string $type): void
    {
        self::$typeMap[$method->value] = $type;
    }

    /**
     * Check if a response type is registered for a method
     *
     * @param ApiMethod $method
     * @return bool
     */
    public static function hasType(ApiMethod $method): bool
    {
        return isset(self::$typeMap[$method->value]);
    }
}
