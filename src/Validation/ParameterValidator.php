<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Validation;

/**
 * Parameter Validator
 *
 * Validates request parameters before API calls
 */
class ParameterValidator
{
    /**
     * Validate parameters against required and optional rules
     *
     * @param array<string, mixed> $params
     * @param array<string> $required
     * @param array<string> $optional
     * @return array{valid: bool, errors: array<string>}
     */
    public function validate(array $params, array $required = [], array $optional = []): array
    {
        $errors = [];
        $allowed = array_merge($required, $optional);

        // Check required parameters
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                $errors[] = "Required parameter '{$param}' is missing";
            }
        }

        // Check for unknown parameters (strict mode)
        if (!empty($allowed)) {
            foreach (array_keys($params) as $param) {
                if (!in_array($param, $allowed, true)) {
                    $errors[] = "Unknown parameter '{$param}'";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate a chat_id parameter
     *
     * @param mixed $chatId
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateChatId(mixed $chatId): array
    {
        $errors = [];

        if (!is_int($chatId) && !is_string($chatId)) {
            $errors[] = "chat_id must be an integer or string, got " . gettype($chatId);
        }

        if (is_string($chatId) && !str_starts_with($chatId, '@') && !str_starts_with($chatId, '-')) {
            // Could be a username or channel link, but let's validate format
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,31}$/', $chatId) !== 1) {
                $errors[] = "Invalid chat_id format";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate a message_id parameter
     *
     * @param mixed $messageId
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateMessageId(mixed $messageId): array
    {
        $errors = [];

        if (!is_int($messageId) || $messageId <= 0) {
            $errors[] = "message_id must be a positive integer";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate text parameter
     *
     * @param mixed $text
     * @param int $maxLength
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateText(mixed $text, int $maxLength = 4096): array
    {
        $errors = [];

        if (!is_string($text)) {
            $errors[] = "text must be a string";
        } elseif (mb_strlen($text) > $maxLength) {
            $errors[] = "text exceeds maximum length of {$maxLength} characters";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate file input (URL, file ID, or CURLFile)
     *
     * @param mixed $file
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateFile(mixed $file): array
    {
        $errors = [];

        if (!is_string($file) && !($file instanceof \CURLFile)) {
            $errors[] = "File must be a string (URL or file ID) or CURLFile instance";
        }

        if (is_string($file) && empty($file)) {
            $errors[] = "File path/URL cannot be empty";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate reply_markup structure
     *
     * @param mixed $replyMarkup
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateReplyMarkup(mixed $replyMarkup): array
    {
        $errors = [];

        if ($replyMarkup === null || $replyMarkup === false) {
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        if (!is_string($replyMarkup) && !is_array($replyMarkup)) {
            $errors[] = "reply_markup must be a JSON string or array";
        }

        if (is_string($replyMarkup)) {
            json_decode($replyMarkup);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "reply_markup is not valid JSON: " . json_last_error_msg();
            }
        }

        if (is_array($replyMarkup) && empty($replyMarkup)) {
            $errors[] = "reply_markup cannot be an empty array";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate parse_mode parameter
     *
     * @param mixed $parseMode
     * @return array{valid: bool, errors: array<string>}
     */
    public function validateParseMode(mixed $parseMode): array
    {
        $errors = [];

        if ($parseMode === null) {
            return [
                'valid' => true,
                'errors' => []
            ];
        }

        if (!is_string($parseMode)) {
            $errors[] = "parse_mode must be a string";
        } elseif (!in_array($parseMode, ['MarkdownV2', 'HTML', 'Markdown'], true)) {
            $errors[] = "parse_mode must be one of: MarkdownV2, HTML, Markdown";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
