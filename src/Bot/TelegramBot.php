<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bot;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Api\Methods\ChatService;
use AhmCho\Telegram\Api\Methods\MessageService;
use AhmCho\Telegram\Api\Methods\MediaService;
use AhmCho\Telegram\Api\Methods\PollsService;
use AhmCho\Telegram\Api\Methods\WebhookService;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Command\CommandHandler;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Config\EnvLoader;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Formatting\TextFormatterInterface;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Logging\LoggerFactory;
use AhmCho\Telegram\Logging\LoggerInterface;
use AhmCho\Telegram\Logging\Traits\LoggerHelperTrait;

/**
 * Telegram Bot Facade
 *
 * Main entry point for interacting with the Telegram Bot API
 */
final class TelegramBot
{
    use LoggerHelperTrait;

    private readonly ApiService $apiService;
    private readonly MessageService $messages;
    private readonly MediaService $media;
    private readonly ChatService $chats;
    private readonly WebhookService $webhooks;
    private readonly PollsService $polls;
    private readonly MarkdownV2Formatter $formatter;
    private readonly ?LoggerInterface $logger;
    private readonly CommandHandler $commands;
    private string $inputSource = 'php://input';

    public function __construct(
        ?string $token = null,
        ?BotConfig $config = null,
        ?HttpClientInterface $httpClient = null
    ) {
        $loader = new EnvLoader();
        $loader->load();

        $config ??= new BotConfig(
            token: $token ?? $loader->require('TELEGRAM_BOT_TOKEN')
        );

        // Create logger from config
        $this->logger = LoggerFactory::createFromConfig($config);

        // Log bot initialization
        $this->logIfEnabled('info', 'TelegramBot initialized', [
            'token_hash' => substr(md5($config->getToken()), 0, 8),
            'logging_enabled' => $config->isLoggingEnabled(),
            'log_level' => $config->getLogLevel(),
            'timeout' => $config->getTimeout(),
            'throw_exceptions' => $config->shouldThrowExceptions()
        ]);

        // Create HTTP client with logger
        $httpClient ??= HttpClientFactory::create($config, $this->logger);

        // Create bulk manager with logger
        $bulkManager = new BulkOperationManager($httpClient, $config, $this->logger);

        // Create API service with logger
        $this->apiService = new ApiService($httpClient, $config, $bulkManager, $this->logger);

        $this->messages = new MessageService($this->apiService);
        $this->media = new MediaService($this->apiService);
        $this->chats = new ChatService($this->apiService);
        $this->webhooks = new WebhookService($this->apiService);
        $this->polls = new PollsService($this->apiService);
        $this->formatter = new MarkdownV2Formatter();
        $this->commands = new CommandHandler($this);
    }

    // Service accessors

    public function messages(): MessageService
    {
        return $this->messages;
    }

    public function media(): MediaService
    {
        return $this->media;
    }

    public function chats(): ChatService
    {
        return $this->chats;
    }

    public function webhooks(): WebhookService
    {
        return $this->webhooks;
    }

    public function polls(): PollsService
    {
        return $this->polls;
    }

    public function formatter(): TextFormatterInterface
    {
        return $this->formatter;
    }

    public function commands(): CommandHandler
    {
        return $this->commands;
    }

    public function api(): ApiService
    {
        return $this->apiService;
    }

    /**
     * Get the logger instance
     * Returns null if logging is disabled
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    // Convenience methods for backward compatibility

    /**
     * @param array<string, mixed> $params
     */
    public function sendMessage(array $params): array
    {
        return $this->messages->send($params);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function sendPhoto(array $params): array
    {
        return $this->media->sendPhoto($params);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMe(): array
    {
        return $this->apiService->call(ApiMethod::GET_ME);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpdates(array $params = []): array
    {
        return $this->apiService->call(ApiMethod::GET_UPDATES, $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getWebhookUpdates(): ?array
    {
        $input = file_get_contents($this->inputSource);
        if ($input === false || $input === '' || $input === null) {
            return null;
        }

        if (!json_validate($input)) {
            return null;
        }

        return json_decode($input, true);
    }

    public function processWebhook(callable $handler): void
    {
        $update = $this->getWebhookUpdates();
        if ($update !== null) {
            $handler($update);
        }
    }

    /**
     * Set input source for webhook updates (for testing)
     * Allows overriding php://input with a custom stream
     */
    public function setInputSource(string $source): void
    {
        $this->inputSource = $source;
    }

    // Retry methods with automatic error handling

    /**
     * Send message with automatic retry on failure
     *
     * @param array<string, mixed> $params Message parameters
     * @param array<string, mixed> $options Retry options:
     *   - max_retries: int (default: 3)
     *   - initial_delay_ms: int (default: 1000)
     *   - max_delay_ms: int (default: 10000)
     *   - on_retry: callable Called on each retry
     * @return array<string, mixed> The API response
     */
    public function sendMessageWithRetry(array $params, array $options = []): array
    {
        return $this->executeWithRetry(
            fn() => $this->messages->send($params),
            $options
        );
    }

    /**
     * Execute bulk operation with retry on failure
     *
     * @param array<int, array<string, mixed>> $messagesArray Messages to send
     * @param array<string, mixed> $bulkOptions Bulk operation options
     * @param array<string, mixed> $retryOptions Retry options
     * @return array<string, mixed> Bulk operation result
     */
    public function sendBulkWithRetry(
        array $messagesArray,
        array $bulkOptions = [],
        array $retryOptions = []
    ): array {
        return $this->executeWithRetry(
            fn() => $this->messages->sendBulk($messagesArray, $bulkOptions),
            $retryOptions
        );
    }

    /**
     * Execute callback with automatic retry on failure
     *
     * @param callable $callback The function to execute
     * @param array<string, mixed> $options Retry options
     * @return mixed The result from the callback
     */
    public function executeWithRetry(callable $callback, array $options = []): mixed
    {
        $maxRetries = $options['max_retries'] ?? 3;
        $initialDelayMs = $options['initial_delay_ms'] ?? 1000;
        $maxDelayMs = $options['max_delay_ms'] ?? 10000;
        $onRetry = $options['on_retry'] ?? null;

        if ($this->logger !== null) {
            $this->logger->debug('Starting operation with retry', [
                'max_retries' => $maxRetries,
                'initial_delay_ms' => $initialDelayMs
            ]);
        }

        $lastException = null;
        $delayMs = $initialDelayMs;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                $result = $callback();

                if ($this->logger !== null && $attempt > 0) {
                    $this->logger->info('Operation succeeded after retry', [
                        'attempt' => $attempt + 1
                    ]);
                }

                return $result;
            } catch (\AhmCho\Telegram\Exception\ApiException $e) {
                $lastException = $e;

                // Log the error
                if ($this->logger !== null) {
                    $this->logger->warning('API request failed', [
                        'attempt' => $attempt + 1,
                        'error' => $e->getMessage(),
                        'http_code' => $e->getHttpCode()
                    ]);
                }

                // Don't retry on client errors (4xx) except 429
                if ($e->getHttpCode() >= 400 && $e->getHttpCode() < 500 && $e->getHttpCode() !== 429) {
                    throw $e;
                }

                // Don't retry if this was the last attempt
                if ($attempt === $maxRetries) {
                    break;
                }

                // Handle rate limit (429)
                if ($e->getHttpCode() === 429) {
                    $response = $e->getResponseBody();
                    $retryAfter = 1;

                    if (is_array($response) && isset($response['parameters']['retry_after'])) {
                        $retryAfter = (int) $response['parameters']['retry_after'];
                    }

                    $delayMs = $retryAfter * 1000;

                    if ($this->logger !== null) {
                        $this->logger->info('Rate limit detected, waiting', [
                            'retry_after_seconds' => $retryAfter
                        ]);
                    }
                }

                // Call retry callback
                if ($onRetry !== null && is_callable($onRetry)) {
                    $onRetry($attempt + 1, $e, $delayMs);
                }

                // Wait before retry
                usleep($delayMs * 1000);

                // Exponential backoff
                $delayMs = min($delayMs * 2, $maxDelayMs);
            }
        }

        if ($this->logger !== null) {
            $this->logger->error('Operation failed after all retries', [
                'max_retries' => $maxRetries,
                'final_error' => $lastException?->getMessage()
            ]);
        }

        throw $lastException;
    }
}
