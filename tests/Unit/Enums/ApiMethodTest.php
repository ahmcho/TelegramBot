<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * ApiMethod Enum Tests
 */
final class ApiMethodTest extends TestCase
{
    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('getUpdates', ApiMethod::GET_UPDATES->value);
        $this->assertSame('sendMessage', ApiMethod::SEND_MESSAGE->value);
        $this->assertSame('sendPhoto', ApiMethod::SEND_PHOTO->value);
        $this->assertSame('sendDocument', ApiMethod::SEND_DOCUMENT->value);
        $this->assertSame('setWebhook', ApiMethod::SET_WEBHOOK->value);
    }

    public function test_is_bulk_capable_for_send_message(): void
    {
        $this->assertTrue(ApiMethod::SEND_MESSAGE->isBulkCapable());
    }

    public function test_is_bulk_capable_for_media_methods(): void
    {
        $this->assertTrue(ApiMethod::SEND_PHOTO->isBulkCapable());
        $this->assertTrue(ApiMethod::SEND_DOCUMENT->isBulkCapable());
        $this->assertTrue(ApiMethod::SEND_VIDEO->isBulkCapable());
        $this->assertTrue(ApiMethod::SEND_AUDIO->isBulkCapable());
        $this->assertTrue(ApiMethod::SEND_VOICE->isBulkCapable());
        $this->assertTrue(ApiMethod::SEND_ANIMATION->isBulkCapable());
        $this->assertTrue(ApiMethod::COPY_MESSAGE->isBulkCapable());
    }

    public function test_is_not_bulk_capable_for_non_bulk_methods(): void
    {
        $this->assertFalse(ApiMethod::GET_UPDATES->isBulkCapable());
        $this->assertFalse(ApiMethod::GET_ME->isBulkCapable());
        $this->assertFalse(ApiMethod::SET_WEBHOOK->isBulkCapable());
        $this->assertFalse(ApiMethod::GET_WEBHOOK_INFO->isBulkCapable());
        $this->assertFalse(ApiMethod::BAN_CHAT_MEMBER->isBulkCapable());
    }

    public function test_required_params_for_send_message(): void
    {
        $required = ApiMethod::SEND_MESSAGE->requiredParams();
        $this->assertContains('chat_id', $required);
        $this->assertContains('text', $required);
        $this->assertCount(2, $required);
    }

    public function test_required_params_for_send_photo(): void
    {
        $required = ApiMethod::SEND_PHOTO->requiredParams();
        $this->assertContains('chat_id', $required);
        $this->assertContains('photo', $required);
        $this->assertCount(2, $required);
    }

    public function test_required_params_for_copy_message(): void
    {
        $required = ApiMethod::COPY_MESSAGE->requiredParams();
        $this->assertContains('chat_id', $required);
        $this->assertContains('from_chat_id', $required);
        $this->assertContains('message_id', $required);
        $this->assertCount(3, $required);
    }

    public function test_required_params_for_non_send_methods(): void
    {
        // Default case returns ['chat_id']
        $this->assertSame(['chat_id'], ApiMethod::GET_UPDATES->requiredParams());
        $this->assertSame(['chat_id'], ApiMethod::GET_ME->requiredParams());
        $this->assertSame(['chat_id'], ApiMethod::GET_WEBHOOK_INFO->requiredParams());
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertIsString(ApiMethod::SEND_MESSAGE->value);
    }

    public function test_enum_has_all_expected_methods(): void
    {
        $method = ApiMethod::SEND_MESSAGE;
        $this->assertTrue(method_exists($method, 'isBulkCapable'));
        $this->assertTrue(method_exists($method, 'requiredParams'));
    }

    public function test_from_method_returns_correct_enum(): void
    {
        $method = ApiMethod::from('sendMessage');
        $this->assertSame(ApiMethod::SEND_MESSAGE, $method);
    }

    public function test_try_from_method_returns_correct_enum(): void
    {
        $method = ApiMethod::tryFrom('sendMessage');
        $this->assertSame(ApiMethod::SEND_MESSAGE, $method);

        $invalid = ApiMethod::tryFrom('invalidMethod');
        $this->assertNull($invalid);
    }

    public function test_cases_method_returns_all_enum_cases(): void
    {
        $cases = ApiMethod::cases();
        $this->assertIsArray($cases);
        $this->assertNotEmpty($cases);
        $this->assertInstanceOf(ApiMethod::class, $cases[0]);
    }
}
