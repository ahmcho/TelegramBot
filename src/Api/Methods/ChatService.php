<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Chat Service
 *
 * Handles all chat-related Telegram API operations
 */
class ChatService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function sendAction(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SEND_CHAT_ACTION, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getChat(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_CHAT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getMember(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_CHAT_MEMBER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getAdministrators(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_CHAT_ADMINISTRATORS, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function getMemberCount(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::GET_CHAT_MEMBER_COUNT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function banMember(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::BAN_CHAT_MEMBER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function unbanMember(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::UNBAN_CHAT_MEMBER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function restrictMember(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::RESTRICT_CHAT_MEMBER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function promoteMember(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::PROMOTE_CHAT_MEMBER, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function leave(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::LEAVE_CHAT, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function pinMessage(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::PIN_CHAT_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function unpinMessage(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::UNPIN_CHAT_MESSAGE, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function unpinAllMessages(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::UNPIN_ALL_CHAT_MESSAGES, $params);
    }

    /**
     * Change the title of a chat
     *
     * @param array{chat_id: int|string, title: string} $params
     * @return mixed
     */
    public function setChatTitle(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_CHAT_TITLE, $params);
    }

    /**
     * Change the description of a group, supergroup or channel
     *
     * @param array{chat_id: int|string, description?: string} $params
     * @return mixed
     */
    public function setChatDescription(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_CHAT_DESCRIPTION, $params);
    }

    /**
     * Set a new profile photo for the chat
     *
     * @param array{chat_id: int|string, photo: mixed} $params
     * @return mixed
     */
    public function setChatPhoto(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_CHAT_PHOTO, $params);
    }

    /**
     * Delete the chat photo
     *
     * @param array{chat_id: int|string} $params
     * @return mixed
     */
    public function deleteChatPhoto(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::DELETE_CHAT_PHOTO, $params);
    }

    /**
     * Set default chat permissions for all members
     *
     * @param array{chat_id: int|string, permissions: array<string, bool>} $params
     * @return mixed
     */
    public function setChatPermissions(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::SET_CHAT_PERMISSIONS, $params);
    }

    /**
     * Get the current menu button
     *
     * @param array{chat_id?: int|string} $params
     * @return array<string, mixed>
     */
    public function getMenuButton(array $params = []): array
    {
        return $this->apiService->call(
            ApiMethod::GET_CHAT_MENU_BUTTON,
            $params
        );
    }

    /**
     * Change the menu button
     *
     * @param array{chat_id?: int|string, menu_button: array} $params
     * @return array<string, mixed>
     */
    public function setMenuButton(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::SET_CHAT_MENU_BUTTON,
            $params
        );
    }

    /**
     * Answer a callback query sent from an inline keyboard
     *
     * @param array{callback_query_id: string, text?: string, show_alert?: bool, url?: string, cache_time?: int} $params
     * @return mixed
     */
    public function answerCallbackQuery(array $params): mixed
    {
        return $this->apiService->call(ApiMethod::ANSWER_CALLBACK_QUERY, $params);
    }
}
