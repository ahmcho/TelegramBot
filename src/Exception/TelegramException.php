<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Exception;

use Exception;

/**
 * Base Exception for Telegram Bot Library
 *
 * All library exceptions extend this base class for consistent error handling
 */
abstract class TelegramException extends Exception
{
    // Base exception for all library exceptions
    // Can add common methods here if needed
}
