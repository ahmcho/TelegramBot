<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

/**
 * Reply Keyboard Options
 *
 * Configuration options for reply keyboards
 */
readonly class ReplyKeyboardOptions
{
    public function __construct(
        public bool $resizeKeyboard = false,
        public bool $oneTimeKeyboard = false,
        public bool $selective = false,
        public bool $isPersistent = false
    ) {
    }
}
