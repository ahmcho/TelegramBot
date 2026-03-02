<?php

declare(strict_types=1);

/**
 * Menu Bot Example - Modern API
 *
 * This example demonstrates a complex inline keyboard menu
 * with multi-level navigation and dynamic menu generation.
 *
 * Modern features showcased:
 * - Service-oriented API ($bot->messages(), $bot->formatter())
 * - Auto-escaping for MarkdownV2 with special characters
 * - Formatter for styled text (bold, italic, etc.)
 * - PHP 8.1+ features (strict types, proper typing)
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

require_once __DIR__ . '/../autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

// Menu system
class MenuSystem
{
    private TelegramBot $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Show main menu
     */
    public function showMainMenu(int $chatId, ?int $messageId = null): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🛍️ Products', 'menu:products'),
                Button::callback('📦 Services', 'menu:services')
            )
            ->addRow(
                Button::callback('ℹ️ About Us', 'menu:about'),
                Button::callback('📞 Contact', 'menu:contact')
            )
            ->addRow(
                Button::callback('⚙️ Settings', 'menu:settings'),
                Button::callback('❓ Help', 'menu:help')
            );

        // Using formatter for styled text - auto-escaped!
        $text = $this->bot->formatter()
            ->bold('Welcome to the Menu Bot!')
            . "\n\n"
            . 'Please select an option from the menu below:';

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show products menu
     */
    public function showProductsMenu(int $chatId, ?int $messageId = null): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('💻 Electronics', 'menu:products:electronics'),
                Button::callback('👕 Clothing', 'menu:products:clothing')
            )
            ->addRow(
                Button::callback('📚 Books', 'menu:products:books'),
                Button::callback('🏠 Home', 'menu:products:home')
            )
            ->addRow(
                Button::callback('🔙 Back to Main', 'menu:main')
            );

        // Auto-escaped formatting - no need to manually escape!
        $text = $this->bot->formatter()
            ->bold('Products')
            . "\n\n"
            . 'Browse our product categories:';

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show services menu
     */
    public function showServicesMenu(int $chatId, ?int $messageId = null): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🎨 Design', 'menu:services:design'),
                Button::callback('💻 Development', 'menu:services:development')
            )
            ->addRow(
                Button::callback('📈 Marketing', 'menu:services:marketing'),
                Button::callback('🔧 Support', 'menu:services:support')
            )
            ->addRow(
                Button::callback('🔙 Back to Main', 'menu:main')
            );

        $text = $this->bot->formatter()
            ->bold('Services')
            . "\n\n"
            . 'Explore our services:';

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show product detail
     */
    public function showProductDetail(int $chatId, string $category, ?int $messageId = null): void
    {
        $products = [
            'electronics' => [
                'title' => '💻 Electronics',
                'items' => ['Smartphones', 'Laptops', 'Tablets', 'Accessories']
            ],
            'clothing' => [
                'title' => '👕 Clothing',
                'items' => ['Men', 'Women', 'Kids', 'Accessories']
            ],
            'books' => [
                'title' => '📚 Books',
                'items' => ['Fiction', 'Non-Fiction', 'Technical', 'Children']
            ],
            'home' => [
                'title' => '🏠 Home & Garden',
                'items' => ['Furniture', 'Decor', 'Kitchen', 'Garden']
            ]
        ];

        $product = $products[$category] ?? null;
        if (!$product) {
            return;
        }

        $text = "{$product['title']}\n\n";
        foreach ($product['items'] as $item) {
            $text .= "• $item\n";
        }

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🔙 Back to Products', 'menu:products'),
                Button::callback('🏠 Main Menu', 'menu:main')
            );

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show service detail
     */
    public function showServiceDetail(int $chatId, string $service, ?int $messageId = null): void
    {
        $services = [
            'design' => [
                'title' => '🎨 Design Services',
                'description' => 'UI/UX design, graphic design, branding'
            ],
            'development' => [
                'title' => '💻 Development',
                'description' => 'Web development, mobile apps, custom software'
            ],
            'marketing' => [
                'title' => '📈 Marketing',
                'description' => 'SEO, social media, advertising campaigns'
            ],
            'support' => [
                'title' => '🔧 Support',
                'description' => 'Technical support, maintenance, consulting'
            ]
        ];

        $serviceInfo = $services[$service] ?? null;
        if (!$serviceInfo) {
            return;
        }

        $text = "{$serviceInfo['title']}\n\n"
            . "ℹ️ {$serviceInfo['description']}\n\n"
            . "✅ Professional quality\n"
            . "✅ Fast delivery\n"
            . "✅ Competitive prices";

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('📞 Contact Us', 'menu:contact'),
                Button::callback('🔙 Back to Services', 'menu:services')
            )
            ->addRow(
                Button::callback('🏠 Main Menu', 'menu:main')
            );

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show about page
     */
    public function showAbout(int $chatId, ?int $messageId = null): void
    {
        $text = "ℹ️ *About Us*\n\n"
            . "We are a leading company providing excellent products and services since 2020.\n\n"
            . "🎯 Our Mission:\n"
            . "To provide quality solutions for our customers.\n\n"
            . "👥 Our Team:\n"
            . "Experienced professionals dedicated to your success.";

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::url('🌐 Visit Website', 'https://example.com'),
                Button::callback('🏠 Main Menu', 'menu:main')
            );

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show contact page
     */
    public function showContact(int $chatId, ?int $messageId = null): void
    {
        $text = "📞 *Contact Information*\n\n"
            . "📧 Email: contact@example.com\n"
            . "📱 Phone: +1 234 567 890\n"
            . "🌐 Website: https://example.com\n"
            . "📍 Address: 123 Main St, City, Country\n\n"
            . "🕐 Business Hours:\n"
            . "Monday - Friday: 9:00 - 18:00";

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::url('🌐 Visit Website', 'https://example.com'),
                Button::url('📧 Email Us', 'mailto:contact@example.com')
            )
            ->addRow(
                Button::callback('🏠 Main Menu', 'menu:main')
            );

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show settings menu
     */
    public function showSettings(int $chatId, ?int $messageId = null): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🔔 Notifications', 'settings:notif'),
                Button::callback('🌐 Language', 'settings:lang')
            )
            ->addRow(
                Button::callback('🎨 Theme', 'settings:theme'),
                Button::callback('🔒 Privacy', 'settings:privacy')
            )
            ->addRow(
                Button::callback('🔙 Back to Main', 'menu:main')
            );

        // Using formatter - auto-escaped for MarkdownV2!
        $text = $this->bot->formatter()
            ->bold('⚙️ Settings')
            . "\n\n"
            . 'Customize your experience:';

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Show help page
     */
    public function showHelp(int $chatId, ?int $messageId = null): void
    {
        $text = "❓ *Help & FAQ*\n\n"
            . "*How to use the menu:*\n"
            . "1. Tap any button to navigate\n"
            . "2. Use 🔙 to go back\n"
            . "3. Use 🏠 to return to main menu\n\n"
            . "*Common Questions:*\n"
            . "• Q: How do I place an order?\n"
            . "  A: Browse products and contact us!\n\n"
            . "• Q: What are your prices?\n"
            . "  A: Contact us for a quote.\n\n"
            . "• Q: Do you offer support?\n"
            . "  A: Yes! Check our Services menu.";

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('📞 Contact Support', 'menu:contact'),
                Button::callback('🏠 Main Menu', 'menu:main')
            );

        $this->sendMessageOrEdit($chatId, $text, $keyboard->build(), $messageId);
    }

    /**
     * Handle menu navigation
     */
    public function handleMenuNavigation(string $data, int $chatId, int $messageId): void
    {
        $parts = explode(':', $data);
        $menu = $parts[1] ?? 'main';

        switch ($menu) {
            case 'main':
                $this->showMainMenu($chatId, $messageId);
                break;

            case 'products':
                if (isset($parts[2])) {
                    $this->showProductDetail($chatId, $parts[2], $messageId);
                } else {
                    $this->showProductsMenu($chatId, $messageId);
                }
                break;

            case 'services':
                if (isset($parts[2])) {
                    $this->showServiceDetail($chatId, $parts[2], $messageId);
                } else {
                    $this->showServicesMenu($chatId, $messageId);
                }
                break;

            case 'about':
                $this->showAbout($chatId, $messageId);
                break;

            case 'contact':
                $this->showContact($chatId, $messageId);
                break;

            case 'settings':
                $this->showSettings($chatId, $messageId);
                break;

            case 'help':
                $this->showHelp($chatId, $messageId);
                break;

            default:
                $this->showMainMenu($chatId, $messageId);
        }
    }

    /**
     * Send message or edit existing message
     */
    private function sendMessageOrEdit(int $chatId, string $text, string $keyboard, ?int $messageId = null): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',  // Auto-escaping enabled!
            'reply_markup' => $keyboard
        ];

        if ($messageId !== null) {
            $params['message_id'] = $messageId;
            $this->bot->messages()->editText($params);
        } else {
            $this->bot->messages()->send($params);
        }
    }
}

// Main bot loop
try {
    $bot = new TelegramBot();
    $menu = new MenuSystem($bot);

    echo "Menu Bot started...\n";
    echo "Press Ctrl+C to stop\n\n";

    $offset = 0;

    while (true) {
        try {
            $updates = $bot->getUpdates([
                'offset' => $offset,
                'timeout' => 30
            ]);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                // Handle callback queries
                if (isset($update['callback_query'])) {
                    $callbackQuery = $update['callback_query'];
                    $chatId = $callbackQuery['message']['chat']['id'];
                    $messageId = $callbackQuery['message']['message_id'];
                    $data = $callbackQuery['data'];
                    $queryId = $callbackQuery['id'];

                    // Answer callback query
                    $bot->api()->call(
                        ApiMethod::ANSWER_CALLBACK_QUERY,
                        ['callback_query_id' => $queryId]
                    );

                    // Handle URL buttons
                    if (strpos($data, 'url:') === 0) {
                        continue; // URL is handled automatically by Telegram
                    }

                    // Handle menu navigation
                    if (strpos($data, 'menu:') === 0) {
                        $menu->handleMenuNavigation($data, $chatId, $messageId);
                    }

                    continue;
                }

                // Handle messages
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';

                    // Handle /start command
                    if ($text === '/start' || $text === '/menu') {
                        $menu->showMainMenu($chatId);
                    } elseif ($text === '/help') {
                        $menu->showHelp($chatId);
                    } else {
                        $bot->messages()->send([
                            'chat_id' => $chatId,
                            'text' => "Welcome! Use the buttons below to navigate or type /menu to show the main menu.",
                            'reply_markup' => InlineKeyboardBuilder::create()
                                ->addRow(
                                    Button::callback('🏠 Show Menu', 'menu:main')
                                )
                                ->build()
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }
} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
