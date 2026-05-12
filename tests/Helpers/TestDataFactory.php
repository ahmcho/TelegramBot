<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Helpers;

use AhmCho\Telegram\Enums\ParseMode;

/**
 * Test Data Factory
 *
 * Generates test data for messages, updates, users, and other Telegram entities.
 * Provides consistent test data across all test classes.
 */
class TestDataFactory
{
    /**
     * Create a valid Telegram update with message
     *
     * @param int $update_id Update ID
     * @param int $message_id Message ID
     * @param int $chat_id Chat ID
     * @param int $user_id User ID
     * @param string $text Message text
     * @return array<string, mixed>
     */
    public static function createMessageUpdate(
        int $update_id = 1,
        int $message_id = 100,
        int $chat_id = 123456789,
        int $user_id = 987654321,
        string $text = 'Test message'
    ): array {
        return MockTelegramResponse::update(
            $update_id,
            MockTelegramResponse::message($message_id, $user_id, $chat_id, $text)
        );
    }

    /**
     * Create a valid Telegram update with callback query
     *
     * @param int $update_id Update ID
     * @param string $callback_query_id Callback query ID
     * @param int $message_id Message ID
     * @param int $chat_id Chat ID
     * @param int $user_id User ID
     * @param string $data Callback data
     * @return array<string, mixed>
     */
    public static function createCallbackQueryUpdate(
        int $update_id = 1,
        string $callback_query_id = 'callback_123',
        int $message_id = 100,
        int $chat_id = 123456789,
        int $user_id = 987654321,
        string $data = 'button_click'
    ): array {
        $message = MockTelegramResponse::message($message_id, $user_id, $chat_id);

        return MockTelegramResponse::update(
            $update_id,
            callback_query: MockTelegramResponse::callbackQuery(
                $callback_query_id,
                $user_id,
                $message,
                $data
            )
        );
    }

    /**
     * Create a valid Telegram update with inline query
     *
     * @param int $update_id Update ID
     * @param string $inline_query_id Inline query ID
     * @param int $user_id User ID
     * @param string $query Query text
     * @return array<string, mixed>
     */
    public static function createInlineQueryUpdate(
        int $update_id = 1,
        string $inline_query_id = 'inline_123',
        int $user_id = 987654321,
        string $query = 'search query'
    ): array {
        return MockTelegramResponse::update(
            $update_id,
            inline_query: MockTelegramResponse::inlineQuery(
                $inline_query_id,
                $user_id,
                $query
            )
        );
    }

    /**
     * Create a user object
     *
     * @param int $id User ID
     * @param string $first_name First name
     * @param string|null $username Username
     * @param bool $is_bot Whether it's a bot
     * @return array<string, mixed>
     */
    public static function createUser(
        int $id = 987654321,
        string $first_name = 'Test',
        ?string $username = 'testuser',
        bool $is_bot = false
    ): array {
        return MockTelegramResponse::user($id, $first_name, $is_bot, $username);
    }

    /**
     * Create a chat object
     *
     * @param int $id Chat ID
     * @param string $type Chat type
     * @return array<string, mixed>
     */
    public static function createChat(
        int $id = 123456789,
        string $type = 'private'
    ): array {
        return MockTelegramResponse::chat($id, $type);
    }

    /**
     * Create a message object
     *
     * @param int $id Message ID
     * @param int $from_id Sender ID
     * @param int $chat_id Chat ID
     * @param string $text Message text
     * @return array<string, mixed>
     */
    public static function createMessage(
        int $id = 100,
        int $from_id = 987654321,
        int $chat_id = 123456789,
        string $text = 'Test message'
    ): array {
        return MockTelegramResponse::message($id, $from_id, $chat_id, $text);
    }

    /**
     * Create parameters for sendMessage
     *
     * @param int|string $chat_id Chat ID
     * @param string $text Message text
     * @param ParseMode|null $parse_mode Parse mode
     * @param array<string, mixed> $additional_params Additional parameters
     * @return array<string, mixed>
     */
    public static function createMessageParams(
        int|string $chat_id = 123456789,
        string $text = 'Test message',
        ?ParseMode $parse_mode = null,
        array $additional_params = []
    ): array {
        $params = [
            'chat_id' => $chat_id,
            'text' => $text
        ];

        if ($parse_mode !== null) {
            $params['parse_mode'] = $parse_mode->value;
        }

        return array_merge($params, $additional_params);
    }

    /**
     * Create multiple message parameters for bulk operations
     *
     * @param int $count Number of parameters to create
     * @param array<string, mixed> $override_params Parameters to override defaults
     * @return array<int, array<string, mixed>>
     */
    public static function createBulkMessageParams(
        int $count,
        array $override_params = []
    ): array {
        $paramsArray = [];

        for ($i = 0; $i < $count; $i++) {
            $paramsArray[] = self::createMessageParams(
                $i + 1,
                "Bulk message $i",
                null,
                $override_params
            );
        }

        return $paramsArray;
    }

    /**
     * Create inline keyboard data
     *
     * @param array<int, array<int, array<string, mixed>>> $keyboard Keyboard structure
     * @return array<string, mixed>
     */
    public static function createInlineKeyboard(array $keyboard = []): array
    {
        if (empty($keyboard)) {
            $keyboard = [
                [
                    ['text' => 'Button 1', 'callback_data' => 'data1'],
                    ['text' => 'Button 2', 'callback_data' => 'data2']
                ],
                [
                    ['text' => 'Button 3', 'url' => 'https://example.com']
                ]
            ];
        }

        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Create reply keyboard data
     *
     * @param array<int, array<int, array<string, mixed>>> $keyboard Keyboard structure
     * @param bool $resize_keyboard
     * @param bool $one_time_keyboard
     * @return array<string, mixed>
     */
    public static function createReplyKeyboard(
        array $keyboard = [],
        bool $resize_keyboard = true,
        bool $one_time_keyboard = false
    ): array {
        if (empty($keyboard)) {
            $keyboard = [
                [['text' => 'Option 1'], ['text' => 'Option 2']],
                [['text' => 'Option 3'], ['text' => 'Option 4']]
            ];
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => $resize_keyboard,
            'one_time_keyboard' => $one_time_keyboard
        ];
    }

    /**
     * Create bot token
     *
     * @param string $identifier Token identifier
     * @return string
     */
    public static function createBotToken(string $identifier = 'test'): string
    {
        return $identifier . '_token_' . time();
    }

    /**
     * Create API URL
     *
     * @param string $base_url Base URL
     * @param string|null $token Bot token (null to use default)
     * @return string
     */
    public static function createApiUrl(
        string $base_url = 'https://api.telegram.org/',
        ?string $token = null
    ): string {
        if ($token === null) {
            $token = self::createBotToken();
        }

        return rtrim($base_url, '/') . '/bot' . $token . '/';
    }

    /**
     * Create webhook URL
     *
     * @param string $domain Domain
     * @param string $path Webhook path
     * @return string
     */
    public static function createWebhookUrl(
        string $domain = 'https://example.com',
        string $path = '/webhook.php'
    ): string {
        return rtrim($domain, '/') . $path;
    }

    /**
     * Create test text with special characters for MarkdownV2
     *
     * @return string
     */
    public static function createTextWithSpecialChars(): string
    {
        return 'Text with *bold_, [links](url), `code`, and other _special_ chars!';
    }

    /**
     * Create HTML test text with tags
     *
     * @return string
     */
    public static function createHtmlText(): string
    {
        return '<b>Bold</b> and <i>italic</i> text with <code>code</code>';
    }

    /**
     * Create file upload parameters
     *
     * @param string $file_path Path to the file
     * @param string $filename Optional filename
     * @return \CURLFile
     */
    public static function createCurlFile(string $file_path, string $filename = 'test.jpg'): \CURLFile
    {
        return new \CURLFile($file_path, mime_content_type($file_path) ?: 'application/octet-stream', $filename);
    }

    /**
     * Get sample API response for various methods
     *
     * @param string $method API method name
     * @param array<string, mixed> $data Additional data
     * @return array<string, mixed>
     */
    public static function createApiResponse(string $method, array $data = []): array
    {
        return match ($method) {
            'getMe' => MockTelegramResponse::getMe(),
            'sendMessage' => MockTelegramResponse::sendMessage($data['message_id'] ?? 1, $data['chat_id'] ?? 123456789),
            'sendPhoto' => MockTelegramResponse::photo($data['message_id'] ?? 1, $data['chat_id'] ?? 123456789),
            'sendDice' => MockTelegramResponse::dice($data['message_id'] ?? 1, $data['chat_id'] ?? 123456789),
            'getChat' => MockTelegramResponse::getChat($data['chat_id'] ?? 123456789),
            'getWebhookInfo' => MockTelegramResponse::webhookInfo(),
            default => MockTelegramResponse::success($data)
        };
    }

    /**
     * Get test environment variable value
     *
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    public static function getEnv(string $key, mixed $default = null): mixed
    {
        return getenv($key) ?: $default;
    }
}
