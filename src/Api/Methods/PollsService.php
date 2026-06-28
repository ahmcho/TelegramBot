<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;

/**
 * Poll Service
 *
 * Handles poll-related Telegram Bot API methods.
 */
class PollsService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Send a poll to a chat
     *
     * @param array{chat_id: int|string, question: string, options: array<string>, parse_mode?: string, is_closed?: bool, type?: 'regular'|'quiz', allows_multiple_answers?: bool, correct_option_id?: int, explanation?: string, explanation_parse_mode?: string, open_period?: int, close_date?: int, is_anonymous?: bool, message_thread_id?: int, reply_parameters?: array} $params
     * @return array<string, mixed>
     */
    public function send(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::SEND_POLL,
            $params
        );
    }

    /**
     * Stop a poll
     *
     * @param array{chat_id: int|string, message_id: int, business_connection_id?: string} $params
     * @return array<string, mixed>
     */
    public function stop(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::STOP_POLL,
            $params
        );
    }

    /**
     * Close a poll
     *
     * @param array{chat_id: int|string, message_id: int} $params
     * @return array<string, mixed>
     */
    public function close(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::CLOSE_POLL,
            $params
        );
    }
}
