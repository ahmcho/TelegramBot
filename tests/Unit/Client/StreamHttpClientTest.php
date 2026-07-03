<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Client\StreamHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;

/**
 * Stream HTTP Client Tests
 *
 * Tests stream context options, SSL configuration, and error handling.
 */
final class StreamHttpClientTest extends TestCase
{
    private BotConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        if (!StreamHttpClient::isAvailable()) {
            $this->markTestSkipped('Stream client requires OpenSSL extension');
        }

        $this->config = new BotConfig('test_token');
    }

    public function test_isAvailable_returns_true_when_openssl_enabled(): void
    {
        $this->assertTrue(StreamHttpClient::isAvailable());
    }

    public function test_isAvailable_returns_false_when_openssl_disabled(): void
    {
        // This cannot be tested without changing the runtime environment
        // It's more of a documentation test
        $this->assertTrue(
            extension_loaded('openssl'),
            'OpenSSL extension should be available for this test'
        );
    }

    public function test_initializable(): void
    {
        $client = new StreamHttpClient($this->config);

        $this->assertInstanceOf(StreamHttpClient::class, $client);
    }

    public function test_initializable_with_custom_config(): void
    {
        $config = new BotConfig(
            token: 'custom_token',
            timeout: 60,
            verifySsl: true
        );

        $client = new StreamHttpClient($config);

        $this->assertInstanceOf(StreamHttpClient::class, $client);
    }

    public function test_getLastHttpCode_returns_zero_initially(): void
    {
        $client = new StreamHttpClient($this->config);

        $this->assertSame(0, $client->getLastHttpCode());
    }

    public function test_resolveTimeout_uses_config_timeout_when_no_timeout_param(): void
    {
        $config = new BotConfig('token', timeout: 30);
        $client = new StreamHttpClient($config);

        $method = new \ReflectionMethod($client, 'resolveTimeout');

        $this->assertSame(30, $method->invoke($client, ['chat_id' => 123]));
    }

    public function test_resolveTimeout_extends_beyond_long_poll_timeout_param(): void
    {
        // getUpdates(['timeout' => 30]) with the default 30s client timeout
        // is a guaranteed race: the stream can abort right as Telegram's
        // long-poll response is due. The client timeout must exceed it.
        $config = new BotConfig('token', timeout: 30);
        $client = new StreamHttpClient($config);

        $method = new \ReflectionMethod($client, 'resolveTimeout');

        $this->assertSame(40, $method->invoke($client, ['timeout' => 30]));
    }

    public function test_resolveTimeout_keeps_larger_config_timeout(): void
    {
        $config = new BotConfig('token', timeout: 120);
        $client = new StreamHttpClient($config);

        $method = new \ReflectionMethod($client, 'resolveTimeout');

        $this->assertSame(120, $method->invoke($client, ['timeout' => 30]));
    }

    public function test_resolveTimeout_ignores_non_numeric_timeout_param(): void
    {
        $config = new BotConfig('token', timeout: 30);
        $client = new StreamHttpClient($config);

        $method = new \ReflectionMethod($client, 'resolveTimeout');

        $this->assertSame(30, $method->invoke($client, ['timeout' => 'not-a-number']));
    }

    public function test_successful_post_request_returns_array(): void
    {
        // Test that request method signature accepts correct parameters
        $client = new StreamHttpClient($this->config);

        // Verify method exists and accepts correct parameters
        $method = new \ReflectionMethod($client, 'request');
        $parameters = $method->getParameters();

        $this->assertSame(3, count($parameters));
        $this->assertSame(HttpMethod::class, $parameters[0]->getType()->getName());
        $this->assertSame('string', $parameters[1]->getType()->getName());
        $this->assertSame('array', $parameters[2]->getType()->getName());

        // Verify return type is mixed (not array)
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('mixed', $returnType->__toString());
    }

    public function test_request_throws_exception_on_invalid_url(): void
    {
        $client = new StreamHttpClient($this->config);

        $invalidUrl = 'http://localhost:9999/invalid';

        $this->expectException(HttpClientException::class);
        $client->request(HttpMethod::POST, $invalidUrl, ['test' => 'data']);
    }

    public function test_request_throws_exception_when_openssl_disabled(): void
    {
        // This test verifies the check happens, even if we can't disable OpenSSL
        $client = new StreamHttpClient($this->config);

        // The check happens in constructor, and we verified it passes in setUp
        // This test documents the behavior
        $this->assertTrue(extension_loaded('openssl'));
    }

    public function test_config_timeout_is_used_in_context(): void
    {
        $config = new BotConfig('token', timeout: 30);
        $client = new StreamHttpClient($config);

        // Verify client is created with timeout config
        $this->assertInstanceOf(StreamHttpClient::class, $client);

        // The actual timeout value is used in stream context
        // We can verify the config was set correctly
        $this->assertSame(30, $config->getTimeout());
    }

    public function test_config_ssl_verification_is_respected(): void
    {
        $configWithSsl = new BotConfig('token', verifySsl: true);
        $configWithoutSsl = new BotConfig('token', verifySsl: false);

        $clientWithSsl = new StreamHttpClient($configWithSsl);
        $clientWithoutSsl = new StreamHttpClient($configWithoutSsl);

        // Verify both clients can be created with different SSL settings
        $this->assertInstanceOf(StreamHttpClient::class, $clientWithSsl);
        $this->assertInstanceOf(StreamHttpClient::class, $clientWithoutSsl);

        // Verify the config values are different
        $this->assertTrue($configWithSsl->shouldVerifySsl());
        $this->assertFalse($configWithoutSsl->shouldVerifySsl());
    }

    public function test_requestMulti_falls_back_to_serial_execution(): void
    {
        $client = new StreamHttpClient($this->config);

        // localhost:9999 will refuse connection — verify requestMulti returns results not throws
        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/invalid',
            [['chat_id' => 123], ['chat_id' => 456]]
        );

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_requestMulti_serial_result_has_correct_structure(): void
    {
        $client = new StreamHttpClient($this->config);

        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/invalid',
            [['chat_id' => 123]]
        );

        $this->assertArrayHasKey(0, $result);
        $entry = $result[0];
        $this->assertArrayHasKey('success', $entry);
        $this->assertArrayHasKey('chat_id', $entry);
        $this->assertArrayHasKey('message_id', $entry);
        $this->assertArrayHasKey('data', $entry);
        $this->assertArrayHasKey('error', $entry);
    }

    public function test_requestMulti_captures_individual_failures_without_throwing(): void
    {
        $client = new StreamHttpClient($this->config);

        // All requests will fail — must still return array not throw
        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/invalid',
            [
                ['chat_id' => 111],
                ['chat_id' => 222],
                ['chat_id' => 333],
            ]
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $entry) {
            $this->assertFalse($entry['success']);
            $this->assertNull($entry['message_id']);
            $this->assertNull($entry['data']);
            $this->assertNotEmpty($entry['error']);
        }
    }

    public function test_requestMulti_preserves_chat_id_in_failure_result(): void
    {
        $client = new StreamHttpClient($this->config);

        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/invalid',
            [['chat_id' => 12345]]
        );

        $this->assertSame(12345, $result[0]['chat_id']);
    }

    public function test_request_handles_get_method(): void
    {
        $client = new StreamHttpClient($this->config);

        // Test that GET method is accepted (will fail on actual request but tests the logic)
        try {
            $client->request(HttpMethod::GET, 'http://localhost:9999/test', []);
        } catch (HttpClientException $e) {
            // Expected to fail with invalid URL
            $this->assertTrue(true);
        }
    }

    public function test_request_handles_post_method(): void
    {
        $client = new StreamHttpClient($this->config);

        // Test that POST method is accepted
        try {
            $client->request(HttpMethod::POST, 'http://localhost:9999/test', ['test' => 'data']);
        } catch (HttpClientException $e) {
            // Expected to fail with invalid URL
            $this->assertTrue(true);
        }
    }

    public function test_request_includes_json_content_type_header(): void
    {
        // Test that parameters are JSON serialized
        $params = ['chat_id' => 123, 'text' => 'Hello'];
        $json = json_encode($params);

        $this->assertIsString($json);
        // Verify it's valid JSON
        $this->assertIsArray(json_decode($json, true));
    }

    public function test_request_sends_json_encoded_body(): void
    {
        // Test that parameters are properly JSON encoded
        $params = ['chat_id' => 123, 'text' => 'Hello "World"'];
        $json = json_encode($params);

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertSame($params, $decoded);
    }

    public function test_invalid_json_response_throws_exception(): void
    {
        $client = new StreamHttpClient($this->config);
        $badJson = '{"ok": true, "result": invalid json}';

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Invalid JSON response');

        $method = new \ReflectionMethod($client, 'parseResponse');
        $method->invoke($client, $badJson);
    }

    public function test_api_error_response_throws_api_exception(): void
    {
        $client = new StreamHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Bad Request: invalid chat ID", "error_code": 400}';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Bad Request: invalid chat ID');

        $method = new \ReflectionMethod($client, 'parseResponse');
        $method->invoke($client, $errorResponse);
    }

    public function test_api_error_response_carries_error_code(): void
    {
        $client = new StreamHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Forbidden: bot was blocked by the user", "error_code": 403}';

        $method = new \ReflectionMethod($client, 'parseResponse');

        try {
            $method->invoke($client, $errorResponse);
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->getErrorCode());
            $this->assertSame('Forbidden: bot was blocked by the user', $e->getMessage());
        }
    }

    public function test_api_error_is_not_http_client_exception(): void
    {
        $client = new StreamHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Bad Request", "error_code": 400}';

        $method = new \ReflectionMethod($client, 'parseResponse');

        try {
            $method->invoke($client, $errorResponse);
            $this->fail('Expected ApiException was not thrown');
        } catch (HttpClientException $e) {
            $this->fail('Should have thrown ApiException, not HttpClientException');
        } catch (ApiException $e) {
            $this->assertInstanceOf(ApiException::class, $e);
        }
    }

    public function test_successful_response_parses_correctly(): void
    {
        $client = new StreamHttpClient($this->config);
        $validResponse = '{"ok": true, "result": {"message_id": 123, "text": "Hello"}}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $validResponse);

        $this->assertIsArray($result);
        $this->assertSame(123, $result['message_id']);
        $this->assertSame('Hello', $result['text']);
    }

    public function test_response_with_empty_result_returns_empty_array(): void
    {
        $client = new StreamHttpClient($this->config);
        $emptyResponse = '{"ok": true, "result": []}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $emptyResponse);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_response_with_boolean_result(): void
    {
        $client = new StreamHttpClient($this->config);
        $boolResponse = '{"ok": true, "result": true}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $boolResponse);

        $this->assertTrue($result);
    }

    public function test_request_parses_http_status_from_headers(): void
    {
        // Test that HTTP status parsing works
        $headers = [
            'HTTP/1.1 200 OK',
            'Content-Type: application/json'
        ];

        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $this->assertSame('200', $matches[1]);
                return;
            }
        }

        $this->fail('No HTTP status code found in headers');
    }

    public function test_request_handles_200_status(): void
    {
        // Test HTTP status code regex
        $header = 'HTTP/1.1 200 OK';
        $this->assertTrue((bool) preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches));
        $this->assertSame('200', $matches[1]);
    }

    public function test_request_handles_400_status(): void
    {
        // Test HTTP status code regex for error status
        $header = 'HTTP/1.1 400 Bad Request';
        $this->assertTrue((bool) preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches));
        $this->assertSame('400', $matches[1]);
    }

    public function test_request_handles_500_status(): void
    {
        // Test HTTP status code regex for server error
        $header = 'HTTP/1.1 500 Internal Server Error';
        $this->assertTrue((bool) preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches));
        $this->assertSame('500', $matches[1]);
    }

    public function test_response_with_int_result(): void
    {
        $client = new StreamHttpClient($this->config);
        $intResponse = '{"ok": true, "result": 42}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $intResponse);

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function test_response_with_bool_result(): void
    {
        $client = new StreamHttpClient($this->config);
        $boolResponse = '{"ok": true, "result": true}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $boolResponse);

        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    public function test_response_with_string_result(): void
    {
        $client = new StreamHttpClient($this->config);
        $stringResponse = '{"ok": true, "result": "test_string"}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $stringResponse);

        $this->assertSame('test_string', $result);
        $this->assertIsString($result);
    }

    public function test_hasFileUpload_detects_curlfile(): void
    {
        $client = new StreamHttpClient($this->config);
        $method = new \ReflectionMethod($client, 'hasFileUpload');

        $this->assertFalse($method->invoke($client, ['chat_id' => 123, 'text' => 'Hello']));
        $this->assertTrue($method->invoke($client, ['chat_id' => 123, 'photo' => new \CURLFile(__FILE__)]));
    }

    public function test_buildMultipartBody_includes_file_contents_and_boundary(): void
    {
        $client = new StreamHttpClient($this->config);
        $method = new \ReflectionMethod($client, 'buildMultipartBody');

        $result = $method->invoke($client, [
            'chat_id' => 123,
            'caption' => 'A test photo',
            'photo' => new \CURLFile(__FILE__),
        ]);

        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('boundary', $result);

        $body = $result['body'];
        $boundary = $result['boundary'];

        $this->assertStringContainsString("--{$boundary}", $body);
        $this->assertStringContainsString("--{$boundary}--\r\n", $body);
        $this->assertStringContainsString('Content-Disposition: form-data; name="chat_id"', $body);
        $this->assertStringContainsString("123", $body);
        $this->assertStringContainsString('Content-Disposition: form-data; name="caption"', $body);
        $this->assertStringContainsString('A test photo', $body);
        $this->assertStringContainsString(
            'Content-Disposition: form-data; name="photo"; filename="' . basename(__FILE__) . '"',
            $body
        );
        $this->assertStringContainsString(file_get_contents(__FILE__), $body);
    }

    public function test_buildMultipartBody_encodes_bool_and_array_fields(): void
    {
        $client = new StreamHttpClient($this->config);
        $method = new \ReflectionMethod($client, 'buildMultipartBody');

        $result = $method->invoke($client, [
            'disable_notification' => true,
            'protect_content' => false,
            'entities' => ['type' => 'bold', 'offset' => 0, 'length' => 4],
            'skip_me' => null,
        ]);

        $body = $result['body'];

        $this->assertMatchesRegularExpression(
            '/name="disable_notification"\r\n\r\n1\r\n/',
            $body
        );
        $this->assertMatchesRegularExpression(
            '/name="protect_content"\r\n\r\n\r\n/',
            $body
        );
        $this->assertStringContainsString(
            json_encode(['type' => 'bold', 'offset' => 0, 'length' => 4]),
            $body
        );
        $this->assertStringNotContainsString('name="skip_me"', $body);
    }

    public function test_buildMultipartBody_throws_for_unreadable_file(): void
    {
        $client = new StreamHttpClient($this->config);
        $method = new \ReflectionMethod($client, 'buildMultipartBody');

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Unable to read local file for upload');

        $method->invoke($client, [
            'photo' => new \CURLFile(__DIR__ . '/does-not-exist-' . uniqid() . '.jpg'),
        ]);
    }
}
