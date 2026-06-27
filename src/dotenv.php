<?php

/**
 * Simple .env file loader
 * Loads environment variables from .env file
 *
 * This is a thin wrapper around EnvLoader for backward compatibility
 * All logic is delegated to the EnvLoader class
 */

use AhmCho\Telegram\Config\EnvLoader;

if (!function_exists('loadEnv')) {
    /**
     * Load environment variables from .env file
     *
     * @param string|null $path Optional path to .env file. If null, searches common locations
     */
    function loadEnv(?string $path = null): void
    {
        $loader = new EnvLoader();
        $loader->load($path);
    }
}

// Auto-load .env when this file is included
loadEnv();
