<?php

/**
 * Simple .env file loader
 * Loads environment variables from .env file
 */

if (!function_exists('loadEnv')) {
    function loadEnv(?string $path = null): void
    {
        if ($path === null) {
            // Find .env file in current directory or parent directories
            $path = findEnvFile();
        }

        if ($path === null || !file_exists($path)) {
            return; // No .env file found, that's okay
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE or KEY="VALUE" or KEY='VALUE'
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];

                // Remove quotes if present
                if (strlen($value) > 1) {
                    if ($value[0] === '"' && $value[strlen($value) - 1] === '"') {
                        $value = substr($value, 1, -1);
                    } elseif ($value[0] === "'" && $value[strlen($value) - 1] === "'") {
                        $value = substr($value, 1, -1);
                    }
                }

                // Set environment variable
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    function findEnvFile(): ?string
    {
        $paths = [
            getcwd() . '/.env',
            dirname(__DIR__) . '/.env',
            __DIR__ . '/../.env',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}

// Auto-load .env when this file is included
loadEnv();
