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
     */
    public function __construct(string $logFilePath, bool $createDirectory = true)
    {
        // Validate log file path
        if (empty($logFilePath)) {
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

        if (!is_dir($directory) && !empty($directory)) {
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
        file_put_contents($this->logFilePath, '');
    }

    /**
     * Get the size of the log file in bytes
     */
    public function getSize(): int
    {
        if (!file_exists($this->logFilePath)) {
            return 0;
        }

        return filesize($this->logFilePath);
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

        $handle = fopen($this->logFilePath, 'r');
        if ($handle === false) {
            return [];
        }

        $buffer = [];
        $lineCount = 0;

        try {
            // Seek to end of file
            fseek($handle, 0, SEEK_END);

            // Read backwards line by line
            $pos = ftell($handle) - 1;

            while ($pos >= 0 && $lineCount < $lines) {
                $char = fgetc($handle);
                if ($char === "\n") {
                    $lineCount++;
                    $pos--;
                    fseek($handle, $pos);
                    continue;
                }

                fseek($handle, $pos);
                $pos--;
            }

            // Read remaining lines
            while (!feof($handle)) {
                $buffer[] = fgets($handle);
            }
        } finally {
            fclose($handle);
        }

        return array_filter(array_reverse($buffer));
    }
}
