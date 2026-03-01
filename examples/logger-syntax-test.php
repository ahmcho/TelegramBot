<?php

declare(strict_types=1);

// Test syntax without actually running
echo "Testing PHP syntax...\n";

$files = [
    'src/Client/CurlHttpClient.php',
    'src/Client/StreamHttpClient.php',
    'src/Client/HttpClientFactory.php',
    'src/Bulk/BulkOperationManager.php',
    'src/Api/ApiService.php',
    'src/Database/SqliteUserRepository.php',
    'src/Bot/TelegramBot.php',
    'src/Logging/LoggerInterface.php',
    'src/Logging/LoggerFactory.php',
    'src/Logging/Logger.php',
];

$errors = 0;
foreach ($files as $file) {
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
    if ($return !== 0) {
        echo "✗ $file: " . implode("\n", $output) . "\n";
        $errors++;
    } else {
        echo "✓ $file\n";
    }
}

echo "\n";
if ($errors === 0) {
    echo "All files have valid syntax!\n";
    exit(0);
} else {
    echo "Found $errors files with syntax errors\n";
    exit(1);
}
