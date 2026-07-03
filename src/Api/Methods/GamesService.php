<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Games Service
 *
 * Handles Telegram Bot API game methods: sending games and
 * reading/writing game scores.
 */
class GamesService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Send a game
     *
     * @param array{chat_id: int, message_thread_id?: int, game_short_name: string, disable_notification?: bool, protect_content?: bool, message_effect_id?: string, reply_parameters?: array, reply_markup?: string} $params
     * @return array<string, mixed>
     */
    public function sendGame(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_GAME, $params);
    }

    /**
     * Set the score of the specified user in a game message
     *
     * Exactly one of chat_id+message_id or inline_message_id must be provided.
     *
     * @param array{user_id: int, score: int, force?: bool, disable_edit_message?: bool, chat_id?: int, message_id?: int, inline_message_id?: string} $params
     * @return mixed Message on success, or True if the message is not an inline message
     */
    public function setGameScore(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_GAME_SCORE, $params);
    }

    /**
     * Get data for high score tables
     *
     * Exactly one of chat_id+message_id or inline_message_id must be provided.
     *
     * @param array{user_id: int, chat_id?: int, message_id?: int, inline_message_id?: string} $params
     * @return array<int, array<string, mixed>> Array of GameHighScore objects
     */
    public function getGameHighScores(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_GAME_HIGH_SCORES, $params);
    }
}
