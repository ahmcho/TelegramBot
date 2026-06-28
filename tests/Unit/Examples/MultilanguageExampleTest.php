<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Minimal translator matching the logic in examples/multilanguage.php
 */
final class TestTranslator
{
    private string $language = 'en';

    private array $translations = [
        'en' => [
            'welcome' => "Welcome to the Multi-Language Bot! 🌍\n\n",
            'select_language' => "Please select your language:",
            'language_changed' => "Language changed to English! 🇬🇧",
            'language_set' => "Your language is now set to: ",
            'current_language' => "Current language: English 🇬🇧",
            'menu_features' => '✨ Features',
            'menu_settings' => '⚙️ Settings',
            'menu_about' => 'ℹ️ About',
            'cmd_start' => 'Start the bot',
            'cmd_lang' => 'Change language',
            'cmd_help' => 'Show help',
        ],
        'es' => [
            'welcome' => "¡Bienvenido al Bot Multiidioma! 🌍\n\n",
            'select_language' => "Por favor, selecciona tu idioma:",
            'language_changed' => "¡Idioma cambiado a Español! 🇪🇸",
            'language_set' => "Tu idioma ahora está configurado en: ",
            'current_language' => "Idioma actual: Español 🇪🇸",
            'menu_features' => '✨ Características',
            'menu_settings' => '⚙️ Configuración',
            'menu_about' => 'ℹ️ Acerca de',
            'cmd_start' => 'Iniciar el bot',
            'cmd_lang' => 'Cambiar idioma',
            'cmd_help' => 'Mostrar ayuda',
        ],
        'fr' => [
            'welcome' => "Bienvenue sur le Bot Multilingue! 🌍\n\n",
            'select_language' => "Veuillez sélectionner votre langue:",
            'language_changed' => "Langue changée en Français! 🇫🇷",
            'language_set' => "Votre langue est maintenant configurée sur: ",
            'current_language' => "Langue actuelle: Français 🇫🇷",
            'menu_features' => '✨ Fonctionnalités',
            'menu_settings' => '⚙️ Paramètres',
            'menu_about' => 'ℹ️ À propos',
            'cmd_start' => 'Démarrer le bot',
            'cmd_lang' => 'Changer de langue',
            'cmd_help' => "Afficher l'aide",
        ],
        'de' => [
            'welcome' => "Willkommen beim mehrsprachigen Bot! 🌍\n\n",
            'select_language' => "Bitte wählen Sie Ihre Sprache:",
            'language_changed' => "Sprache geändert zu Deutsch! 🇩🇪",
            'language_set' => "Ihre Sprache ist jetzt eingestellt auf: ",
            'current_language' => "Aktuelle Sprache: Deutsch 🇩🇪",
            'menu_features' => '✨ Funktionen',
            'menu_settings' => '⚙️ Einstellungen',
            'menu_about' => 'ℹ️ Über',
            'cmd_start' => 'Bot starten',
            'cmd_lang' => 'Sprache ändern',
            'cmd_help' => 'Hilfe anzeigen',
        ],
        'ar' => [
            'welcome' => "مرحباً بك في البوت متعدد اللغات! 🌍\n\n",
            'select_language' => "الرجاء تحديد لغتك:",
            'language_changed' => "تم تغيير اللغة إلى العربية! 🇸🇦",
            'language_set' => "لغتك الآن مضبوطة على: ",
            'current_language' => "اللغة الحالية: العربية 🇸🇦",
            'menu_features' => '✨ الميزات',
            'menu_settings' => '⚙️ الإعدادات',
            'menu_about' => 'ℹ️ حول',
            'cmd_start' => 'بدء البوت',
            'cmd_lang' => 'تغيير اللغة',
            'cmd_help' => 'عرض المساعدة',
        ],
        'zh' => [
            'welcome' => "欢迎来到多语言机器人! 🌍\n\n",
            'select_language' => "请选择您的语言:",
            'language_changed' => "语言已更改为中文! 🇨🇳",
            'language_set' => "您的语言现在设置为: ",
            'current_language' => "当前语言: 中文 🇨🇳",
            'menu_features' => '✨ 功能',
            'menu_settings' => '⚙️ 设置',
            'menu_about' => 'ℹ️ 关于',
            'cmd_start' => '启动机器人',
            'cmd_lang' => '更改语言',
            'cmd_help' => '显示帮助',
        ],
    ];

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

    public function get(string $key, array $params = []): string
    {
        $text = $this->translations[$this->language][$key] ?? $key;
        foreach ($params as $search => $replace) {
            $text = str_replace('{' . $search . '}', $replace, $text);
        }
        return $text;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function detectLanguage(?array $user): string
    {
        if (!$user) {
            return 'en';
        }
        $langCode = $user['language_code'] ?? 'en';
        if (isset($this->translations[$langCode])) {
            return $langCode;
        }
        $baseLang = substr($langCode, 0, 2);
        if (isset($this->translations[$baseLang])) {
            return $baseLang;
        }
        return 'en';
    }
}

/**
 * Tests for examples/multilanguage.php
 *
 * The multilanguage example defines a Translator class and implements:
 * - Language detection from Telegram user data
 * - Translation lookup by key
 * - Language switching via callback queries
 * - Localized reply keyboard menus
 * - Fallback to base language (es-MX → es)
 * - Default fallback to English
 */
final class MultilanguageExampleTest extends TestCase
{
    private TestTranslator $translator;
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = new TestTranslator();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_default_language_is_english(): void
    {
        $this->assertSame('en', $this->translator->getLanguage());
    }

    public function test_get_returns_english_translation_by_default(): void
    {
        $welcome = $this->translator->get('welcome');

        $this->assertStringContainsString('Welcome', $welcome);
    }

    public function test_set_language_changes_translation(): void
    {
        $this->translator->setLanguage('es');

        $welcome = $this->translator->get('welcome');

        $this->assertStringContainsString('Bienvenido', $welcome);
        $this->assertSame('es', $this->translator->getLanguage());
    }

    public function test_translations_exist_for_all_6_languages(): void
    {
        $languages = ['en', 'es', 'fr', 'de', 'ar', 'zh'];
        $requiredKeys = ['welcome', 'select_language', 'language_changed', 'menu_features'];

        foreach ($languages as $lang) {
            $this->translator->setLanguage($lang);
            foreach ($requiredKeys as $key) {
                $value = $this->translator->get($key);
                $this->assertNotSame($key, $value, "Missing translation for '{$key}' in language '{$lang}'");
            }
        }
    }

    public function test_set_language_ignores_unsupported_language(): void
    {
        $this->translator->setLanguage('en');
        $this->translator->setLanguage('xx');

        $this->assertSame('en', $this->translator->getLanguage());
    }

    public function test_get_returns_key_when_translation_missing(): void
    {
        $result = $this->translator->get('nonexistent_key');

        $this->assertSame('nonexistent_key', $result);
    }

    public function test_detect_language_from_user_with_known_code(): void
    {
        $user = ['id' => 123, 'language_code' => 'es'];

        $lang = $this->translator->detectLanguage($user);

        $this->assertSame('es', $lang);
    }

    public function test_detect_language_falls_back_to_base_language(): void
    {
        $user = ['id' => 456, 'language_code' => 'es-MX'];

        $lang = $this->translator->detectLanguage($user);

        $this->assertSame('es', $lang);
    }

    public function test_detect_language_falls_back_to_english_for_unknown(): void
    {
        $user = ['id' => 789, 'language_code' => 'xx'];

        $lang = $this->translator->detectLanguage($user);

        $this->assertSame('en', $lang);
    }

    public function test_detect_language_returns_english_for_null_user(): void
    {
        $lang = $this->translator->detectLanguage(null);

        $this->assertSame('en', $lang);
    }

    public function test_detect_language_returns_english_when_no_language_code(): void
    {
        $user = ['id' => 100, 'first_name' => 'User'];

        $lang = $this->translator->detectLanguage($user);

        $this->assertSame('en', $lang);
    }

    public function test_get_languages_returns_all_6_supported_languages(): void
    {
        $languages = $this->translator->getLanguages();

        $this->assertArrayHasKey('en', $languages);
        $this->assertArrayHasKey('es', $languages);
        $this->assertArrayHasKey('fr', $languages);
        $this->assertArrayHasKey('de', $languages);
        $this->assertArrayHasKey('ar', $languages);
        $this->assertArrayHasKey('zh', $languages);
        $this->assertCount(6, $languages);
    }

    public function test_localized_reply_keyboard_uses_translated_menu_keys(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->translator->setLanguage('es');

        $builder = ReplyKeyboardBuilder::create(
            new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
        )
            ->addRow(
                Button::text($this->translator->get('menu_features')),
                Button::text($this->translator->get('menu_settings'))
            )
            ->addRow(Button::text($this->translator->get('menu_about')));

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $this->translator->get('welcome'),
            'reply_markup' => $builder->build(),
        ]);

        $markup = $builder->toArray();

        // ReplyKeyboardBuilder stores button text as plain strings
        $this->assertSame('✨ Características', $markup['keyboard'][0][0]);
        $this->assertSame('⚙️ Configuración', $markup['keyboard'][0][1]);
        $this->assertSame('ℹ️ Acerca de', $markup['keyboard'][1][0]);
    }

    public function test_language_selection_inline_keyboard_pairs_languages(): void
    {
        $languages = $this->translator->getLanguages();
        $keyboard = InlineKeyboardBuilder::create();
        $keys = array_keys($languages);

        for ($i = 0; $i < count($languages); $i += 2) {
            $row = [];
            $row[] = Button::callback($languages[$keys[$i]], "set_lang:{$keys[$i]}");
            if (isset($keys[$i + 1])) {
                $row[] = Button::callback($languages[$keys[$i + 1]], "set_lang:{$keys[$i + 1]}");
            }
            $keyboard->addRow(...$row);
        }

        $built = $keyboard->toArray();

        $this->assertCount(3, $built['inline_keyboard']);
        $this->assertSame('set_lang:en', $built['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('set_lang:es', $built['inline_keyboard'][0][1]['callback_data']);
        $this->assertSame('set_lang:fr', $built['inline_keyboard'][1][0]['callback_data']);
    }

    public function test_language_switch_callback_updates_translator(): void
    {
        $callbackData = 'set_lang:fr';
        $lang = substr($callbackData, 9);

        $this->translator->setLanguage($lang);

        $this->assertSame('fr', $this->translator->getLanguage());
        $this->assertStringContainsString('Bienvenue', $this->translator->get('welcome'));
    }

    public function test_send_language_changed_confirmation(): void
    {
        $this->mockClient->setResponse(['message_id' => 2]);
        $this->mockClient->setBoolResponse(true);

        $this->translator->setLanguage('de');

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $this->translator->get('language_changed'),
        ]);

        $this->bot->chats()->answerCallbackQuery(['callback_query_id' => 'cq_de']);

        $request = $this->mockClient->getRequests()[0];
        $this->assertStringContainsString('Deutsch', $request['params']['text']);
    }

    public function test_all_6_language_welcome_messages_are_distinct(): void
    {
        $welcomes = [];
        foreach (['en', 'es', 'fr', 'de', 'ar', 'zh'] as $lang) {
            $this->translator->setLanguage($lang);
            $welcomes[$lang] = $this->translator->get('welcome');
        }

        $this->assertCount(6, array_unique($welcomes), 'All welcome messages should be unique');
    }

    public function test_detect_and_notify_on_first_interaction(): void
    {
        $this->mockClient->setResponse(['message_id' => 3]);

        $userFrom = ['id' => 200, 'first_name' => 'Maria', 'language_code' => 'es'];

        $detected = $this->translator->detectLanguage($userFrom);
        $this->translator->setLanguage($detected);

        $languages = $this->translator->getLanguages();

        $this->bot->messages()->send([
            'chat_id' => 200,
            'text' => $this->translator->get('language_set') . ($languages[$detected] ?? ''),
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Español', $request['params']['text']);
    }
}
