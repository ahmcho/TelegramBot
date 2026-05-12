<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Enums\ChatAction;

/**
 * ChatAction Enum Tests
 */
final class ChatActionTest extends TestCase
{
    public function test_enum_values_are_correct(): void
    {
        $this->assertSame('typing', ChatAction::TYPING->value);
        $this->assertSame('upload_photo', ChatAction::UPLOAD_PHOTO->value);
        $this->assertSame('record_video', ChatAction::RECORD_VIDEO->value);
        $this->assertSame('upload_video', ChatAction::UPLOAD_VIDEO->value);
        $this->assertSame('record_voice', ChatAction::RECORD_VOICE->value);
        $this->assertSame('upload_voice', ChatAction::UPLOAD_VOICE->value);
        $this->assertSame('upload_document', ChatAction::UPLOAD_DOCUMENT->value);
        $this->assertSame('find_location', ChatAction::FIND_LOCATION->value);
        $this->assertSame('record_video_note', ChatAction::RECORD_VIDEO_NOTE->value);
        $this->assertSame('upload_video_note', ChatAction::UPLOAD_VIDEO_NOTE->value);
    }

    public function test_is_typing_action_for_typing(): void
    {
        $this->assertTrue(ChatAction::TYPING->isTypingAction());
    }

    public function test_is_not_typing_action_for_other_actions(): void
    {
        $this->assertFalse(ChatAction::UPLOAD_PHOTO->isTypingAction());
        $this->assertFalse(ChatAction::RECORD_VIDEO->isTypingAction());
        $this->assertFalse(ChatAction::FIND_LOCATION->isTypingAction());
    }

    public function test_is_upload_action_for_upload_methods(): void
    {
        $this->assertTrue(ChatAction::UPLOAD_PHOTO->isUploadAction());
        $this->assertTrue(ChatAction::UPLOAD_VIDEO->isUploadAction());
        $this->assertTrue(ChatAction::UPLOAD_VOICE->isUploadAction());
        $this->assertTrue(ChatAction::UPLOAD_DOCUMENT->isUploadAction());
        $this->assertTrue(ChatAction::UPLOAD_VIDEO_NOTE->isUploadAction());
    }

    public function test_is_not_upload_action_for_non_upload_methods(): void
    {
        $this->assertFalse(ChatAction::TYPING->isUploadAction());
        $this->assertFalse(ChatAction::RECORD_VIDEO->isUploadAction());
        $this->assertFalse(ChatAction::RECORD_VOICE->isUploadAction());
        $this->assertFalse(ChatAction::FIND_LOCATION->isUploadAction());
        $this->assertFalse(ChatAction::RECORD_VIDEO_NOTE->isUploadAction());
    }

    public function test_get_duration_for_typing(): void
    {
        $this->assertSame(5, ChatAction::TYPING->getDuration());
    }

    public function test_get_duration_for_upload_photo(): void
    {
        $this->assertSame(5, ChatAction::UPLOAD_PHOTO->getDuration());
    }

    public function test_get_duration_for_video_operations(): void
    {
        $this->assertSame(10, ChatAction::RECORD_VIDEO->getDuration());
        $this->assertSame(10, ChatAction::UPLOAD_VIDEO->getDuration());
    }

    public function test_get_duration_for_voice_operations(): void
    {
        $this->assertSame(10, ChatAction::RECORD_VOICE->getDuration());
        $this->assertSame(10, ChatAction::UPLOAD_VOICE->getDuration());
    }

    public function test_get_duration_for_document_upload(): void
    {
        $this->assertSame(10, ChatAction::UPLOAD_DOCUMENT->getDuration());
    }

    public function test_get_duration_for_location(): void
    {
        $this->assertSame(10, ChatAction::FIND_LOCATION->getDuration());
    }

    public function test_get_duration_for_video_note_operations(): void
    {
        $this->assertSame(10, ChatAction::RECORD_VIDEO_NOTE->getDuration());
        $this->assertSame(10, ChatAction::UPLOAD_VIDEO_NOTE->getDuration());
    }

    public function test_enum_is_string_backed(): void
    {
        $this->assertIsString(ChatAction::TYPING->value);
        $this->assertIsString(ChatAction::UPLOAD_PHOTO->value);
    }

    public function test_from_method_returns_correct_enum(): void
    {
        $action = ChatAction::from('typing');
        $this->assertSame(ChatAction::TYPING, $action);

        $action = ChatAction::from('upload_photo');
        $this->assertSame(ChatAction::UPLOAD_PHOTO, $action);
    }

    public function test_try_from_method_returns_correct_enum(): void
    {
        $action = ChatAction::tryFrom('typing');
        $this->assertSame(ChatAction::TYPING, $action);

        $invalid = ChatAction::tryFrom('invalid_action');
        $this->assertNull($invalid);
    }

    public function test_cases_method_returns_all_enum_cases(): void
    {
        $cases = ChatAction::cases();
        $this->assertCount(10, $cases);
        $this->assertContains(ChatAction::TYPING, $cases);
        $this->assertContains(ChatAction::UPLOAD_PHOTO, $cases);
        $this->assertContains(ChatAction::RECORD_VIDEO, $cases);
    }

    public function test_enum_has_all_expected_methods(): void
    {
        $action = ChatAction::TYPING;
        $this->assertTrue(method_exists($action, 'isTypingAction'));
        $this->assertTrue(method_exists($action, 'isUploadAction'));
        $this->assertTrue(method_exists($action, 'getDuration'));
    }
}
