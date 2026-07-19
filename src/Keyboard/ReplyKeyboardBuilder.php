<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

use AhmCho\Telegram\Keyboard\Traits\JsonBuildTrait;

/**
 * Reply Keyboard Builder
 *
 * Builds reply keyboards for Telegram messages
 */
class ReplyKeyboardBuilder implements KeyboardBuilderInterface
{
    use JsonBuildTrait;

    /**
     * @var array<int, array<int, array<string, string>>>
     */
    private array $rows = [];

    private ReplyKeyboardOptions $options;

    public function __construct(?ReplyKeyboardOptions $options = null)
    {
        $this->options = $options ?? new ReplyKeyboardOptions();
    }

    public function addRow(Button ...$buttons): self
    {
        $this->rows[] = array_values(array_map(
            fn(Button $button): array => ['text' => $button->text],
            $buttons
        ));

        return $this;
    }

    public function toArray(): array
    {
        return [
            'keyboard' => $this->rows,
            'resize_keyboard' => $this->options->resizeKeyboard,
            'one_time_keyboard' => $this->options->oneTimeKeyboard,
            'selective' => $this->options->selective,
            'is_persistent' => $this->options->isPersistent
        ];
    }

    /**
     * Static factory for convenience
     */
    public static function create(?ReplyKeyboardOptions $options = null): self
    {
        return new self($options);
    }
}
