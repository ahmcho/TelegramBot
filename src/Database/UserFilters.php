<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Database;

/**
 * User Filters Value Object
 *
 * Fluent interface for building database query filters
 */
readonly class UserFilters
{
    private function __construct(
        public readonly ?string $activeSince,
        public readonly ?bool $hasUsername,
        public readonly ?bool $isPremium,
        public readonly ?bool $includeBots,
        public readonly ?int $limit
    ) {
    }

    public static function create(): self
    {
        return new self(
            activeSince: null,
            hasUsername: null,
            isPremium: null,
            includeBots: null,
            limit: null
        );
    }

    public function withActiveSince(string $date): self
    {
        return new self(
            activeSince: $date,
            hasUsername: $this->hasUsername,
            isPremium: $this->isPremium,
            includeBots: $this->includeBots,
            limit: $this->limit
        );
    }

    public function withHasUsername(bool $hasUsername): self
    {
        return new self(
            activeSince: $this->activeSince,
            hasUsername: $hasUsername,
            isPremium: $this->isPremium,
            includeBots: $this->includeBots,
            limit: $this->limit
        );
    }

    public function withIsPremium(bool $isPremium): self
    {
        return new self(
            activeSince: $this->activeSince,
            hasUsername: $this->hasUsername,
            isPremium: $isPremium,
            includeBots: $this->includeBots,
            limit: $this->limit
        );
    }

    public function withIncludeBots(bool $includeBots): self
    {
        return new self(
            activeSince: $this->activeSince,
            hasUsername: $this->hasUsername,
            isPremium: $this->isPremium,
            includeBots: $includeBots,
            limit: $this->limit
        );
    }

    public function withLimit(int $limit): self
    {
        return new self(
            activeSince: $this->activeSince,
            hasUsername: $this->hasUsername,
            isPremium: $this->isPremium,
            includeBots: $this->includeBots,
            limit: $limit
        );
    }
}
