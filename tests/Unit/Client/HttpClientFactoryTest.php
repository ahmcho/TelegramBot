<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Client\CurlHttpClient;
use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Client\StreamHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;

/**
 * HTTP Client Factory Tests
 *
 * Tests correct client selection based on config and fallback behavior.
 */
final class HttpClientFactoryTest extends TestCase
{
    private BotConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new BotConfig('test_token');
    }

    public function test_create_returns_curl_client_when_available(): void
    {
        if (!CurlHttpClient::isAvailable()) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $client = HttpClientFactory::create($this->config);

        $this->assertInstanceOf(CurlHttpClient::class, $client);
        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function test_create_returns_stream_client_when_curl_unavailable(): void
    {
        // This test only runs when cURL is actually unavailable
        // If cURL is available, we skip as we cannot mock extension availability

        if (CurlHttpClient::isAvailable()) {
            $this->markTestSkipped('cURL extension is available - this test only applies when cURL is unavailable');
        }

        if (!StreamHttpClient::isAvailable()) {
            $this->markTestSkipped('Neither cURL nor Stream is available');
        }

        $client = HttpClientFactory::create($this->config);

        $this->assertInstanceOf(StreamHttpClient::class, $client);
    }

    public function test_create_throws_exception_when_no_client_available(): void
    {
        // This test only documents the expected behavior
        // It can only run when neither cURL nor OpenSSL are available

        if (CurlHttpClient::isAvailable() || StreamHttpClient::isAvailable()) {
            $this->markTestSkipped('At least one HTTP transport is available - cannot test failure scenario');
        }

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('No HTTP transport available');

        HttpClientFactory::create($this->config);
    }

    public function test_createCurl_returns_curl_client(): void
    {
        if (!CurlHttpClient::isAvailable()) {
            $this->expectException(HttpClientException::class);
            $this->expectExceptionMessage('cURL extension is not available');
            HttpClientFactory::createCurl($this->config);
            return;
        }

        $client = HttpClientFactory::createCurl($this->config);

        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    public function test_createCurl_throws_when_curl_unavailable(): void
    {
        if (CurlHttpClient::isAvailable()) {
            $this->markTestSkipped('cURL extension is available - cannot test unavailable scenario');
        }

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('cURL extension is not available');

        HttpClientFactory::createCurl($this->config);
    }

    public function test_createStream_returns_stream_client(): void
    {
        if (!StreamHttpClient::isAvailable()) {
            $this->expectException(HttpClientException::class);
            $this->expectExceptionMessage('OpenSSL extension is not available');
            HttpClientFactory::createStream($this->config);
            return;
        }

        $client = HttpClientFactory::createStream($this->config);

        $this->assertInstanceOf(StreamHttpClient::class, $client);
    }

    public function test_createStream_throws_when_stream_unavailable(): void
    {
        if (StreamHttpClient::isAvailable()) {
            $this->markTestSkipped('Stream client is available - cannot test unavailable scenario');
        }

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('OpenSSL extension is not available');

        HttpClientFactory::createStream($this->config);
    }

    public function test_factory_respects_config(): void
    {
        if (!CurlHttpClient::isAvailable()) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $config = new BotConfig(
            token: 'test_token',
            timeout: 60,
            verifySsl: true
        );

        $client = HttpClientFactory::create($config);

        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    public function test_create_methods_return_http_client_interface(): void
    {
        if (CurlHttpClient::isAvailable()) {
            $client = HttpClientFactory::createCurl($this->config);
            $this->assertInstanceOf(HttpClientInterface::class, $client);
        }

        if (StreamHttpClient::isAvailable()) {
            $client = HttpClientFactory::createStream($this->config);
            $this->assertInstanceOf(HttpClientInterface::class, $client);
        }

        if (!CurlHttpClient::isAvailable() && !StreamHttpClient::isAvailable()) {
            $this->markTestSkipped('No HTTP clients available');
        }
    }

    public function test_factory_uses_correct_precedence(): void
    {
        // cURL should be preferred over Stream
        if (CurlHttpClient::isAvailable() && StreamHttpClient::isAvailable()) {
            $client = HttpClientFactory::create($this->config);
            $this->assertInstanceOf(CurlHttpClient::class, $client);
        } else {
            $this->markTestSkipped('Both clients need to be available');
        }
    }
}
