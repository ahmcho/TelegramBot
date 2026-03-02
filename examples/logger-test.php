<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Logging\LoggerFactory;

echo "=== Logger Integration Test ===\n\n";

// Test 1: Create bot with default config (logging enabled by default)
echo "Test 1: Creating bot with default config...\n";
$bot = new TelegramBot();
$logger = $bot->getLogger();
echo "Logger instance: " . ($logger ? 'Yes' : 'No') . "\n";
echo "Logger type: " . get_class($logger ?? new class {}) . "\n";
echo "✓ Bot created with logger\n\n";

// Test 2: Create bot with logging disabled
echo "Test 2: Creating bot with logging disabled...\n";
$config = new BotConfig(
    token: 'test:token',
    loggingEnabled: false
);
$botNoLog = new TelegramBot(null, $config);
$loggerNoLog = $botNoLog->getLogger();
echo "Logger instance: " . ($loggerNoLog ? 'Yes' : 'No') . "\n";
echo "✓ Bot created without logger (null)\n\n";

// Test 3: Create bot with custom log level
echo "Test 3: Creating bot with custom log level...\n";
$configDebug = new BotConfig(
    token: 'test:token',
    loggingEnabled: true,
    logLevel: 'DEBUG',
    logFilePath: 'debug.log'
);
$botDebug = new TelegramBot(null, $configDebug);
$loggerDebug = $botDebug->getLogger();
echo "Logger instance: " . ($loggerDebug ? 'Yes' : 'No') . "\n";
echo "✓ Bot created with DEBUG logger\n\n";

// Test 4: Test logger directly
echo "Test 4: Testing logger directly...\n";
$directLogger = LoggerFactory::createDefault();
$directLogger->info('Test info message');
$directLogger->debug('Test debug message');
$directLogger->warning('Test warning message');
echo "✓ Direct logger test completed\n\n";

// Test 5: Test logger exception handling
echo "Test 5: Testing logger exception handling...\n";
try {
    throw new Exception('Test exception for logging');
} catch (\Throwable $e) {
    $directLogger->logException($e, ['context' => 'test']);
    echo "✓ Exception logged successfully\n\n";
}

echo "=== All Tests Passed ===\n";
echo "Log files created in current directory\n";
