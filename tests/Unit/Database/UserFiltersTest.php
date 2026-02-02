<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Database\UserFilters;

/**
 * User Filters Tests
 *
 * Tests filter builder methods, filter array generation,
 * and default filter values.
 */
final class UserFiltersTest extends TestCase
{
    public function test_create_returns_default_filters(): void
    {
        $filters = UserFilters::create();

        $this->assertNull($filters->activeSince);
        $this->assertNull($filters->hasUsername);
        $this->assertNull($filters->isPremium);
        $this->assertNull($filters->includeBots);
        $this->assertNull($filters->limit);
    }

    public function test_withActiveSince_returns_new_instance(): void
    {
        $filters = UserFilters::create();
        $newFilters = $filters->withActiveSince('2024-01-01');

        $this->assertNotSame($filters, $newFilters);
        $this->assertSame('2024-01-01', $newFilters->activeSince);
        $this->assertNull($filters->activeSince); // Original unchanged
    }

    public function test_withHasUsername_returns_new_instance(): void
    {
        $filters = UserFilters::create();
        $newFilters = $filters->withHasUsername(true);

        $this->assertNotSame($filters, $newFilters);
        $this->assertTrue($newFilters->hasUsername);
        $this->assertNull($filters->hasUsername);
    }

    public function test_withIsPremium_returns_new_instance(): void
    {
        $filters = UserFilters::create();
        $newFilters = $filters->withIsPremium(false);

        $this->assertNotSame($filters, $newFilters);
        $this->assertFalse($newFilters->isPremium);
        $this->assertNull($filters->isPremium);
    }

    public function test_withIncludeBots_returns_new_instance(): void
    {
        $filters = UserFilters::create();
        $newFilters = $filters->withIncludeBots(true);

        $this->assertNotSame($filters, $newFilters);
        $this->assertTrue($newFilters->includeBots);
        $this->assertNull($filters->includeBots);
    }

    public function test_withLimit_returns_new_instance(): void
    {
        $filters = UserFilters::create();
        $newFilters = $filters->withLimit(50);

        $this->assertNotSame($filters, $newFilters);
        $this->assertSame(50, $newFilters->limit);
        $this->assertNull($filters->limit);
    }

    public function test_method_chaining_builds_complex_filters(): void
    {
        $filters = UserFilters::create()
            ->withActiveSince('2024-01-01')
            ->withHasUsername(true)
            ->withIsPremium(true)
            ->withLimit(100);

        $this->assertSame('2024-01-01', $filters->activeSince);
        $this->assertTrue($filters->hasUsername);
        $this->assertTrue($filters->isPremium);
        $this->assertSame(100, $filters->limit);
        $this->assertNull($filters->includeBots);
    }

    public function test_filters_are_readonly(): void
    {
        $filters = UserFilters::create();

        $reflection = new \ReflectionClass($filters);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_multiple_filters_are_independent(): void
    {
        $filters1 = UserFilters::create()->withLimit(10);
        $filters2 = UserFilters::create()->withLimit(50);

        $this->assertSame(10, $filters1->limit);
        $this->assertSame(50, $filters2->limit);
    }

    public function test_chaining_preserves_previous_filters(): void
    {
        $filters = UserFilters::create()
            ->withActiveSince('2024-01-01')
            ->withHasUsername(true)
            ->withLimit(100);

        $this->assertSame('2024-01-01', $filters->activeSince);
        $this->assertTrue($filters->hasUsername);
        $this->assertSame(100, $filters->limit);
    }

    public function test_constructor_is_private(): void
    {
        $reflection = new \ReflectionClass(UserFilters::class);
        $constructor = $reflection->getConstructor();

        $this->assertTrue($constructor->isPrivate());
    }
}
