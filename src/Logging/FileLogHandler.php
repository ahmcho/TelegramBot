<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

/**
 * File-based log handler with concurrent write protection
 */
final class FileLogHandler
{
    private readonly string $logFilePath;
    private int $maxRetries = 3;
    private int $retryDelayMs = 100;

    /**
     * @param string $logFilePath Path to the log file
     * @param bool $createDirectory If true, create directory if it doesn't exist
     * @param int $maxBytes Rotate when file exceeds this size; 0 = disabled
     */
    public function __construct(
        string $logFilePath,
        bool $createDirectory = true,
        private readonly int $maxBytes = 0
    ) {
        if ($logFilePath === '' || $logFilePath === '0') {
            throw new \InvalidArgumentException("Log file path cannot be empty");
        }

        $this->logFilePath = $logFilePath;

        if ($createDirectory) {
            $this->ensureDirectoryExists();
        }
    }

    /**
     * Write a log entry to the file
     *
     * @param string $entry The formatted log entry
     * @throws \RuntimeException If unable to write after retries
     */
    public function write(string $entry): void
    {
        if ($this->maxBytes > 0 && $this->getSize() >= $this->maxBytes) {
            $this->rotate();
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                $this->writeWithLock($entry);
                return;
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    // Exponential backoff
                    usleep($this->retryDelayMs * 1000 * (1 << ($attempt - 1)));
                }
            }
        }

        // All retries failed
        if ($lastError === null) {
            throw new \RuntimeException("Failed to write to log file after {$this->maxRetries} attempts");
        }

        throw new \RuntimeException(
            "Failed to write to log file after {$this->maxRetries} attempts: {$lastError->getMessage()}",
            0,
            $lastError
        );
    }

    /**
     * Write to file with exclusive lock
     */
    private function writeWithLock(string $entry): void
    {
        $handle = fopen($this->logFilePath, 'a');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open log file: {$this->logFilePath}");
        }

        try {
            // Acquire exclusive lock (non-blocking)
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $written = fwrite($handle, $entry);

                if ($written === false) {
                    throw new \RuntimeException("Failed to write to log file");
                }

                // Release lock
                fflush($handle);
                flock($handle, LOCK_UN);
            } else {
                throw new \RuntimeException("Unable to acquire lock on log file");
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Ensure the log directory exists
     */
    private function ensureDirectoryExists(): void
    {
        $directory = dirname($this->logFilePath);

        if (!is_dir($directory) && $directory !== '' && $directory !== '.') {
            if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new \RuntimeException("Failed to create log directory: {$directory}");
            }
        }
    }

    /**
     * Get the log file path
     */
    public function getLogFilePath(): string
    {
        return $this->logFilePath;
    }

    /**
     * Clear the log file contents
     */
    public function clear(): void
    {
        if (file_put_contents($this->logFilePath, '') === false) {
            throw new \RuntimeException("Failed to clear log file: {$this->logFilePath}");
        }
    }

    /**
     * Get the size of the log file in bytes
     */
    public function getSize(): int
    {
        if (!file_exists($this->logFilePath)) {
            return 0;
        }

        $size = filesize($this->logFilePath);

        return $size !== false ? $size : 0;
    }

    /**
     * Get the configured rotation threshold in bytes (0 = disabled)
     */
    public function getMaxBytes(): int
    {
        return $this->maxBytes;
    }

    /**
     * Rotate the log file: rename current file to .1, start fresh
     */
    private function rotate(): void
    {
        $rotatedPath = $this->logFilePath . '.1';

        // Overwrite any existing .1 file
        if (file_exists($rotatedPath)) {
            @unlink($rotatedPath);
        }

        @rename($this->logFilePath, $rotatedPath);
    }

    /**
     * Read the last N lines from the log file
     *
     * @param int $lines Number of lines to read
     * @return array<string> Array of log lines
     */
    public function readLastLines(int $lines = 100): array
    {
        if (!file_exists($this->logFilePath)) {
            return [];
        }

        $content = file($this->logFilePath, FILE_IGNORE_NEW_LINES);
        if ($content === false) {
            return [];
        }

        return array_values(array_slice($content, -$lines));
    }
}
