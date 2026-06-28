<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

/**
 * Multi-Language Bot Example
 *
 * Demonstrates internationalization (i18n) with:
 * - Language detection
 * - Translated messages
 * - Language switching
 */

/**
 * Simple translation system
 */
class Translator
{
    private string $language = 'en';

    /**
     * Translations
     */
    private array $translations = [
        'en' => [
            'welcome' => "Welcome to the Multi-Language Bot! 🌍\n\n",
            'select_language' => "Please select your language:",
            'language_changed' => "Language changed to English! 🇬🇧",
            'language_set' => "Your language is now set to: ",
            'current_language' => "Current language: English 🇬🇧",
            'commands' => "Commands:\n",
            'help_text' => "Available Commands:\n/start - Start the bot\n/lang - Change language\n/help - Show this help",
            'cmd_start' => 'Start the bot',
            'cmd_lang' => 'Change language',
            'cmd_help' => 'Show help',
            'menu_features' => '✨ Features',
            'menu_settings' => '⚙️ Settings',
            'menu_about' => 'ℹ️ About',
        ],
        'es' => [
            'welcome' => "¡Bienvenido al Bot Multiidioma! 🌍\n\n",
            'select_language' => "Por favor, selecciona tu idioma:",
            'language_changed' => "¡Idioma cambiado a Español! 🇪🇸",
            'language_set' => "Tu idioma ahora está configurado en: ",
            'current_language' => "Idioma actual: Español 🇪🇸",
            'commands' => "Comandos:\n",
            'help_text' => "Comandos Disponibles:\n/start - Iniciar el bot\n/lang - Cambiar idioma\n/help - Mostrar esta ayuda",
            'cmd_start' => 'Iniciar el bot',
            'cmd_lang' => 'Cambiar idioma',
            'cmd_help' => 'Mostrar ayuda',
            'menu_features' => '✨ Características',
            'menu_settings' => '⚙️ Configuración',
            'menu_about' => 'ℹ️ Acerca de',
        ],
        'fr' => [
            'welcome' => "Bienvenue sur le Bot Multilingue! 🌍\n\n",
            'select_language' => "Veuillez sélectionner votre langue:",
            'language_changed' => "Langue changée en Français! 🇫🇷",
            'language_set' => "Votre langue est maintenant configurée sur: ",
            'current_language' => "Langue actuelle: Français 🇫🇷",
            'commands' => "Commandes:\n",
            'help_text' => "Commandes Disponibles:\n/start - Démarrer le bot\n/lang - Changer de langue\n/help - Afficher cette aide",
            'cmd_start' => 'Démarrer le bot',
            'cmd_lang' => 'Changer de langue',
            'cmd_help' => 'Afficher l\'aide',
            'menu_features' => '✨ Fonctionnalités',
            'menu_settings' => '⚙️ Paramètres',
            'menu_about' => 'ℹ️ À propos',
        ],
        'de' => [
            'welcome' => "Willkommen beim mehrsprachigen Bot! 🌍\n\n",
            'select_language' => "Bitte wählen Sie Ihre Sprache:",
            'language_changed' => "Sprache geändert zu Deutsch! 🇩🇪",
            'language_set' => "Ihre Sprache ist jetzt eingestellt auf: ",
            'current_language' => "Aktuelle Sprache: Deutsch 🇩🇪",
            'commands' => "Befehle:\n",
            'help_text' => "Verfügbare Befehle:\n/start - Bot starten\n/lang - Sprache ändern\n/help - Hilfe anzeigen",
            'cmd_start' => 'Bot starten',
            'cmd_lang' => 'Sprache ändern',
            'cmd_help' => 'Hilfe anzeigen',
            'menu_features' => '✨ Funktionen',
            'menu_settings' => '⚙️ Einstellungen',
            'menu_about' => 'ℹ️ Über',
        ],
        'ar' => [
            'welcome' => "مرحباً بك في البوت متعدد اللغات! 🌍\n\n",
            'select_language' => "الرجاء تحديد لغتك:",
            'language_changed' => "تم تغيير اللغة إلى العربية! 🇸🇦",
            'language_set' => "لغتك الآن مضبوطة على: ",
            'current_language' => "اللغة الحالية: العربية 🇸🇦",
            'commands' => "الأوامر:\n",
            'help_text' => "الأوامر المتاحة:\n/start - بدء البوت\n/lang - تغيير اللغة\n/help - عرض هذه المساعدة",
            'cmd_start' => 'بدء البوت',
            'cmd_lang' => 'تغيير اللغة',
            'cmd_help' => 'عرض المساعدة',
            'menu_features' => '✨ الميزات',
            'menu_settings' => '⚙️ الإعدادات',
            'menu_about' => 'ℹ️ حول',
        ],
        'zh' => [
            'welcome' => "欢迎来到多语言机器人! 🌍\n\n",
            'select_language' => "请选择您的语言:",
            'language_changed' => "语言已更改为中文! 🇨🇳",
            'language_set' => "您的语言现在设置为: ",
            'current_language' => "当前语言: 中文 🇨🇳",
            'commands' => "命令:\n",
            'help_text' => "可用命令:\n/start - 启动机器人\n/lang - 更改语言\n/help - 显示此帮助",
            'cmd_start' => '启动机器人',
            'cmd_lang' => '更改语言',
            'cmd_help' => '显示帮助',
            'menu_features' => '✨ 功能',
            'menu_settings' => '⚙️ 设置',
            'menu_about' => 'ℹ️ 关于',
        ],
    ];

    /**
     * Supported languages
     */
    private array $languages = [
        'en' => '🇬🇧 English',
        'es' => '🇪🇸 Español',
        'fr' => '🇫🇷 Français',
        'de' => '🇩🇪 Deutsch',
        'ar' => '🇸🇦 العربية',
        'zh' => '🇨🇳 中文',
    ];

    public function setLanguage(string $lang): void
    {
        if (isset($this->translations[$lang])) {
            $this->language = $lang;
        }
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get a translation
     */
    public function get(string $key, array $params = []): string
    {
        $text = $this->translations[$this->language][$key] ?? $key;

        // Replace parameters
        foreach ($params as $search => $replace) {
            $text = str_replace('{' . $search . '}', $replace, $text);
        }

        return $text;
    }

    /**
     * Get available languages
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * Detect language from Telegram user data
     */
    public function detectLanguage(?array $user): string
    {
        if (!$user) {
            return 'en';
        }

        $langCode = $user['language_code'] ?? 'en';

        // Check if we support this language
        if (isset($this->translations[$langCode])) {
            return $langCode;
        }

        // Try base language (e.g., 'es' from 'es-MX')
        $baseLang = substr($langCode, 0, 2);
        if (isset($this->translations[$baseLang])) {
            return $baseLang;
        }

        return 'en'; // Default to English
    }
}

// User language storage (in production, use database)
$userLanguages = [];

// User awaiting language selection
$awaitingLanguage = [];

$bot = new TelegramBot();
$translator = new Translator();

// Register commands
$bot->commands()
    ->register('start', function ($bot, $chatId, $args) use (&$userLanguages, $translator) {
        // Try to detect language from user info if available
        if (!isset($userLanguages[$chatId])) {
            $userLanguages[$chatId] = 'en'; // Default
        }

        $translator->setLanguage($userLanguages[$chatId]);

        $text = $translator->get('welcome');
        $text .= $translator->get('commands');
        $text .= "/start - {$translator->get('cmd_start')}\n";
        $text .= "/lang - {$translator->get('cmd_lang')}\n";
        $text .= "/help - {$translator->get('cmd_help')}";

        // Create localized menu
        $keyboard = ReplyKeyboardBuilder::create(
                new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
            )
            ->addRow(Button::text($translator->get('menu_features')), Button::text($translator->get('menu_settings')))
            ->addRow(Button::text($translator->get('menu_about')))
            ->build();

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard
        ]);
    }, $translator->get('cmd_start'))

    ->register('lang', function ($bot, $chatId, $args) use ($translator, &$awaitingLanguage) {
        $text = $translator->get('select_language');

        $keyboard = InlineKeyboardBuilder::create();

        $languages = $translator->getLanguages();
        $keys = array_keys($languages);

        for ($i = 0; $i < count($languages); $i += 2) {
            $row = [];
            $row[] = Button::callback($languages[$keys[$i]], "set_lang:{$keys[$i]}");

            if (isset($keys[$i + 1])) {
                $row[] = Button::callback($languages[$keys[$i + 1]], "set_lang:{$keys[$i + 1]}");
            }

            $keyboard->addRow(...$row);
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard->build()
        ]);
    }, $translator->get('cmd_lang'))

    ->register('help', function ($bot, $chatId, $args) use ($translator, &$userLanguages) {
        if (isset($userLanguages[$chatId])) {
            $translator->setLanguage($userLanguages[$chatId]);
        }

        $text = $translator->get('help_text');

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }, $translator->get('cmd_help'));

echo "Multi-language bot started. Polling for updates...\n";

$offset = 0;

while (true) {
    try {
        $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 30]);

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;

            // Handle callback queries (language selection)
            if (isset($update['callback_query'])) {
                $query = $update['callback_query'];
                $chatId = $query['message']['chat']['id'];
                $data = $query['data'];

                if (str_starts_with($data, 'set_lang:')) {
                    $lang = substr($data, 9);
                    $translator->setLanguage($lang);
                    $userLanguages[$chatId] = $lang;

                    $bot->messages()->send([
                        'chat_id' => $chatId,
                        'text' => $translator->get('language_changed')
                    ]);

                    $bot->chats()->answerCallbackQuery(['callback_query_id' => $query['id']]);
                }
            }

            // Handle messages
            elseif (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $from = $update['message']['from'] ?? null;

                // Detect language on first interaction
                if (!isset($userLanguages[$chatId]) && $from) {
                    $detected = $translator->detectLanguage($from);
                    $userLanguages[$chatId] = $detected;
                    $translator->setLanguage($detected);

                    $bot->messages()->send([
                        'chat_id' => $chatId,
                        'text' => $translator->get('language_set') . $translator->getLanguages()[$detected]
                    ]);
                }
                elseif (isset($userLanguages[$chatId])) {
                    $translator->setLanguage($userLanguages[$chatId]);
                }

                // Handle commands
                if (isset($update['message']['text']) && str_starts_with($update['message']['text'], '/')) {
                    $bot->commands()->handleUpdate($update);
                }
                // Handle regular messages
                else {
                    $bot->messages()->send([
                        'chat_id' => $chatId,
                        'text' => $translator->get('current_language')
                    ]);
                }
            }
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        sleep(5);
    }
}
