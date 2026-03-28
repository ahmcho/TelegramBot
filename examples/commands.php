<?php

declare(strict_types=1);

function handleFeatures(TelegramBot \$bot, int \$chatId): void
{
    \$features = "* Bot Features\n\n"
        . "✅ Commands /start, /help, /keyboard, /photo, /dice\n"
        . "✅ Inline keyboards\n"
        . "✅ Callback queries\n"
        . "✅ Custom reply keyboards\n"
        . "✅ Media sending : photos, videos, etc.\n"
        . "✅ Message formatting - (Markdown, HTML)\n"
        . "✅ And much more!";

    \$bot->messages()->send([
        'chat_id' => \$chatId,
        'text' => \$features
    ]);
}
