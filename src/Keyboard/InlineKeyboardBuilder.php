<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

use AhmCho\Telegram\Keyboard\Traits\JsonBuildTrait;

/**
 * Inline Keyboard Builder
 *
 * Builds inline keyboards for Telegram messages
 */
class InlineKeyboardBuilder implements KeyboardBuilderInterface
{
    use JsonBuildTrait;

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $rows = [];

    public function addRow(Button ...$buttons): self
    {
        $this->rows[] = array_map(
            fn(Button $button) => $button->toArray(),
            $buttons
        );

        return $this;
    }

    public function toArray(): array
    {
        return ['inline_keyboard' => $this->rows];
    }

    /**
     * Static factory for convenience
     */
    public static function create(): self
    {
        return new self();
    }
}
