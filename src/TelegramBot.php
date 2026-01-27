<?php

/**
 * Pure PHP Telegram Bot Library
 * A dependency-free Telegram Bot API client
 *
 * @author AhmCho <ahmad@cholluyev.com>
 * @version 1.0.0
 */

/**
 * Exception for bulk operation failures
 * Contains the full results array for partial success scenarios
 */
class BulkSendException extends Exception
{
    private array $results;

    public function __construct(string $message, array $results)
    {
        $this->results = $results;
        parent::__construct($message);
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

class TelegramBot
{
    /**
     * Telegram Bot API base URL
     */
    private string $apiUrl;

    /**
     * Bot token
     */
    private ?string $token = null;

    /**
     * Request timeout in seconds
     */
    private int $timeout = 30;

    /**
     * Whether to throw exceptions on API errors
     */
    private bool $throwExceptions = true;

    /**
     * Last error message
     */
    private ?string $lastError = null;

    /**
     * Last HTTP status code
     */
    private int $lastHttpCode = 0;

    /**
     * Optional database instance
     */
    private ?Database $database = null;

    /**
     * Constructor
     *
     * @param string|null $token Bot token (null to load from environment)
     * @throws Exception if token is not provided and not found in environment
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token ?? $this->getTokenFromEnvironment();

        if (empty($this->token)) {
            throw new Exception('Bot token is required. Set TELEGRAM_BOT_TOKEN environment variable or pass token to constructor.');
        }

        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    /**
     * Get bot token from environment variable
     *
     * @return string|null
     */
    private function getTokenFromEnvironment(): ?string
    {
        return $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN') ?: null;
    }

    /**
     * Make API request
     *
     * @param string $method API method name
     * @param array $params Request parameters
     * @return array Response data
     * @throws Exception on API errors
     */
    private function request(string $method, array $params = []): array
    {
        $url = $this->apiUrl . $method;

        // Handle file uploads
        $hasFile = false;
        foreach ($params as $key => $value) {
            if ($value instanceof CURLFile) {
                $hasFile = true;
                break;
            }
        }

        if ($hasFile) {
            return $this->requestWithCurl($method, $params);
        }

        // Try cURL first, fallback to file_get_contents (only if OpenSSL is available)
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            return $this->requestWithCurl($method, $params);
        }

        // Only use file_get_contents if OpenSSL extension is loaded
        if (extension_loaded('openssl') && in_array('https', stream_get_wrappers())) {
            return $this->requestWithFileGetContents($url, $params);
        }

        throw new Exception('No HTTP transport available. Please enable either the cURL extension or the OpenSSL extension for file_get_contents.');
    }

    /**
     * Make request using cURL
     *
     * @param string $method API method name
     * @param array $params Request parameters
     * @return array Response data
     * @throws Exception on errors
     */
    private function requestWithCurl(string $method, array $params = []): array
    {
        $url = $this->apiUrl . $method;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false, // Disabled to prevent SSL certificate verification errors
            CURLOPT_SSL_VERIFYHOST => 0,     // Disabled to prevent SSL certificate verification errors
            CURLOPT_POSTFIELDS => $params,
        ]);

        // Check for file uploads
        $hasFile = false;
        foreach ($params as $value) {
            if ($value instanceof CURLFile) {
                $hasFile = true;
                break;
            }
        }

        if (!$hasFile) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec($ch);
        $this->lastHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        // curl_close deprecated in PHP 8.5, handle auto-closed when out of scope
        if (PHP_VERSION_ID < 80500) {
            curl_close($ch);
        }

        if ($error || $errno) {
            throw new Exception("cURL error ($errno): $error");
        }

        if ($response === false) {
            throw new Exception("cURL request failed without error message");
        }

        return $this->parseResponse($response);
    }

    /**
     * Make request using file_get_contents
     *
     * @param string $url Full API URL
     * @param array $params Request parameters
     * @return array Response data
     * @throws Exception on errors
     */
    private function requestWithFileGetContents(string $url, array $params = []): array
    {
        // Check if OpenSSL extension is loaded (required for HTTPS)
        if (!extension_loaded('openssl')) {
            throw new Exception('OpenSSL extension is not enabled. Please enable extension=openssl in your php.ini file.');
        }

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($params),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'Unknown error';

            throw new Exception("HTTP request failed: $errorMessage");
        }

        // Parse HTTP status code (use new function in PHP 8.3+)
        if (function_exists('http_get_last_response_headers')) {
            $headers = http_get_last_response_headers();
        } else {
            $headers = $http_response_header ?? [];
        }

        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $this->lastHttpCode = (int) $matches[1];
                break;
            }
        }

        return $this->parseResponse($response);
    }

    /**
     * Parse API response
     *
     * @param string $response Raw response
     * @return array Parsed response
     * @throws Exception on errors
     */
    private function parseResponse(string $response): array
    {
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        if (!$data['ok']) {
            $this->lastError = $data['description'] ?? 'Unknown error';
            if ($this->throwExceptions) {
                throw new Exception("Telegram API error: {$this->lastError}");
            }
        }

        // Some methods return true on success instead of an array
        $result = $data['result'] ?? [];
        return is_bool($result) ? [] : $result;
    }

    // ==================== MULTI-CURL HANDLERS ====================

    /**
     * Create a single cURL handle for a request
     *
     * @param string $method API method name
     * @param array $params Request parameters
     * @return resource cURL handle
     */
    private function createCurlHandle(string $method, array $params)
    {
        $url = $this->apiUrl . $method;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        // Handle file uploads
        $hasFile = false;
        foreach ($params as $value) {
            if ($value instanceof CURLFile) {
                $hasFile = true;
                break;
            }
        }

        if (!$hasFile) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        return $ch;
    }

    /**
     * Process a single multi-cURL result
     *
     * @param string|null $response Raw response
     * @param int $httpCode HTTP status code
     * @param string $error cURL error message
     * @param int $errno cURL error number
     * @param array $params Original request parameters
     * @return array Result with success/failure status
     */
    private function processMultiResult(
        ?string $response,
        int $httpCode,
        string $error,
        int $errno,
        array $params
    ): array {
        $chatId = $params['chat_id'] ?? 'unknown';

        // Check for cURL errors
        if ($error || $errno) {
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => "cURL error ($errno): $error"
            ];
        }

        if ($response === false) {
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => 'cURL request failed without error message'
            ];
        }

        // Parse response (catch exceptions to avoid aborting batch)
        try {
            $data = $this->parseResponse($response);
            return [
                'success' => true,
                'chat_id' => $chatId,
                'message_id' => $data['message_id'] ?? null,
                'data' => $data,
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'chat_id' => $chatId,
                'message_id' => null,
                'data' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute multiple cURL handles with batching
     *
     * @param mixed $mh cURL multi handle
     * @param array $handles Array of handle data
     * @param array $options Options (max_concurrent, delay_ms)
     * @return array Results for all requests
     */
    private function executeMultiHandles($mh, array $handles, array $options): array
    {
        $results = [];
        $active = null;
        $maxConcurrent = $options['max_concurrent'];
        $delayMs = $options['delay_ms'];

        // Process in batches to respect rate limits
        $handleKeys = array_keys($handles);
        $batchSize = min($maxConcurrent, count($handleKeys));
        $batches = array_chunk($handleKeys, $batchSize);

        foreach ($batches as $batch) {
            // Execute this batch
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active > 0) {
                    curl_multi_select($mh);
                }
            } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

            // Collect results from this batch
            foreach ($batch as $index) {
                $handleData = $handles[$index];
                $ch = $handleData['handle'];

                $response = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                $errno = curl_errno($ch);

                $results[$index] = $this->processMultiResult(
                    $response,
                    $httpCode,
                    $error,
                    $errno,
                    $handleData['params']
                );
            }

            // Rate limiting delay between batches
            if ($delayMs > 0 && $batch !== end($batches)) {
                usleep($delayMs * 1000);
            }
        }

        return $results;
    }

    /**
     * Execute multiple API requests in parallel using curl_multi_exec
     *
     * @param string $method API method name
     * @param array $requestsArray Array of request parameter arrays
     * @param array $options Optional configuration (max_concurrent, delay_ms)
     * @return array Results for all requests
     */
    private function requestMulti(string $method, array $requestsArray, array $options = []): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        $options = array_merge([
            'max_concurrent' => 30,
            'delay_ms' => 0
        ], $options);

        // Initialize all cURL handles
        foreach ($requestsArray as $index => $params) {
            $ch = $this->createCurlHandle($method, $params);
            $handles[$index] = [
                'handle' => $ch,
                'params' => $params,
                'url' => $this->apiUrl . $method
            ];
            curl_multi_add_handle($mh, $ch);
        }

        // Execute requests with optional batching
        $results = $this->executeMultiHandles($mh, $handles, $options);

        // Clean up
        foreach ($handles as $handleData) {
            curl_multi_remove_handle($mh, $handleData['handle']);
            if (PHP_VERSION_ID < 80500) {
                curl_close($handleData['handle']);
            }
        }
        curl_multi_close($mh);

        return $results;
    }

    /**
     * Format raw requestMulti results into final bulk response
     *
     * @param array $rawResults Raw results from requestMulti
     * @return array Formatted bulk results
     * @throws BulkSendException if throwExceptions is enabled and there are failures
     */
    private function formatBulkResults(array $rawResults): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($rawResults as $result) {
            if ($result['success']) {
                $successful++;
            } else {
                $failed++;
                $errors[] = $result['error'];
            }
        }

        $formatted = [
            'success' => $failed === 0,
            'total' => count($rawResults),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $rawResults,
            'errors' => $errors
        ];

        // Handle throwExceptions setting
        if ($this->throwExceptions && $failed > 0) {
            throw new BulkSendException(
                "Bulk operation completed with {$failed} failures out of {$formatted['total']}",
                $formatted
            );
        }

        return $formatted;
    }

    /**
     * Get last error message
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get last HTTP status code
     *
     * @return int
     */
    public function getLastHttpCode(): int
    {
        return $this->lastHttpCode;
    }

    /**
     * Set request timeout
     *
     * @param int $seconds Timeout in seconds
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Enable or disable exceptions
     *
     * @param bool $enable
     * @return self
     */
    public function throwExceptions(bool $enable): self
    {
        $this->throwExceptions = $enable;
        return $this;
    }

    /**
     * Set database instance for user storage
     *
     * @param Database $database Database instance
     * @return self
     */
    public function setDatabase(Database $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Get database instance
     *
     * @return Database|null Database instance or null if not set
     */
    public function getDatabase(): ?Database
    {
        return $this->database;
    }

    /**
     * Check if database is configured
     *
     * @return bool True if database is set
     */
    public function hasDatabase(): bool
    {
        return $this->database !== null;
    }

    // ==================== UPDATES ====================

    /**
     * Get updates (long polling)
     *
     * @param array $params Parameters (offset, limit, timeout, allowed_updates)
     * @return array Array of updates
     */
    public function getUpdates(array $params = []): array
    {
        $defaults = [
            'offset' => 0,
            'limit' => 100,
            'timeout' => 0,
            'allowed_updates' => []
        ];

        $params = array_merge($defaults, $params);

        return $this->request('getUpdates', $params);
    }

    /**
     * Get webhook updates from POST data
     *
     * @return array|null Update data or null if no update
     */
    public function getWebhookUpdates(): ?array
    {
        $input = file_get_contents('php://input');
        if (empty($input)) {
            return null;
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * Process webhook updates with a handler function
     *
     * @param callable $handler Function to handle each update
     * @return void
     */
    public function processWebhook(callable $handler): void
    {
        $update = $this->getWebhookUpdates();
        if ($update !== null) {
            $handler($update);
        }
    }

    // ==================== WEBHOOK MANAGEMENT ====================

    /**
     * Set webhook
     *
     * @param array $params Parameters (url, certificate, max_connections, allowed_updates, drop_pending_updates, secret_token)
     * @return array
     */
    public function setWebhook(array $params): array
    {
        return $this->request('setWebhook', $params);
    }

    /**
     * Get webhook info
     *
     * @return array
     */
    public function getWebhookInfo(): array
    {
        return $this->request('getWebhookInfo');
    }

    /**
     * Delete webhook
     *
     * @param array $params Optional parameters (drop_pending_updates)
     * @return array
     */
    public function deleteWebhook(array $params = []): array
    {
        return $this->request('deleteWebhook', $params);
    }

    // ==================== MESSAGES ====================

    /**
     * Send text message
     *
     * @param array $params Parameters (chat_id, text, parse_mode, entities, disable_web_page_preview, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup)
     * @return array Sent message info
     */
    public function sendMessage(array $params): array
    {
        return $this->request('sendMessage', $params);
    }

    /**
     * Edit message text
     *
     * @param array $params Parameters (chat_id, message_id, inline_message_id, text, parse_mode, entities, disable_web_page_preview, reply_markup)
     * @return array Edited message info
     */
    public function editMessageText(array $params): array
    {
        return $this->request('editMessageText', $params);
    }

    /**
     * Edit message caption
     *
     * @param array $params Parameters (chat_id, message_id, inline_message_id, caption, parse_mode, caption_entities, reply_markup)
     * @return array Edited message info
     */
    public function editMessageCaption(array $params): array
    {
        return $this->request('editMessageCaption', $params);
    }

    /**
     * Delete message
     *
     * @param array $params Parameters (chat_id, message_id)
     * @return array Result
     */
    public function deleteMessage(array $params): array
    {
        return $this->request('deleteMessage', $params);
    }

    /**
     * Forward message
     *
     * @param array $params Parameters (chat_id, from_chat_id, message_id, disable_notification, protect_content)
     * @return array Forwarded message info
     */
    public function forwardMessage(array $params): array
    {
        return $this->request('forwardMessage', $params);
    }

    /**
     * Copy message
     *
     * @param array $params Parameters (chat_id, from_chat_id, message_id, caption, parse_mode, caption_entities, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Message identifier
     */
    public function copyMessage(array $params): array
    {
        return $this->request('copyMessage', $params);
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Send multiple messages in parallel using curl_multi_exec
     *
     * @param array $messagesArray Array of message parameter arrays
     *                            Example: [
     *                                ['chat_id' => 123, 'text' => 'Hello'],
     *                                ['chat_id' => 456, 'text' => 'World']
     *                            ]
     * @param array $options Optional configuration (max_concurrent, delay_ms)
     * @return array Results with success/failure for each message
     */
    public function sendMessagesBulk(array $messagesArray, array $options = []): array
    {
        if (empty($messagesArray)) {
            return [
                'success' => true,
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
                'results' => [],
                'errors' => []
            ];
        }

        $results = $this->requestMulti('sendMessage', $messagesArray, $options);

        return $this->formatBulkResults($results);
    }

    /**
     * Send the same message to multiple chats
     *
     * @param array $chatIds Array of chat IDs
     * @param string $text Message text
     * @param array $commonParams Common parameters (parse_mode, disable_web_page_preview, etc.)
     * @param array $options Optional configuration (max_concurrent, delay_ms)
     * @return array Bulk results
     */
    public function broadcastMessage(
        array $chatIds,
        string $text,
        array $commonParams = [],
        array $options = []
    ): array {
        $messagesArray = [];
        foreach ($chatIds as $chatId) {
            $messagesArray[] = array_merge(
                $commonParams,
                ['chat_id' => $chatId, 'text' => $text]
            );
        }

        return $this->sendMessagesBulk($messagesArray, $options);
    }

    /**
     * Broadcast message to database users with optional filters
     *
     * @param string $text Message text
     * @param array $commonParams Common parameters (parse_mode, disable_web_page_preview, etc.)
     * @param array $filters User filters (active_since, has_username, is_premium, limit)
     * @param array $options Optional configuration (max_concurrent, delay_ms)
     * @return array Bulk results
     * @throws Exception if database is not configured
     */
    public function broadcastToDatabase(
        string $text,
        array $commonParams = [],
        array $filters = [],
        array $options = []
    ): array {
        if ($this->database === null) {
            throw new Exception('Database is not configured. Use setDatabase() first.');
        }

        $chatIds = $this->database->getAllChatIds($filters);

        if (empty($chatIds)) {
            return [
                'success' => true,
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
                'results' => [],
                'errors' => []
            ];
        }

        return $this->broadcastMessage($chatIds, $text, $commonParams, $options);
    }

    /**
     * Save user from Telegram update to database
     *
     * @param array $update Telegram update array
     * @return bool Success status
     * @throws Exception if database is not configured
     */
    public function saveUserFromUpdate(array $update): bool
    {
        if ($this->database === null) {
            throw new Exception('Database is not configured. Use setDatabase() first.');
        }

        $userData = Database::extractUserData($update);

        if ($userData === null) {
            return false;
        }

        return $this->database->saveUser($userData);
    }

    /**
     * Save user from update and send message in one operation
     *
     * @param array $update Telegram update array
     * @param array $messageParams Message parameters
     * @return array Message send result
     * @throws Exception if database is not configured
     */
    public function saveAndSendMessage(array $update, array $messageParams): array
    {
        $this->saveUserFromUpdate($update);
        return $this->sendMessage($messageParams);
    }

    // ==================== MEDIA ====================

    /**
     * Send photo
     *
     * @param array $params Parameters (chat_id, photo, caption, parse_mode, caption_entities, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendPhoto(array $params): array
    {
        return $this->request('sendPhoto', $params);
    }

    /**
     * Send document
     *
     * @param array $params Parameters (chat_id, document, caption, parse_mode, caption_entities, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendDocument(array $params): array
    {
        return $this->request('sendDocument', $params);
    }

    /**
     * Send video
     *
     * @param array $params Parameters (chat_id, video, duration, width, height, caption, parse_mode, caption_entities, supports_streaming, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendVideo(array $params): array
    {
        return $this->request('sendVideo', $params);
    }

    /**
     * Send audio
     *
     * @param array $params Parameters (chat_id, audio, caption, parse_mode, caption_entities, duration, performer, title, thumb, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendAudio(array $params): array
    {
        return $this->request('sendAudio', $params);
    }

    /**
     * Send voice
     *
     * @param array $params Parameters (chat_id, voice, caption, parse_mode, caption_entities, duration, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendVoice(array $params): array
    {
        return $this->request('sendVoice', $params);
    }

    /**
     * Send animation (GIF)
     *
     * @param array $params Parameters (chat_id, animation, duration, width, height, caption, parse_mode, caption_entities, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendAnimation(array $params): array
    {
        return $this->request('sendAnimation', $params);
    }

    /**
     * Send sticker
     *
     * @param array $params Parameters (chat_id, sticker, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendSticker(array $params): array
    {
        return $this->request('sendSticker', $params);
    }

    /**
     * Send location
     *
     * @param array $params Parameters (chat_id, latitude, longitude, horizontal_accuracy, live_period, heading, proximity_alert_radius, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendLocation(array $params): array
    {
        return $this->request('sendLocation', $params);
    }

    /**
     * Send venue
     *
     * @param array $params Parameters (chat_id, latitude, longitude, title, address, foursquare_id, foursquare_type, google_place_id, google_place_type, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendVenue(array $params): array
    {
        return $this->request('sendVenue', $params);
    }

    /**
     * Send contact
     *
     * @param array $params Parameters (chat_id, phone_number, first_name, last_name, vcard, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendContact(array $params): array
    {
        return $this->request('sendContact', $params);
    }

    /**
     * Send poll
     *
     * @param array $params Parameters (chat_id, question, options, is_anonymous, type, allows_multiple_answers, correct_option_id, explanation, explanation_parse_mode, open_period, close_date, is_closed, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendPoll(array $params): array
    {
        return $this->request('sendPoll', $params);
    }

    /**
     * Send dice
     *
     * @param array $params Parameters (chat_id, emoji, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendDice(array $params): array
    {
        return $this->request('sendDice', $params);
    }

    // ==================== CHAT ACTIONS ====================

    /**
     * Send chat action
     *
     * @param array $params Parameters (chat_id, action)
     * @return array Result
     */
    public function sendChatAction(array $params): array
    {
        return $this->request('sendChatAction', $params);
    }

    // ==================== CALLBACK QUERIES ====================

    /**
     * Answer callback query
     *
     * @param array $params Parameters (callback_query_id, text, show_alert, url, cache_time)
     * @return array Result
     */
    public function answerCallbackQuery(array $params): array
    {
        return $this->request('answerCallbackQuery', $params);
    }

    /**
     * Answer inline query
     *
     * @param array $params Parameters (inline_query_id, results, cache_time, is_personal, next_offset, switch_pm_text, switch_pm_parameter)
     * @return array Result
     */
    public function answerInlineQuery(array $params): array
    {
        return $this->request('answerInlineQuery', $params);
    }

    // ==================== BOT INFO ====================

    /**
     * Get bot information
     *
     * @return array Bot info
     */
    public function getMe(): array
    {
        return $this->request('getMe');
    }

    /**
     * Get chat information
     *
     * @param array $params Parameters (chat_id)
     * @return array Chat info
     */
    public function getChat(array $params): array
    {
        return $this->request('getChat', $params);
    }

    /**
     * Get chat member information
     *
     * @param array $params Parameters (chat_id, user_id)
     * @return array Chat member info
     */
    public function getChatMember(array $params): array
    {
        return $this->request('getChatMember', $params);
    }

    /**
     * Get chat administrators
     *
     * @param array $params Parameters (chat_id)
     * @return array Administrators list
     */
    public function getChatAdministrators(array $params): array
    {
        return $this->request('getChatAdministrators', $params);
    }

    /**
     * Get chat member count
     *
     * @param array $params Parameters (chat_id)
     * @return array Member count
     */
    public function getChatMemberCount(array $params): array
    {
        return $this->request('getChatMemberCount', $params);
    }

    // ==================== CHAT ADMINISTRATION ====================

    /**
     * Ban chat member
     *
     * @param array $params Parameters (chat_id, user_id, until_date, revoke_messages)
     * @return array Result
     */
    public function banChatMember(array $params): array
    {
        return $this->request('banChatMember', $params);
    }

    /**
     * Unban chat member
     *
     * @param array $params Parameters (chat_id, user_id, only_if_banned)
     * @return array Result
     */
    public function unbanChatMember(array $params): array
    {
        return $this->request('unbanChatMember', $params);
    }

    /**
     * Kick chat member (alias for banChatMember)
     *
     * @param array $params Parameters (chat_id, user_id, until_date, revoke_messages)
     * @return array Result
     */
    public function kickChatMember(array $params): array
    {
        return $this->banChatMember($params);
    }

    /**
     * Restrict chat member
     *
     * @param array $params Parameters (chat_id, user_id, permissions, until_date)
     * @return array Result
     */
    public function restrictChatMember(array $params): array
    {
        return $this->request('restrictChatMember', $params);
    }

    /**
     * Promote chat member
     *
     * @param array $params Parameters (chat_id, user_id, is_anonymous, can_change_info, can_post_messages, can_edit_messages, can_delete_messages, can_invite_users, can_restrict_members, can_pin_messages, can_manage_topics, can_promote_members, can_manage_video_chats, can_manage_chat, can_manage_voice_chats)
     * @return array Result
     */
    public function promoteChatMember(array $params): array
    {
        return $this->request('promoteChatMember', $params);
    }

    /**
     * Leave chat
     *
     * @param array $params Parameters (chat_id)
     * @return array Result
     */
    public function leaveChat(array $params): array
    {
        return $this->request('leaveChat', $params);
    }

    // ==================== MESSAGE MANAGEMENT ====================

    /**
     * Pin chat message
     *
     * @param array $params Parameters (chat_id, message_id, disable_notification)
     * @return array Result
     */
    public function pinChatMessage(array $params): array
    {
        return $this->request('pinChatMessage', $params);
    }

    /**
     * Unpin chat message
     *
     * @param array $params Parameters (chat_id, message_id)
     * @return array Result
     */
    public function unpinChatMessage(array $params): array
    {
        return $this->request('unpinChatMessage', $params);
    }

    /**
     * Unpin all chat messages
     *
     * @param array $params Parameters (chat_id)
     * @return array Result
     */
    public function unpinAllChatMessages(array $params): array
    {
        return $this->request('unpinAllChatMessages', $params);
    }

    // ==================== CHAT SETTINGS ====================

    /**
     * Set chat title
     *
     * @param array $params Parameters (chat_id, title)
     * @return array Result
     */
    public function setChatTitle(array $params): array
    {
        return $this->request('setChatTitle', $params);
    }

    /**
     * Set chat description
     *
     * @param array $params Parameters (chat_id, description)
     * @return array Result
     */
    public function setChatDescription(array $params): array
    {
        return $this->request('setChatDescription', $params);
    }

    /**
     * Set chat photo
     *
     * @param array $params Parameters (chat_id, photo)
     * @return array Result
     */
    public function setChatPhoto(array $params): array
    {
        return $this->request('setChatPhoto', $params);
    }

    /**
     * Delete chat photo
     *
     * @param array $params Parameters (chat_id)
     * @return array Result
     */
    public function deleteChatPhoto(array $params): array
    {
        return $this->request('deleteChatPhoto', $params);
    }

    /**
     * Set chat permissions
     *
     * @param array $params Parameters (chat_id, permissions)
     * @return array Result
     */
    public function setChatPermissions(array $params): array
    {
        return $this->request('setChatPermissions', $params);
    }

    // ==================== GAMES ====================

    /**
     * Send game
     *
     * @param array $params Parameters (chat_id, game_short_name, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function sendGame(array $params): array
    {
        return $this->request('sendGame', $params);
    }

    /**
     * Set game score
     *
     * @param array $params Parameters (user_id, score, force, disable_edit_message, chat_id, message_id, inline_message_id)
     * @return array Result
     */
    public function setGameScore(array $params): array
    {
        return $this->request('setGameScore', $params);
    }

    /**
     * Get game high scores
     *
     * @param array $params Parameters (user_id, chat_id, message_id, inline_message_id)
     * @return array High scores
     */
    public function getGameHighScores(array $params): array
    {
        return $this->request('getGameHighScores', $params);
    }

    // ==================== PAYMENTS ====================

    /**
     * Send invoice
     *
     * @param array $params Parameters (chat_id, title, description, payload, provider_token, currency, prices, max_tip_amount, suggested_tip_amounts, start_parameter, provider_data, photo_url, photo_size, photo_width, photo_height, need_name, need_phone_number, need_email, need_shipping_address, send_phone_number_to_provider, send_email_to_provider, is_flexible, disable_notification, reply_to_message_id, allow_sending_without_reply, reply_markup, protect_content)
     * @return array Sent message info
     */
    public function createInvoice(array $params): array
    {
        return $this->request('sendInvoice', $params);
    }

    // ==================== KEYBOARD BUILDERS ====================

    /**
     * Build inline keyboard
     *
     * @param array $buttons Multi-dimensional array of buttons
     * @return string JSON-encoded keyboard
     *
     * Example:
     * [
     *   [
     *     ['text' => 'Button 1', 'callback_data' => 'btn1'],
     *     ['text' => 'Button 2', 'callback_data' => 'btn2']
     *   ],
     *   [
     *     ['text' => 'URL', 'url' => 'https://example.com']
     *   ]
     * ]
     */
    public function buildInlineKeyboard(array $buttons): string
    {
        return json_encode([
            'inline_keyboard' => $buttons
        ]);
    }

    /**
     * Build reply keyboard
     *
     * @param array $buttons Multi-dimensional array of buttons
     * @param array $options Optional keyboard settings
     * @return string JSON-encoded keyboard
     *
     * Example:
     * [
     *   ['Button 1', 'Button 2'],
     *   ['Button 3', 'Button 4']
     * ]
     */
    public function buildReplyKeyboard(array $buttons, array $options = []): string
    {
        $keyboard = ['keyboard' => $buttons];

        // Merge with options
        $keyboard = array_merge($keyboard, $options);

        return json_encode($keyboard);
    }

    /**
     * Build force reply keyboard
     *
     * @param bool $selective
     * @return string JSON-encoded keyboard
     */
    public function buildForceReply(bool $selective = false): string
    {
        return json_encode([
            'force_reply' => true,
            'selective' => $selective
        ]);
    }

    /**
     * Build remove keyboard
     *
     * @param bool $selective
     * @return string JSON-encoded keyboard
     */
    public function buildRemoveKeyboard(bool $selective = false): string
    {
        return json_encode([
            'remove_keyboard' => true,
            'selective' => $selective
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Escape text for MarkdownV2
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escapeMarkdownV2(string $text): string
    {
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($chars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }

    /**
     * Escape text for Markdown
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escapeMarkdown(string $text): string
    {
        return str_replace(['*', '_', '[', ']', '`'], ['\*', '\_', '\[', '\]', '\`'], $text);
    }

    /**
     * Escape text for HTML
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escapeHTML(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Create a button for inline keyboard
     *
     * @param string $text Button text
     * @param string $type Button type (callback_data, url, switch_inline_query, etc.)
     * @param string $value Button value
     * @return array Button array
     */
    public function createInlineButton(string $text, string $type, string $value): array
    {
        return [
            'text' => $text,
            $type => $value
        ];
    }

    /**
     * Create a URL button
     *
     * @param string $text Button text
     * @param string $url URL to open
     * @return array Button array
     */
    public function createUrlButton(string $text, string $url): array
    {
        return $this->createInlineButton($text, 'url', $url);
    }

    /**
     * Create a callback button
     *
     * @param string $text Button text
     * @param string $data Callback data
     * @return array Button array
     */
    public function createCallbackButton(string $text, string $data): array
    {
        return $this->createInlineButton($text, 'callback_data', $data);
    }

    /**
     * Create a switch inline query button
     *
     * @param string $text Button text
     * @param string $query Inline query
     * @return array Button array
     */
    public function createSwitchInlineButton(string $text, string $query = ''): array
    {
        return $this->createInlineButton($text, 'switch_inline_query', $query);
    }

    /**
     * Create a switch inline query button in current chat
     *
     * @param string $text Button text
     * @param string $query Inline query
     * @return array Button array
     */
    public function createSwitchInlineCurrentChatButton(string $text, string $query = ''): array
    {
        return $this->createInlineButton($text, 'switch_inline_query_current_chat', $query);
    }
}
