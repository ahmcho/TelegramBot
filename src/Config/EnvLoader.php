<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Config;

use RuntimeException;

/**
 * Environment File Loader
 *
 * Object-oriented .env file loader to replace procedural approach
 */
class EnvLoader
{
    /**
     * @var array<string, string>
     */
    private array $loadedVars = [];

    public function load(string|null $path = null): void
    {
        $path ??= $this->findEnvFile();

        if ($path === null || !file_exists($path)) {
            return; // No .env file found, that's okay
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $this->parseLine($line);
        }
    }

    public function get(string $key, string|null $default = null): string|null
    {
        return $_ENV[$key] ?? $this->loadedVars[$key] ?? $default;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new RuntimeException("Required environment variable '$key' is not set.");
        }

        return $value;
    }

    private function parseLine(string $line): void
    {
        // Skip comments and empty lines
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            return;
        }

        // Parse KEY=VALUE or KEY="VALUE" or KEY='VALUE'
        if (!preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $matches)) {
            return;
        }

        [, $key, $value] = $matches;
        $value = $this->parseValue($value);

        $this->loadedVars[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }

    private function parseValue(string $value): string
    {
        // Remove surrounding quotes if present
        if (strlen($value) > 1) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' || $first === "'") && $first === $last) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private function findEnvFile(): string|null
    {
        $paths = [
            getcwd() . '/.env',
            dirname(__DIR__) . '/.env',
            __DIR__ . '/../../.env',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
