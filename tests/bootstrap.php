<?php

declare(strict_types=1);

/**
 * PHPUnit Test Bootstrap
 *
 * Sets up autoloading, test constants, and test environment
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Set timezone
date_default_timezone_set('UTC');

// Load composer autoloader if available
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php', // For when installed via composer
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadLoaded = true;
        break;
    }
}

// Fallback to project autoload.php
if (!$autoloadLoaded) {
    require_once __DIR__ . '/../autoload.php';
}

// Always load test helpers (even if composer autoload is available)
spl_autoload_register(function (string $class): void {
    $prefix = 'AhmCho\\Telegram\\Tests\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Test constants
define('TEST_BOT_TOKEN', getenv('TEST_BOT_TOKEN') ?: 'test_token_123456');
define('TEST_CHAT_ID', (int)(getenv('TEST_CHAT_ID') ?: '123456789'));
define('TEST_GROUP_ID', (int)(getenv('TEST_GROUP_ID') ?: '-100123456789'));
define('TEST_CHANNEL_ID', getenv('TEST_CHANNEL_ID') ?: '@testchannel');
define('TEST_MESSAGE_ID', 123);
define('TEST_INLINE_QUERY_ID', 'inline_query_123');
define('TEST_CALLBACK_QUERY_ID', 'callback_query_123');
define('TEST_WEBHOOK_URL', 'https://example.com/webhook.php');

// Test file paths
define('TEST_FILES_DIR', __DIR__ . '/_files');
define('TEST_IMAGE_PATH', TEST_FILES_DIR . '/test_image.jpg');
define('TEST_DOCUMENT_PATH', TEST_FILES_DIR . '/test_document.pdf');

// Ensure test files directory exists
if (!is_dir(TEST_FILES_DIR)) {
    mkdir(TEST_FILES_DIR, 0777, true);
}

// Create dummy test files if they don't exist
if (!file_exists(TEST_IMAGE_PATH)) {
    file_put_contents(TEST_IMAGE_PATH, 'fake_image_content');
}
if (!file_exists(TEST_DOCUMENT_PATH)) {
    file_put_contents(TEST_DOCUMENT_PATH, 'fake_document_content');
}

// Note: assert_options is deprecated in PHP 8.3+
// We rely on PHPUnit's built-in assertion handling instead

// Set stricter error handling for tests
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    // Don't handle suppressed errors (@ operator)
    if (!(error_reporting() & $errno)) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo sprintf(
            "\n\nFATAL ERROR: %s in %s on line %d\n",
            $error['message'],
            $error['file'],
            $error['line']
        );
        exit(1);
    }
});
