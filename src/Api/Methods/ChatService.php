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
}
