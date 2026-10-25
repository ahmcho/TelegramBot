<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

use NoDiscard;

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

    #[NoDiscard]
    public function withResizeKeyboard(bool $resize = true): self
    {
        return clone($this, ['resizeKeyboard' => $resize]);
    }

    #[NoDiscard]
    public function withOneTimeKeyboard(bool $oneTime = true): self
    {
        return clone($this, ['oneTimeKeyboard' => $oneTime]);
    }

    #[NoDiscard]
    public function withSelective(bool $selective = true): self
    {
        return clone($this, ['selective' => $selective]);
    }

    #[NoDiscard]
    public function withIsPersistent(bool $isPersistent = true): self
    {
        return clone($this, ['isPersistent' => $isPersistent]);
    }
}
