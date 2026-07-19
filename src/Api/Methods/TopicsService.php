<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Topics Service
 *
 * Handles forum topics/threads functionality for Telegram supergroups.
 */
class TopicsService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Create a forum topic
     *
     * @param array{chat_id: int|string, name: string, icon_color?: int, icon_custom_emoji_id?: string} $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::CREATE_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Edit a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int, name?: string, icon_custom_emoji_id?: string} $params
     */
    public function edit(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::EDIT_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Close a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int} $params
     */
    public function close(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::CLOSE_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Reopen a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int} $params
     */
    public function reopen(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::REOPEN_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Delete a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int} $params
     */
    public function delete(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::DELETE_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Unpin all messages from a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int} $params
     */
    public function unpinAll(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::UNPIN_ALL_FORUM_TOPIC_MESSAGES,
            $params
        );
    }

    /**
     * Edit the general forum topic
     *
     * @param array{chat_id: int|string, name: string} $params
     */
    public function editGeneral(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::EDIT_GENERAL_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Close the general forum topic
     *
     * @param array{chat_id: int|string} $params
     */
    public function closeGeneral(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::CLOSE_GENERAL_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Reopen the general forum topic
     *
     * @param array{chat_id: int|string} $params
     */
    public function reopenGeneral(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::REOPEN_GENERAL_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Hide the general forum topic
     *
     * @param array{chat_id: int|string} $params
     */
    public function hideGeneral(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::HIDE_GENERAL_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Unhide the general forum topic
     *
     * @param array{chat_id: int|string} $params
     */
    public function unhideGeneral(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::UNHIDE_GENERAL_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Get information about a forum topic
     *
     * @param array{chat_id: int|string, message_thread_id: int} $params
     * @return array<string, mixed>
     */
    public function get(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_FORUM_TOPIC,
            $params
        );
    }

    /**
     * Get all forum topics in a chat
     *
     * @param array{chat_id: int|string} $params
     * @return array<string, mixed>
     */
    public function getAll(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_FORUM_TOPICS,
            $params
        );
    }

    /**
     * Get available forum topic icon stickers
     *
     * @return array<string, mixed>
     */
    public function getIconStickers(): array
    {
        return $this->apiService->call(
            ApiMethod::GET_FORUM_TOPIC_ICON_STICKERS
        );
    }
}
