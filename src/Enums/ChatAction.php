<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Enums;

/**
 * Chat Action Enumeration
 *
 * Defines supported chat actions for sendChatAction
 */
enum ChatAction: string
{
    case TYPING = 'typing';
    case UPLOAD_PHOTO = 'upload_photo';
    case RECORD_VIDEO = 'record_video';
    case UPLOAD_VIDEO = 'upload_video';
    case RECORD_VOICE = 'record_voice';
    case UPLOAD_VOICE = 'upload_voice';
    case UPLOAD_DOCUMENT = 'upload_document';
    case FIND_LOCATION = 'find_location';
    case RECORD_VIDEO_NOTE = 'record_video_note';
    case UPLOAD_VIDEO_NOTE = 'upload_video_note';

    /**
     * Check if this is a typing action
     */
    public function isTypingAction(): bool
    {
        return $this === self::TYPING;
    }

    /**
     * Check if this is an upload action
     */
    public function isUploadAction(): bool
    {
        return str_starts_with($this->value, 'upload_');
    }

    /**
     * Get the typical duration for this action in seconds
     */
    public function getDuration(): int
    {
        return match($this) {
            self::TYPING => 5,
            self::UPLOAD_PHOTO => 5,
            self::RECORD_VIDEO, self::UPLOAD_VIDEO => 10,
            self::RECORD_VOICE, self::UPLOAD_VOICE => 10,
            self::UPLOAD_DOCUMENT => 10,
            self::FIND_LOCATION => 10,
            self::RECORD_VIDEO_NOTE, self::UPLOAD_VIDEO_NOTE => 10,
        };
    }
}
