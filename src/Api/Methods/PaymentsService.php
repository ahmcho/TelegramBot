<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Enums\ApiMethod;

/**
 * Payments Service
 *
 * Handles Telegram Bot API payment methods.
 */
class PaymentsService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Send an invoice
     *
     * `provider_token` may be omitted (or an empty string) for payments in
     * Telegram Stars, using currency `XTR`.
     *
     * @param array{chat_id: int|string, message_thread_id?: int, title: string, description: string, payload: string, provider_token?: string, currency: string, prices: array<int, array{label: string, amount: int}>, max_tip_amount?: int, suggested_tip_amounts?: array<int>, start_parameter?: string, provider_data?: string, photo_url?: string, photo_size?: int, photo_width?: int, photo_height?: int, need_name?: bool, need_phone_number?: bool, need_email?: bool, need_shipping_address?: bool, send_phone_number_to_provider?: bool, send_email_to_provider?: bool, is_flexible?: bool, disable_notification?: bool, protect_content?: bool, message_effect_id?: string, reply_parameters?: array<string, mixed>, reply_markup?: string} $params
     * @return array<string, mixed>
     */
    public function sendInvoice(array $params): array
    {
        return $this->apiService->call(ApiMethod::SEND_INVOICE, $params);
    }
}
