<?php

declare(strict_types=1);

/**
 * Simple PSR-4 Autoloader
 *
 * Automatically loads classes from the src/ directory
 * Follows PSR-4 autoloading standard
 */

spl_autoload_register(function (string $class): void {
    // Project namespace prefix
    $prefix = 'AhmCho\\Telegram\\';

    // PSR namespace prefix
    $psrPrefix = 'Psr\\Log\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';
    $psr_base_dir = __DIR__ . '/vendor/psr/log/';

    // Check if the class uses the project namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        // Get the relative class name
        $relative_class = substr($class, $len);

        // Replace namespace separators with directory separators
        // and append .php extension
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
        return;
    }

    // Check if the class uses the PSR namespace prefix
    $psrLen = strlen($psrPrefix);
    if (strncmp($psrPrefix, $class, $psrLen) === 0) {
        // Get the relative class name
        $relative_class = substr($class, $psrLen);

        // Replace namespace separators with directory separators
        // and append .php extension
        $file = $psr_base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
