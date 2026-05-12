<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Bot;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Api\Methods\ChatService;
use AhmCho\Telegram\Api\Methods\MessageService;
use AhmCho\Telegram\Api\Methods\MediaService;
use AhmCho\Telegram\Api\Methods\WebhookService;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Config\EnvLoader;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Formatting\TextFormatterInterface;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Logging\LoggerFactory;
use AhmCho\Telegram\Logging\LoggerInterface;

/**
 * Telegram Bot Facade
 *
 * Main entry point for interacting with the Telegram Bot API
 */
final class TelegramBot
{
    private readonly ApiService $apiService;
    private readonly MessageService $messages;
    private readonly MediaService $media;
    private readonly ChatService $chats;
    private readonly WebhookService $webhooks;
    private readonly MarkdownV2Formatter $formatter;
    private readonly ?LoggerInterface $logger;
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
        $this->formatter = new MarkdownV2Formatter();
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

    public function formatter(): TextFormatterInterface
    {
        return $this->formatter;
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
        if (empty($input)) {
            return null;
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
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

    /**
     * Log message if logger is configured
     * Never throws exceptions from logging operations
     *
     * @param 'info'|'warning'|'error'|'debug' $level
     * @param array<string, mixed> $context
     */
    private function logIfEnabled(string $level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->log($level, $message, $context);
            } catch (\Throwable $e) {
                // Fail silently - never throw from logger
            }
        }
    }
}
