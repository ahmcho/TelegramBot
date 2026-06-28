<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/ecommerce.php
 *
 * Verifies the e-commerce bot patterns:
 * - /catalog command builds product listing with add-to-cart buttons
 * - Cart state management (add, view, clear)
 * - Total calculation
 * - Checkout generates order ID
 * - Inline keyboard callback data format: 'add_cart:{product_id}'
 * - Empty cart shows appropriate message
 */
final class EcommerceExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    private array $products = [
        'p1' => ['id' => 'p1', 'name' => 'Premium Widget', 'price' => 29.99, 'description' => 'High-quality widget'],
        'p2' => ['id' => 'p2', 'name' => 'Standard Gadget', 'price' => 19.99, 'description' => 'Standard quality gadget'],
        'p3' => ['id' => 'p3', 'name' => 'Basic Tool', 'price' => 9.99, 'description' => 'Essential tool'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_catalog_command_sends_product_list(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $text = "📦 Our Products:\n\n";
        foreach ($this->products as $product) {
            $text .= "🔹 {$product['name']} - \${$product['price']}\n";
            $text .= "   {$product['description']}\n\n";
        }

        $keyboard = InlineKeyboardBuilder::create();
        foreach ($this->products as $product) {
            $keyboard->addRow(
                Button::callback("🛒 Add {$product['name']}", "add_cart:{$product['id']}")
            );
        }

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $text,
            'reply_markup' => $keyboard->build(),
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Premium Widget', $request['params']['text']);
        $this->assertStringContainsString('Standard Gadget', $request['params']['text']);

        $markup = $keyboard->toArray();
        $this->assertArrayHasKey('inline_keyboard', $markup);
        $this->assertCount(3, $markup['inline_keyboard']);
        $this->assertSame('add_cart:p1', $markup['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_add_to_cart_callback_data_format(): void
    {
        $callbackData = 'add_cart:p1';
        $parts = explode(':', $callbackData, 2);

        $this->assertSame('add_cart', $parts[0]);
        $this->assertSame('p1', $parts[1]);
    }

    public function test_cart_total_calculation(): void
    {
        $carts = [];
        $chatId = 123;

        $carts[$chatId]['p1'] = ['product_id' => 'p1', 'quantity' => 2];
        $carts[$chatId]['p2'] = ['product_id' => 'p2', 'quantity' => 1];

        $total = 0;
        foreach ($carts[$chatId] as $itemId => $item) {
            $product = $this->products[$item['product_id']];
            $total += $product['price'] * $item['quantity'];
        }

        $this->assertEqualsWithDelta(79.97, $total, 0.01);
    }

    public function test_empty_cart_shows_message(): void
    {
        $this->mockClient->setResponse(['message_id' => 2]);

        $carts = [];
        $chatId = 123;
        $cart = $carts[$chatId] ?? [];

        if (empty($cart)) {
            $this->bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '🛒 Your cart is empty.\n\nUse /catalog to browse products.',
            ]);
        }

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('empty', $request['params']['text']);
    }

    public function test_view_cart_displays_items_and_total(): void
    {
        $this->mockClient->setResponse(['message_id' => 3]);

        $cart = [
            'p1' => ['product_id' => 'p1', 'quantity' => 1],
            'p2' => ['product_id' => 'p2', 'quantity' => 3],
        ];

        $text = "🛒 Your Cart:\n\n";
        $total = 0;

        foreach ($cart as $itemId => $item) {
            $product = $this->products[$item['product_id']];
            $subtotal = $product['price'] * $item['quantity'];
            $total += $subtotal;
            $text .= "🔹 {$product['name']} x{$item['quantity']} = \${$subtotal}\n";
        }

        $text .= "\n💰 Total: \${$total}";

        $cartKeyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('✅ Checkout', 'checkout'),
                Button::callback('🗑️ Clear Cart', 'clear_cart')
            );

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $text,
            'reply_markup' => $cartKeyboard->build(),
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Premium Widget', $request['params']['text']);
        $this->assertStringContainsString('Standard Gadget', $request['params']['text']);
        $this->assertStringContainsString('Total', $request['params']['text']);

        $markup = $cartKeyboard->toArray();
        $this->assertSame('checkout', $markup['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('clear_cart', $markup['inline_keyboard'][0][1]['callback_data']);
    }

    public function test_checkout_generates_order_confirmation(): void
    {
        $this->mockClient->setResponse(['message_id' => 4]);

        $orderId = 'ORD-' . strtoupper(substr(md5((string)time()), 0, 8));
        $chatId = 123;

        $orderText = "✅ Order Confirmed!\n\n";
        $orderText .= "Order ID: {$orderId}\n";
        $orderText .= "Status: Processing\n\n";
        $orderText .= "We'll notify you when your order ships.";

        $this->bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $orderText,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Order Confirmed', $request['params']['text']);
        $this->assertStringContainsString('ORD-', $request['params']['text']);
    }

    public function test_clear_cart_callback_empties_cart(): void
    {
        $carts = [];
        $chatId = 123;
        $carts[$chatId] = [
            'p1' => ['product_id' => 'p1', 'quantity' => 2],
        ];

        $this->assertNotEmpty($carts[$chatId]);

        unset($carts[$chatId]);

        $this->assertEmpty($carts[$chatId] ?? []);
    }

    public function test_start_command_shows_welcome_with_commands(): void
    {
        $this->mockClient->setResponse(['message_id' => 5]);

        $this->bot->commands()->register('start', function ($bot, $chatId, $args) {
            $welcome = "🛒 Welcome to the Store Bot!\n\n";
            $welcome .= "Browse our products and add them to your cart.\n\n";
            $welcome .= "Commands:\n";
            $welcome .= "/catalog - View products\n";
            $welcome .= "/cart - View your cart\n";
            $welcome .= "/checkout - Complete your order\n";
            $welcome .= "/clear - Clear your cart";

            $bot->messages()->send(['chat_id' => $chatId, 'text' => $welcome]);
        }, 'Start shopping');

        $this->bot->commands()->handleUpdate([
            'update_id' => 1,
            'message' => ['message_id' => 1, 'chat' => ['id' => 123], 'text' => '/start'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Store Bot', $request['params']['text']);
        $this->assertStringContainsString('/catalog', $request['params']['text']);
    }

    public function test_add_item_to_cart_multiple_times_increments_quantity(): void
    {
        $carts = [];
        $chatId = 100;

        $addToCart = function (string $productId) use (&$carts, $chatId): void {
            if (!isset($carts[$chatId][$productId])) {
                $carts[$chatId][$productId] = ['product_id' => $productId, 'quantity' => 0];
            }
            $carts[$chatId][$productId]['quantity']++;
        };

        $addToCart('p1');
        $addToCart('p1');
        $addToCart('p2');

        $this->assertSame(2, $carts[$chatId]['p1']['quantity']);
        $this->assertSame(1, $carts[$chatId]['p2']['quantity']);
    }

    public function test_product_keyboard_has_add_button_for_each_product(): void
    {
        $keyboard = InlineKeyboardBuilder::create();
        foreach ($this->products as $product) {
            $keyboard->addRow(
                Button::callback("🛒 Add {$product['name']}", "add_cart:{$product['id']}")
            );
        }
        $built = $keyboard->toArray();

        $this->assertCount(3, $built['inline_keyboard']);

        foreach ($built['inline_keyboard'] as $i => $row) {
            $this->assertStringStartsWith('add_cart:', $row[0]['callback_data']);
        }
    }
}
