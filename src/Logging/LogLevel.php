<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Logging;

/**
 * Log level enum with weight-based filtering
 */
enum LogLevel: string
{
    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';

    /**
     * Get the weight of this log level for filtering
     * Higher weight = more severe
     */
    public function weight(): int
    {
        return match($this) {
            self::DEBUG => 100,
            self::INFO => 200,
            self::WARNING => 300,
            self::ERROR => 400,
            self::CRITICAL => 500,
        };
    }

    /**
     * Check if this log level should be logged based on minimum level
     */
    public function shouldLog(LogLevel $minLevel): bool
    {
        return $this->weight() >= $minLevel->weight();
    }

    /**
     * Create from PSR-3 level string
     */
    public static function fromPsr3(string $level): self
    {
        return match(strtoupper($level)) {
            'DEBUG' => self::DEBUG,
            'INFO' => self::INFO,
            'NOTICE', 'INFO' => self::INFO,
            'WARNING', 'WARN' => self::WARNING,
            'ERROR' => self::ERROR,
            'CRITICAL', 'ALERT', 'EMERGENCY' => self::CRITICAL,
            default => self::INFO,
        };
    }
}
