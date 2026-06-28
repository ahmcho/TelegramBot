<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Enums;

/**
 * Telegram Bot API Methods Enumeration
 *
 * Defines all supported Telegram Bot API methods as type-safe enum values.
 * Uses backing store for method names to ensure correctness in API calls.
 */
enum ApiMethod: string
{
    // Updates
    case GET_UPDATES = 'getUpdates';

        // Message methods
    case SEND_MESSAGE = 'sendMessage';
    case EDIT_MESSAGE_TEXT = 'editMessageText';
    case EDIT_MESSAGE_CAPTION = 'editMessageCaption';
    case DELETE_MESSAGE = 'deleteMessage';
    case FORWARD_MESSAGE = 'forwardMessage';
    case COPY_MESSAGE = 'copyMessage';

        // Media methods
    case SEND_PHOTO = 'sendPhoto';
    case SEND_DOCUMENT = 'sendDocument';
    case SEND_VIDEO = 'sendVideo';
    case SEND_AUDIO = 'sendAudio';
    case SEND_VOICE = 'sendVoice';
    case SEND_ANIMATION = 'sendAnimation';
    case SEND_STICKER = 'sendSticker';
    case SEND_LOCATION = 'sendLocation';
    case SEND_VENUE = 'sendVenue';
    case SEND_CONTACT = 'sendContact';
    case SEND_POLL = 'sendPoll';
    case STOP_POLL = 'stopPoll';
    case CLOSE_POLL = 'closePoll';
    case SEND_DICE = 'sendDice';

        // Chat actions
    case SEND_CHAT_ACTION = 'sendChatAction';

        // Bot info
    case GET_ME = 'getMe';

        // Chat operations
    case GET_CHAT = 'getChat';
    case GET_CHAT_MEMBER = 'getChatMember';
    case GET_CHAT_ADMINISTRATORS = 'getChatAdministrators';
    case GET_CHAT_MEMBER_COUNT = 'getChatMemberCount';
    case BAN_CHAT_MEMBER = 'banChatMember';
    case UNBAN_CHAT_MEMBER = 'unbanChatMember';
    case RESTRICT_CHAT_MEMBER = 'restrictChatMember';
    case PROMOTE_CHAT_MEMBER = 'promoteChatMember';
    case LEAVE_CHAT = 'leaveChat';

        // Message management
    case PIN_CHAT_MESSAGE = 'pinChatMessage';
    case UNPIN_CHAT_MESSAGE = 'unpinChatMessage';
    case UNPIN_ALL_CHAT_MESSAGES = 'unpinAllChatMessages';

        // Chat settings
    case SET_CHAT_TITLE = 'setChatTitle';
    case SET_CHAT_DESCRIPTION = 'setChatDescription';
    case SET_CHAT_PHOTO = 'setChatPhoto';
    case DELETE_CHAT_PHOTO = 'deleteChatPhoto';
    case SET_CHAT_PERMISSIONS = 'setChatPermissions';
    case GET_CHAT_MENU_BUTTON = 'getChatMenuButton';
    case SET_CHAT_MENU_BUTTON = 'setChatMenuButton';

        // Forum topics
    case CREATE_FORUM_TOPIC = 'createForumTopic';
    case EDIT_FORUM_TOPIC = 'editForumTopic';
    case CLOSE_FORUM_TOPIC = 'closeForumTopic';
    case REOPEN_FORUM_TOPIC = 'reopenForumTopic';
    case DELETE_FORUM_TOPIC = 'deleteForumTopic';
    case UNPIN_ALL_FORUM_TOPIC_MESSAGES = 'unpinAllForumTopicMessages';
    case EDIT_GENERAL_FORUM_TOPIC = 'editGeneralForumTopic';
    case CLOSE_GENERAL_FORUM_TOPIC = 'closeGeneralForumTopic';
    case REOPEN_GENERAL_FORUM_TOPIC = 'reopenGeneralForumTopic';
    case HIDE_GENERAL_FORUM_TOPIC = 'hideGeneralForumTopic';
    case UNHIDE_GENERAL_FORUM_TOPIC = 'unhideGeneralForumTopic';
    case GET_FORUM_TOPIC = 'getForumTopic';
    case GET_FORUM_TOPICS = 'getForumTopics';
    case GET_FORUM_TOPIC_ICON_STICKERS = 'getForumTopicIconStickers';

        // Webhook management
    case SET_WEBHOOK = 'setWebhook';
    case GET_WEBHOOK_INFO = 'getWebhookInfo';
    case DELETE_WEBHOOK = 'deleteWebhook';

        // Callback queries
    case ANSWER_CALLBACK_QUERY = 'answerCallbackQuery';
    case ANSWER_INLINE_QUERY = 'answerInlineQuery';

        // Games
    case SEND_GAME = 'sendGame';
    case SET_GAME_SCORE = 'setGameScore';
    case GET_GAME_HIGH_SCORES = 'getGameHighScores';

        // Payments
    case SEND_INVOICE = 'sendInvoice';

    /**
     * Check if this method supports bulk operations
     */
    public function isBulkCapable(): bool
    {
        return in_array($this, [
            self::SEND_MESSAGE,
            self::SEND_PHOTO,
            self::SEND_DOCUMENT,
            self::SEND_VIDEO,
            self::SEND_AUDIO,
            self::SEND_VOICE,
            self::SEND_ANIMATION,
            self::COPY_MESSAGE,
        ], true);
    }

    /**
     * Get required parameters for this method
     * @return array<string>
     */
    public function requiredParams(): array
    {
        return match($this) {
            self::SEND_MESSAGE => ['chat_id', 'text'],
            self::SEND_PHOTO => ['chat_id', 'photo'],
            self::SEND_DOCUMENT => ['chat_id', 'document'],
            self::SEND_VIDEO => ['chat_id', 'video'],
            self::SEND_AUDIO => ['chat_id', 'audio'],
            self::SEND_VOICE => ['chat_id', 'voice'],
            self::SEND_ANIMATION => ['chat_id', 'animation'],
            self::SEND_STICKER => ['chat_id', 'sticker'],
            self::COPY_MESSAGE => ['chat_id', 'from_chat_id', 'message_id'],
            self::EDIT_MESSAGE_TEXT => ['chat_id', 'message_id', 'text'],
            self::EDIT_MESSAGE_CAPTION => ['chat_id', 'message_id'],
            self::DELETE_MESSAGE => ['chat_id', 'message_id'],
            self::FORWARD_MESSAGE => ['chat_id', 'from_chat_id', 'message_id'],
            default => ['chat_id'],
        };
    }

    /**
     * Check if this method supports media uploads
     */
    public function supportsMedia(): bool
    {
        return str_starts_with($this->value, 'send') &&
               in_array(substr($this->value, 4), [
                   'Photo', 'Video', 'Audio', 'Document', 'Animation', 'Voice', 'Sticker'
               ], true);
    }
}
