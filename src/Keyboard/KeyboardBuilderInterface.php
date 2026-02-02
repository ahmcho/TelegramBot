<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

/**
 * Keyboard Builder Interface
 *
 * Contract for building keyboard layouts
 */
interface KeyboardBuilderInterface
{
    /**
     * Add a row of buttons
     * @param Button ...$buttons Variable number of buttons
     */
    public function addRow(Button ...$buttons): self;

    /**
     * Build and return JSON-encoded keyboard
     */
    public function build(): string;

    /**
     * Build and return array structure
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
