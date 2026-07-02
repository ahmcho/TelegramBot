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
    case NOTICE = 'NOTICE';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
    case ALERT = 'ALERT';
    case EMERGENCY = 'EMERGENCY';

    /**
     * Get the weight of this log level for filtering.
     * Weights follow RFC 5424 severity order (higher = more severe).
     */
    public function weight(): int
    {
        return match($this) {
            self::DEBUG => 100,
            self::INFO => 200,
            self::NOTICE => 250,
            self::WARNING => 300,
            self::ERROR => 400,
            self::CRITICAL => 500,
            self::ALERT => 550,
            self::EMERGENCY => 600,
        };
    }

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
            'NOTICE' => self::NOTICE,
            'WARNING', 'WARN' => self::WARNING,
            'ERROR' => self::ERROR,
            'CRITICAL' => self::CRITICAL,
            'ALERT' => self::ALERT,
            'EMERGENCY' => self::EMERGENCY,
            default => self::INFO,
        };
    }
}
