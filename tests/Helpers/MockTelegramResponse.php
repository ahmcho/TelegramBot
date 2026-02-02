<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Helpers;

/**
 * Mock Telegram Response Builder
 *
 * Builds valid Telegram API response structures for testing.
 * Supports both success and error responses.
 */
class MockTelegramResponse
{
    /**
     * Create a successful API response
     *
     * @param array<string, mixed>|bool $result The result data
     * @return array<string, mixed> Complete API response structure
     */
    public static function success(array|bool $result = true): array
    {
        return [
            'ok' => true,
            'result' => $result
        ];
    }

    /**
     * Create an error API response
     *
     * @param string $description Error description
     * @param int $error_code Telegram error code
     * @return array<string, mixed> Complete API error response structure
     */
    public static function error(string $description, int $error_code = 400): array
    {
        return [
            'ok' => false,
            'error_code' => $error_code,
            'description' => $description
        ];
    }

    /**
     * Create a sendMessage response
     *
     * @param int $message_id The message ID
     * @param int $chat_id The chat ID
     * @param string $text The message text
     * @param int|null $date Unix timestamp
     * @return array<string, mixed>
     */
    public static function sendMessage(
        int $message_id,
        int $chat_id,
        string $text = 'Test message',
        ?int $date = null
    ): array {
        return self::success([
            'message_id' => $message_id,
            'from' => self::user(123456789, 'TestBot', true),
            'chat' => self::chat($chat_id, 'private'),
            'date' => $date ?? time(),
            'text' => $text
        ]);
    }

    /**
     * Create a getMe response
     *
     * @param int $id Bot user ID
     * @param string $first_name Bot first name
     * @param string $username Bot username
     * @param bool $is_bot Whether it's a bot
     * @return array<string, mixed>
     */
    public static function getMe(
        int $id = 123456789,
        string $first_name = 'TestBot',
        string $username = 'test_bot',
        bool $is_bot = true
    ): array {
        return self::success(self::user($id, $first_name, $is_bot, $username));
    }

    /**
     * Create a getChat response
     *
     * @param int|string $id Chat ID
     * @param string $type Chat type ('private', 'group', 'supergroup', 'channel')
     * @param string|null $title Chat title (for groups/channels)
     * @return array<string, mixed>
     */
    public static function getChat(
        int|string $id,
        string $type = 'private',
        ?string $title = null
    ): array {
        return self::success(self::chat($id, $type, $title));
    }

    /**
     * Create a webhook info response
     *
     * @param string|null $url Webhook URL
     * @param bool $has_custom_certificate
     * @param int $pending_update_count
     * @return array<string, mixed>
     */
    public static function webhookInfo(
        ?string $url = null,
        bool $has_custom_certificate = false,
        int $pending_update_count = 0
    ): array {
        $data = [
            'url' => $url,
            'has_custom_certificate' => $has_custom_certificate,
            'pending_update_count' => $pending_update_count
        ];

        return self::success($data);
    }

    /**
     * Create a user object
     *
     * @param int $id User ID
     * @param string $first_name First name
     * @param bool $is_bot Whether it's a bot
     * @param string|null $username Username
     * @param string|null $last_name Last name
     * @param string|null $language_code Language code
     * @return array<string, mixed>
     */
    public static function user(
        int $id,
        string $first_name = 'Test',
        bool $is_bot = false,
        ?string $username = null,
        ?string $last_name = null,
        ?string $language_code = 'en'
    ): array {
        $user = [
            'id' => $id,
            'is_bot' => $is_bot,
            'first_name' => $first_name
        ];

        if ($username !== null) {
            $user['username'] = $username;
        }

        if ($last_name !== null) {
            $user['last_name'] = $last_name;
        }

        if ($language_code !== null) {
            $user['language_code'] = $language_code;
        }

        return $user;
    }

    /**
     * Create a chat object
     *
     * @param int|string $id Chat ID
     * @param string $type Chat type
     * @param string|null $title Chat title (for groups/channels)
     * @param string|null $username Chat username
     * @return array<string, mixed>
     */
    public static function chat(
        int|string $id,
        string $type = 'private',
        ?string $title = null,
        ?string $username = null
    ): array {
        $chat = [
            'id' => $id,
            'type' => $type
        ];

        if ($title !== null) {
            $chat['title'] = $title;
        }

        if ($username !== null) {
            $chat['username'] = $username;
        }

        return $chat;
    }

    /**
     * Create an update object
     *
     * @param int $update_id Update ID
     * @param array<string, mixed>|null $message Message object
     * @param array<string, mixed>|null $callback_query Callback query object
     * @param array<string, mixed>|null $inline_query Inline query object
     * @return array<string, mixed>
     */
    public static function update(
        int $update_id,
        ?array $message = null,
        ?array $callback_query = null,
        ?array $inline_query = null
    ): array {
        $update = ['update_id' => $update_id];

        if ($message !== null) {
            $update['message'] = $message;
        }

        if ($callback_query !== null) {
            $update['callback_query'] = $callback_query;
        }

        if ($inline_query !== null) {
            $update['inline_query'] = $inline_query;
        }

        return $update;
    }

    /**
     * Create a message object
     *
     * @param int $message_id Message ID
     * @param int $from_id Sender user ID
     * @param int $chat_id Chat ID
     * @param string|null $text Message text
     * @param int|null $date Unix timestamp
     * @return array<string, mixed>
     */
    public static function message(
        int $message_id,
        int $from_id,
        int $chat_id,
        ?string $text = null,
        ?int $date = null
    ): array {
        $message = [
            'message_id' => $message_id,
            'from' => self::user($from_id),
            'chat' => self::chat($chat_id),
            'date' => $date ?? time()
        ];

        if ($text !== null) {
            $message['text'] = $text;
        }

        return $message;
    }

    /**
     * Create a callback query object
     *
     * @param string $id Callback query ID
     * @param int $from_id User who sent the callback
     * @param array<string, mixed> $message Message with the callback button
     * @param string $data Callback data
     * @return array<string, mixed>
     */
    public static function callbackQuery(
        string $id,
        int $from_id,
        array $message,
        string $data
    ): array {
        return [
            'id' => $id,
            'from' => self::user($from_id),
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Create an inline query object
     *
     * @param string $id Inline query ID
     * @param int $from_id User who sent the query
     * @param string $query Query text
     * @param string $offset Offset
     * @return array<string, mixed>
     */
    public static function inlineQuery(
        string $id,
        int $from_id,
        string $query,
        string $offset = ''
    ): array {
        return [
            'id' => $id,
            'from' => self::user($from_id),
            'query' => $query,
            'offset' => $offset
        ];
    }

    /**
     * Create a photo response (sendPhoto)
     *
     * @param int $message_id Message ID
     * @param int $chat_id Chat ID
     * @param array<string, mixed> $photo_sizes Photo sizes array
     * @param string|null $caption Photo caption
     * @return array<string, mixed>
     */
    public static function photo(
        int $message_id,
        int $chat_id,
        array $photo_sizes = [],
        ?string $caption = null
    ): array {
        if (empty($photo_sizes)) {
            $photo_sizes = [
                [
                    'file_id' => 'AgADbqwAAg6l8Ek',
                    'file_size' => 1188,
                    'width' => 90,
                    'height' => 67
                ],
                [
                    'file_id' => 'AgADbqwAAg6l8Ek',
                    'file_size' => 18824,
                    'width' => 320,
                    'height' => 240
                ]
            ];
        }

        $data = [
            'message_id' => $message_id,
            'from' => self::user(123456789, 'TestBot', true),
            'chat' => self::chat($chat_id, 'private'),
            'date' => time(),
            'photo' => $photo_sizes
        ];

        if ($caption !== null) {
            $data['caption'] = $caption;
        }

        return self::success($data);
    }

    /**
     * Create a dice response (sendDice)
     *
     * @param int $message_id Message ID
     * @param int $chat_id Chat ID
     * @param int $value Dice value (1-6)
     * @param string $emoji Dice emoji
     * @return array<string, mixed>
     */
    public static function dice(
        int $message_id,
        int $chat_id,
        int $value = 3,
        string $emoji = '🎲'
    ): array {
        return self::success([
            'message_id' => $message_id,
            'from' => self::user(123456789, 'TestBot', true),
            'chat' => self::chat($chat_id, 'private'),
            'date' => time(),
            'dice' => [
                'emoji' => $emoji,
                'value' => $value
            ]
        ]);
    }

    /**
     * Create a bulk result for testing multiple responses
     *
     * @param array<int, array<string, mixed>> $results Individual results
     * @return array<int, array<string, mixed>>
     */
    public static function bulkResults(array $results): array
    {
        return $results;
    }

    /**
     * Common error responses
     */

    public static function errorBadRequest(string $description = 'Bad request: wrong file identifier'): array
    {
        return self::error($description, 400);
    }

    public static function errorUnauthorized(string $description = 'Unauthorized'): array
    {
        return self::error($description, 401);
    }

    public static function errorForbidden(string $description = 'Forbidden: bot was blocked by the user'): array
    {
        return self::error($description, 403);
    }

    public static function errorNotFound(string $description = 'Not Found'): array
    {
        return self::error($description, 404);
    }

    public static function errorTooManyRequests(string $description = 'Too many requests'): array
    {
        return self::error($description, 429);
    }

    public static function errorInternal(string $description = 'Internal server error'): array
    {
        return self::error($description, 500);
    }
}
