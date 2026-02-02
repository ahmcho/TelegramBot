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

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Class doesn't use our namespace, skip
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace namespace separators with directory separators
    // and append .php extension
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
});
