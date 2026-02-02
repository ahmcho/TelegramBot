<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * HTTP Client Factory
 *
 * Creates appropriate HTTP client based on available extensions
 */
class HttpClientFactory
{
    public static function create(BotConfig $config): HttpClientInterface
    {
        return match (true) {
            CurlHttpClient::isAvailable() => new CurlHttpClient($config),
            StreamHttpClient::isAvailable() => new StreamHttpClient($config),
            default => throw new HttpClientException(
                'No HTTP transport available. Please enable either the cURL extension or the OpenSSL extension.'
            )
        };
    }

    public static function createCurl(BotConfig $config): CurlHttpClient
    {
        if (!CurlHttpClient::isAvailable()) {
            throw new HttpClientException('cURL extension is not available');
        }

        return new CurlHttpClient($config);
    }

    public static function createStream(BotConfig $config): StreamHttpClient
    {
        if (!StreamHttpClient::isAvailable()) {
            throw new HttpClientException('OpenSSL extension is not available');
        }

        return new StreamHttpClient($config);
    }
}
