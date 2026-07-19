<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Invite Links Service
 *
 * Handles invite link management for Telegram chats and channels.
 */
class InviteLinksService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Create a new invite link
     *
     * @param array{chat_id: int|string, name?: string, expire_date?: int, member_limit?: int, creates_join_request?: bool} $params
     * @return array<string, mixed>
     */
    public function create(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::CREATE_CHAT_INVITE_LINK,
            $params
        );
    }

    /**
     * Edit an existing invite link
     *
     * @param array{chat_id: int|string, invite_link: string, name?: string, expire_date?: int, member_limit?: int, creates_join_request?: bool} $params
     * @return array<string, mixed>
     */
    public function edit(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::EDIT_CHAT_INVITE_LINK,
            $params
        );
    }

    /**
     * Revoke an invite link
     *
     * @param array{chat_id: int|string, invite_link: string} $params
     * @return array<string, mixed>
     */
    public function revoke(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::REVOKE_CHAT_INVITE_LINK,
            $params
        );
    }

    /**
     * Export an invite link as a file
     *
     * @param array{chat_id: int|string} $params
     */
    public function export(array $params): mixed
    {
        return $this->apiService->call(
            ApiMethod::EXPORT_CHAT_INVITE_LINK,
            $params
        );
    }

    /**
     * Get information about an invite link
     *
     * @param array{chat_id: int|string, invite_link: string} $params
     * @return array<string, mixed>
     */
    public function get(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_CHAT_INVITE_LINK,
            $params
        );
    }

    /**
     * Get statistics about invite links
     *
     * @param array{chat_id: int|string} $params
     * @return array<string, mixed>
     */
    public function getCounts(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_CHAT_INVITE_LINK_COUNTS,
            $params
        );
    }

    /**
     * Get members who joined via an invite link
     *
     * @param array{chat_id: int|string, invite_link: string} $params
     * @return array<string, mixed>
     */
    public function getMembers(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::GET_CHAT_INVITE_LINK_MEMBERS,
            $params
        );
    }

    /**
     * Edit a subscription invite link
     *
     * @param array{chat_id: int|string, invite_link: string, name?: string} $params
     * @return array<string, mixed>
     */
    public function editSubscription(array $params): array
    {
        return $this->apiService->call(
            ApiMethod::EDIT_CHAT_SUBSCRIPTION_INVITE_LINK,
            $params
        );
    }
}
