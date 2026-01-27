<?php
/**
 * Menu Bot Example
 *
 * This example demonstrates a complex inline keyboard menu
 * with multi-level navigation and dynamic menu generation.
 */

require_once __DIR__ . '/../src/TelegramBot.php';

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
        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('🛍️ Products', 'menu:products'),
                $this->bot->createCallbackButton('📦 Services', 'menu:services')
            ],
            [
                $this->bot->createCallbackButton('ℹ️ About Us', 'menu:about'),
                $this->bot->createCallbackButton('📞 Contact', 'menu:contact')
            ],
            [
                $this->bot->createCallbackButton('⚙️ Settings', 'menu:settings'),
                $this->bot->createCallbackButton('❓ Help', 'menu:help')
            ]
        ]);

        $text = "🏠 *Welcome to the Menu Bot*\n\n"
            . "Please select an option from the menu below:";

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
    }

    /**
     * Show products menu
     */
    public function showProductsMenu(int $chatId, ?int $messageId = null): void
    {
        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('💻 Electronics', 'menu:products:electronics'),
                $this->bot->createCallbackButton('👕 Clothing', 'menu:products:clothing')
            ],
            [
                $this->bot->createCallbackButton('📚 Books', 'menu:products:books'),
                $this->bot->createCallbackButton('🏠 Home', 'menu:products:home')
            ],
            [
                $this->bot->createCallbackButton('🔙 Back to Main', 'menu:main')
            ]
        ]);

        $text = "🛍️ *Products*\n\n"
            . "Browse our product categories:";

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
    }

    /**
     * Show services menu
     */
    public function showServicesMenu(int $chatId, ?int $messageId = null): void
    {
        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('🎨 Design', 'menu:services:design'),
                $this->bot->createCallbackButton('💻 Development', 'menu:services:development')
            ],
            [
                $this->bot->createCallbackButton('📈 Marketing', 'menu:services:marketing'),
                $this->bot->createCallbackButton('🔧 Support', 'menu:services:support')
            ],
            [
                $this->bot->createCallbackButton('🔙 Back to Main', 'menu:main')
            ]
        ]);

        $text = "📦 *Services*\n\n"
            . "Explore our services:";

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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

        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('🔙 Back to Products', 'menu:products'),
                $this->bot->createCallbackButton('🏠 Main Menu', 'menu:main')
            ]
        ]);

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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

        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('📞 Contact Us', 'menu:contact'),
                $this->bot->createCallbackButton('🔙 Back to Services', 'menu:services')
            ],
            [
                $this->bot->createCallbackButton('🏠 Main Menu', 'menu:main')
            ]
        ]);

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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

        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('🌐 Visit Website', 'url:https://example.com'),
                $this->bot->createCallbackButton('🏠 Main Menu', 'menu:main')
            ]
        ]);

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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

        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createUrlButton('🌐 Visit Website', 'https://example.com'),
                $this->bot->createUrlButton('📧 Email Us', 'mailto:contact@example.com')
            ],
            [
                $this->bot->createCallbackButton('🏠 Main Menu', 'menu:main')
            ]
        ]);

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
    }

    /**
     * Show settings menu
     */
    public function showSettings(int $chatId, ?int $messageId = null): void
    {
        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('🔔 Notifications', 'settings:notif'),
                $this->bot->createCallbackButton('🌐 Language', 'settings:lang')
            ],
            [
                $this->bot->createCallbackButton('🎨 Theme', 'settings:theme'),
                $this->bot->createCallbackButton('🔒 Privacy', 'settings:privacy')
            ],
            [
                $this->bot->createCallbackButton('🔙 Back to Main', 'menu:main')
            ]
        ]);

        $text = "⚙️ *Settings*\n\n"
            . "Customize your experience:";

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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

        $keyboard = $this->bot->buildInlineKeyboard([
            [
                $this->bot->createCallbackButton('📞 Contact Support', 'menu:contact'),
                $this->bot->createCallbackButton('🏠 Main Menu', 'menu:main')
            ]
        ]);

        $this->sendMessageOrEdit($chatId, $text, $keyboard, $messageId);
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
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard
        ];

        if ($messageId !== null) {
            $params['message_id'] = $messageId;
            $this->bot->editMessageText($params);
        } else {
            $this->bot->sendMessage($params);
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
                    $bot->answerCallbackQuery([
                        'callback_query_id' => $queryId
                    ]);

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
                        $bot->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "Welcome! Use the buttons below to navigate or type /menu to show the main menu.",
                            'reply_markup' => $bot->buildInlineKeyboard([
                                [
                                    $bot->createCallbackButton('🏠 Show Menu', 'menu:main')
                                ]
                            ])
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
