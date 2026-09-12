<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard\Traits;

use Override;

/**
 * JSON Build Trait
 *
 * Provides build() for any KeyboardBuilderInterface implementation via its toArray().
 */
trait JsonBuildTrait
{
    #[Override]
    public function build(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
