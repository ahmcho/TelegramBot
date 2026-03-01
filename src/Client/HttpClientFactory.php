<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client;

use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Logging\LoggerInterface;

/**
 * HTTP Client Factory
 *
 * Creates appropriate HTTP client based on available extensions
 */
class HttpClientFactory
{
    public static function create(BotConfig $config, ?LoggerInterface $logger = null): HttpClientInterface
    {
        return match (true) {
            CurlHttpClient::isAvailable() => new CurlHttpClient($config, $logger),
            StreamHttpClient::isAvailable() => new StreamHttpClient($config, $logger),
            default => throw new HttpClientException(
                'No HTTP transport available. Please enable either the cURL extension or the OpenSSL extension.'
            )
        };
    }

    public static function createCurl(BotConfig $config, ?LoggerInterface $logger = null): CurlHttpClient
    {
        if (!CurlHttpClient::isAvailable()) {
            throw new HttpClientException('cURL extension is not available');
        }

        return new CurlHttpClient($config, $logger);
    }

    public static function createStream(BotConfig $config, ?LoggerInterface $logger = null): StreamHttpClient
    {
        if (!StreamHttpClient::isAvailable()) {
            throw new HttpClientException('OpenSSL extension is not available');
        }

        return new StreamHttpClient($config, $logger);
    }
}
