<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Command;

use AhmCho\Telegram\Bot\TelegramBot;

/**
 * Command Handler System
 *
 * Provides a simple way to register and handle bot commands
 * without manual parsing and routing
 */
class CommandHandler
{
    /**
     * @var array<string, CommandCallback>
     */
    private array $commands = [];

    /**
     * @var array<string, string>
     */
    private array $descriptions = [];

    /**
     * @var array<string, mixed>
     */
    private array $middleware = [];

    /**
     * Default callback for unknown commands
     *
     * @var callable|null
     */
    private $defaultCallback = null;

    /**
     * Command callback type
     */
    private const CALLBACK_TYPE = 'callable';

    public function __construct(
        private readonly TelegramBot $bot
    ) {}

    /**
     * Register a command handler
     *
     * @param string $command The command name (with or without /)
     * @param callable $callback The callback function: function(TelegramBot $bot, int $chatId, array $args): void
     * @param string $description Command description for help menu
     * @return self For chaining
     */
    public function register(string $command, callable $callback, string $description = ''): self
    {
        // Normalize command name (remove leading /, convert to lowercase)
        $normalizedName = $this->normalizeCommand($command);

        $this->commands[$normalizedName] = $callback;

        if ($description !== '') {
            $this->descriptions[$normalizedName] = $description;
        }

        return $this;
    }

    /**
     * Register multiple commands at once
     *
     * @param array<string, callable|array{callback: callable, description: string}> $commands
     * @return self For chaining
     */
    public function registerCommands(array $commands): self
    {
        foreach ($commands as $command => $handler) {
            if (is_array($handler)) {
                $this->register(
                    $command,
                    $handler['callback'],
                    $handler['description'] ?? ''
                );
            } else {
                $this->register($command, $handler);
            }
        }

        return $this;
    }

    /**
     * Set the default callback for unknown commands
     *
     * @param callable $callback Function: function(TelegramBot $bot, int $chatId, string $command): void
     * @return self For chaining
     */
    public function setDefault(callable $callback): self
    {
        $this->defaultCallback = $callback;
        return $this;
    }

    /**
     * Add middleware that runs before command execution
     *
     * @param string $name Middleware name
     * @param callable $middleware Function: function(TelegramBot $bot, int $chatId, string $command, array $args): bool
     * @return self For chaining
     */
    public function addMiddleware(string $name, callable $middleware): self
    {
        $this->middleware[$name] = $middleware;
        return $this;
    }

    /**
     * Handle an incoming update and route to the appropriate command
     *
     * @param array<string, mixed> $update The Telegram update
     * @return bool True if a command was handled, false otherwise
     */
    public function handleUpdate(array $update): bool
    {
        // Check if this is a message update
        if (!isset($update['message'])) {
            return false;
        }

        $message = $update['message'];
        $text = $message['text'] ?? '';

        // Check if this is a command
        if (!$this->isCommand($text)) {
            return false;
        }

        $chatId = (int) $message['chat']['id'];
        $parts = explode(' ', trim($text), 2);
        $command = $this->normalizeCommand($parts[0]);
        $args = isset($parts[1]) ? explode(' ', $parts[1]) : [];

        // Execute middleware
        foreach ($this->middleware as $name => $middleware) {
            try {
                $result = $middleware($this->bot, $chatId, $command, $args);
                if ($result === false) {
                    // Middleware returned false, stop execution
                    return true;
                }
            } catch (\Throwable $e) {
                // Log and continue with other middleware
                error_log("Middleware '$name' error: {$e->getMessage()}");
            }
        }

        // Execute command callback
        if (isset($this->commands[$command])) {
            try {
                ($this->commands[$command])($this->bot, $chatId, $args);
                return true;
            } catch (\Throwable $e) {
                // Send error message to user
                $this->bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => "⚠️ Error executing command: {$e->getMessage()}"
                ]);
                error_log("Command '$command' error: {$e->getMessage()}");
                return true;
            }
        }

        // No matching command, use default callback
        if ($this->defaultCallback !== null) {
            try {
                ($this->defaultCallback)($this->bot, $chatId, $command, $args);
                return true;
            } catch (\Throwable $e) {
                error_log("Default callback error: {$e->getMessage()}");
            }
        }

        return false;
    }

    /**
     * Generate a help message listing all registered commands
     *
     * @return string The help message
     */
    public function generateHelp(): string
    {
        if (empty($this->descriptions)) {
            return "No commands registered.";
        }

        $lines = ["📖 *Available Commands*\n"];

        foreach ($this->descriptions as $command => $description) {
            $lines[] = "/{$command} - {$description}";
        }

        return implode("\n", $lines);
    }

    /**
     * Send help message to a chat
     *
     * @param int $chatId The chat ID to send help to
     * @return void
     */
    public function sendHelp(int $chatId): void
    {
        $this->bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $this->generateHelp(),
            'parse_mode' => 'MarkdownV2'
        ]);
    }

    /**
     * Get all registered commands
     *
     * @return array<string> List of command names
     */
    public function getRegisteredCommands(): array
    {
        return array_keys($this->commands);
    }

    /**
     * Check if a command is registered
     *
     * @param string $command The command name (with or without /)
     * @return bool True if registered
     */
    public function hasCommand(string $command): bool
    {
        return isset($this->commands[$this->normalizeCommand($command)]);
    }

    /**
     * Unregister a command
     *
     * @param string $command The command name (with or without /)
     * @return bool True if command was removed, false if not found
     */
    public function unregister(string $command): bool
    {
        $normalizedName = $this->normalizeCommand($command);

        if (isset($this->commands[$normalizedName])) {
            unset($this->commands[$normalizedName]);
            unset($this->descriptions[$normalizedName]);
            return true;
        }

        return false;
    }

    /**
     * Clear all registered commands
     *
     * @return void
     */
    public function clear(): void
    {
        $this->commands = [];
        $this->descriptions = [];
    }

    /**
     * Normalize command name for consistent lookup
     *
     * @param string $command The command name
     * @return string Normalized command name
     */
    private function normalizeCommand(string $command): string
    {
        return ltrim(strtolower($command), '/');
    }

    /**
     * Check if text is a command
     *
     * @param string $text The text to check
     * @return bool True if text starts with /
     */
    private function isCommand(string $text): bool
    {
        return str_starts_with(trim($text), '/');
    }
}
