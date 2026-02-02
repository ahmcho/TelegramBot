<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Enums;

/**
 * HTTP Method Enumeration
 *
 * Defines supported HTTP methods for API requests
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
}
