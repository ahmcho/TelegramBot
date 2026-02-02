<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Database;

/**
 * User Repository Interface
 *
 * Contract for user data persistence operations
 */
interface UserRepositoryInterface
{
    /**
     * Save or update a user
     */
    public function save(UserEntity $user): bool;

    /**
     * Find user by Telegram ID
     */
    public function findByTelegramId(int $telegramId): ?UserEntity;

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?UserEntity;

    /**
     * Get all chat IDs for bulk messaging
     * @return array<int, int>
     */
    public function getAllChatIds(?UserFilters $filters = null): array;

    /**
     * Get all users with pagination and filters
     * @return array<int, UserEntity>
     */
    public function findAll(?UserFilters $filters = null, int $limit = 100, int $offset = 0): array;

    /**
     * Update user's last active timestamp
     */
    public function updateLastActive(int $telegramId): bool;

    /**
     * Delete user
     */
    public function delete(int $telegramId): bool;

    /**
     * Get statistics
     * @return array<string, int>
     */
    public function getStats(): array;
}
