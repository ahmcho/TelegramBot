<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

/**
 * E-Commerce Example Bot
 *
 * Demonstrates how to build a simple ordering system with:
 * - Product catalog
 * - Cart management
 * - Order processing
 */

// Sample products
$products = [
    'p1' => ['id' => 'p1', 'name' => 'Premium Widget', 'price' => 29.99, 'description' => 'High-quality widget'],
    'p2' => ['id' => 'p2', 'name' => 'Standard Gadget', 'price' => 19.99, 'description' => 'Standard quality gadget'],
    'p3' => ['id' => 'p3', 'name' => 'Basic Tool', 'price' => 9.99, 'description' => 'Essential tool'],
];

// Simple cart storage (in production, use a database)
$carts = [];

$bot = new TelegramBot();

// Register commands
$bot->commands()
    ->register('start', function ($bot, $chatId, $args) use ($products) {
        $welcome = "🛒 Welcome to the Store Bot!\n\n";
        $welcome .= "Browse our products and add them to your cart.\n\n";
        $welcome .= "Commands:\n";
        $welcome .= "/catalog - View products\n";
        $welcome .= "/cart - View your cart\n";
        $welcome .= "/checkout - Complete your order\n";
        $welcome .= "/clear - Clear your cart";

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $welcome
        ]);
    }, 'Start shopping')

    ->register('catalog', function ($bot, $chatId, $args) use ($products) {
        $text = "📦 Our Products:\n\n";
        foreach ($products as $product) {
            $text .= "🔹 {$product['name']} - \${$product['price']}\n";
            $text .= "   {$product['description']}\n\n";
        }

        $keyboard = InlineKeyboardBuilder::create();
        foreach ($products as $product) {
            $keyboard->addRow(
                Button::callback("🛒 Add {$product['name']}", "add_cart:{$product['id']}")
            );
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard->build()
        ]);
    }, 'View product catalog')

    ->register('cart', function ($bot, $chatId, $args) use (&$carts, $products) {
        $cart = $carts[$chatId] ?? [];

        if (empty($cart)) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '🛒 Your cart is empty.\n\nUse /catalog to browse products.'
            ]);
            return;
        }

        $text = "🛒 Your Cart:\n\n";
        $total = 0;

        foreach ($cart as $itemId => $item) {
            $product = $products[$item['product_id']];
            $subtotal = $product['price'] * $item['quantity'];
            $total += $subtotal;
            $text .= "🔹 {$product['name']} x{$item['quantity']} = \${$subtotal}\n";
        }

        $text .= "\n💰 Total: \${$total}";

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('✅ Checkout', 'checkout'),
                Button::callback('🗑️ Clear Cart', 'clear_cart')
            )
            ->build();

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard
        ]);
    }, 'View your cart')

    ->register('checkout', function ($bot, $chatId, $args) use (&$carts, $products) {
        $cart = $carts[$chatId] ?? [];

        if (empty($cart)) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => 'Your cart is empty. Add items first!'
            ]);
            return;
        }

        $total = 0;
        $items = [];

        foreach ($cart as $itemId => $item) {
            $product = $products[$item['product_id']];
            $subtotal = $product['price'] * $item['quantity'];
            $total += $subtotal;
            $items[] = "{$product['name']} x{$item['quantity']}";
        }

        // In production, save order to database here
        $orderId = 'ORD-' . strtoupper(uniqid());

        $text = "✅ Order Placed!\n\n";
        $text .= "Order ID: {$orderId}\n";
        $text .= "Items:\n" . implode("\n", $items) . "\n\n";
        $text .= "💰 Total: \${$total}\n\n";
        $text .= "Thank you for your order!";

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);

        // Clear cart after checkout
        unset($carts[$chatId]);
    }, 'Complete your order')

    ->register('clear', function ($bot, $chatId, $args) use (&$carts) {
        unset($carts[$chatId]);
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '🗑️ Cart cleared.'
        ]);
    }, 'Clear your cart');

// Handle callback queries
echo "Bot started. Polling for updates...\n";

$offset = 0;

while (true) {
    try {
        $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 30]);

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;

            // Handle callback queries
            if (isset($update['callback_query'])) {
                $query = $update['callback_query'];
                $chatId = $query['message']['chat']['id'];
                $data = $query['data'];

                // Add to cart
                if (str_starts_with($data, 'add_cart:')) {
                    $productId = substr($data, 9);

                    if (!isset($products[$productId])) {
                        continue;
                    }

                    if (!isset($carts[$chatId])) {
                        $carts[$chatId] = [];
                    }

                    $itemId = count($carts[$chatId]);
                    $carts[$chatId][$itemId] = [
                        'product_id' => $productId,
                        'quantity' => 1
                    ];

                    $product = $products[$productId];
                    $bot->messages()->send([
                        'chat_id' => $chatId,
                        'text' => "✅ Added {$product['name']} to cart!"
                    ]);

                    $bot->api()->call(
                        \AhmCho\Telegram\Enums\ApiMethod::ANSWER_CALLBACK_QUERY,
                        [
                            'callback_query_id' => $query['id']
                        ]
                    );
                }

                // Checkout
                elseif ($data === 'checkout') {
                    $bot->commands()->execute('checkout', $chatId);

                    $bot->api()->call(
                        \AhmCho\Telegram\Enums\ApiMethod::ANSWER_CALLBACK_QUERY,
                        [
                            'callback_query_id' => $query['id']
                        ]
                    );
                }

                // Clear cart
                elseif ($data === 'clear_cart') {
                    $bot->commands()->execute('clear', $chatId);

                    $bot->api()->call(
                        \AhmCho\Telegram\Enums\ApiMethod::ANSWER_CALLBACK_QUERY,
                        [
                            'callback_query_id' => $query['id']
                        ]
                    );
                }
            }

            // Handle commands
            elseif (isset($update['message'])) {
                $bot->commands()->handleUpdate($update);
            }
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        sleep(5);
    }
}
