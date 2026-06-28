<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Traits;

use AhmCho\Telegram\Validation\ParameterValidator;

/**
 * Validation Helper Trait
 *
 * Provides validation methods for service classes
 */
trait ValidationHelperTrait
{
    /**
     * @var ParameterValidator|null
     */
    private ?ParameterValidator $validator = null;

    /**
     * Get the validator instance
     */
    private function validator(): ParameterValidator
    {
        return $this->validator ??= new ParameterValidator();
    }

    /**
     * Validate parameters and throw exception if invalid
     *
     * @param array<string, mixed> $params
     * @param array<string> $required
     * @param array<string> $optional
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function validateParams(
        array $params,
        array $required = [],
        array $optional = []
    ): void {
        $result = $this->validator()->validate($params, $required, $optional);

        if (!$result['valid']) {
            throw new \InvalidArgumentException(
                'Parameter validation failed: ' . implode(', ', $result['errors'])
            );
        }
    }

    /**
     * Validate chat_id parameter
     *
     * @param mixed $chatId
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function validateChatId(mixed $chatId): void
    {
        $result = $this->validator()->validateChatId($chatId);

        if (!$result['valid']) {
            throw new \InvalidArgumentException(
                'chat_id validation failed: ' . implode(', ', $result['errors'])
            );
        }
    }

    /**
     * Validate message_id parameter
     *
     * @param mixed $messageId
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function validateMessageId(mixed $messageId): void
    {
        $result = $this->validator()->validateMessageId($messageId);

        if (!$result['valid']) {
            throw new \InvalidArgumentException(
                'message_id validation failed: ' . implode(', ', $result['errors'])
            );
        }
    }
}
