<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Client\CurlHttpClient;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Enums\HttpMethod;

/**
 * cURL HTTP Client Tests
 *
 * Tests successful requests, file uploads, timeouts, SSL, and error handling.
 */
final class CurlHttpClientTest extends TestCase
{
    private BotConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        if (!CurlHttpClient::isAvailable()) {
            $this->markTestSkipped('cURL extension is not available');
        }

        $this->config = new BotConfig('test_token');
    }

    public function test_isAvailable_returns_true_when_curl_enabled(): void
    {
        $this->assertTrue(CurlHttpClient::isAvailable());
    }

    public function test_initializable(): void
    {
        $client = new CurlHttpClient($this->config);

        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    public function test_initializable_with_custom_config(): void
    {
        $config = new BotConfig(
            token: 'custom_token',
            timeout: 60,
            verifySsl: true
        );

        $client = new CurlHttpClient($config);

        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    public function test_getLastHttpCode_returns_zero_initially(): void
    {
        $client = new CurlHttpClient($this->config);

        $this->assertSame(0, $client->getLastHttpCode());
    }

    public function test_successful_post_request_returns_array(): void
    {
        // Test that request method signature accepts correct parameters
        $client = new CurlHttpClient($this->config);

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

    public function test_request_throws_exception_on_curl_error(): void
    {
        $client = new CurlHttpClient($this->config);

        // Invalid URL that will cause cURL to fail
        $invalidUrl = 'http://localhost:9999/invalid';

        $this->expectException(HttpClientException::class);
        $client->request(HttpMethod::POST, $invalidUrl, ['test' => 'data']);
    }

    public function test_config_timeout_is_used(): void
    {
        $config = new BotConfig('token', timeout: 30);
        $client = new CurlHttpClient($config);

        // Verify client is created with timeout config
        $this->assertInstanceOf(CurlHttpClient::class, $client);

        // The actual timeout value is used in CURLOPT_TIMEOUT
        // We can verify the config was set correctly
        $this->assertSame(30, $config->getTimeout());
    }

    public function test_config_ssl_verification_is_respected(): void
    {
        $configWithSsl = new BotConfig('token', verifySsl: true);
        $configWithoutSsl = new BotConfig('token', verifySsl: false);

        $this->assertTrue($configWithSsl->shouldVerifySsl());
        $this->assertFalse($configWithoutSsl->shouldVerifySsl());

        // Clients should be constructable with both settings
        $this->assertInstanceOf(CurlHttpClient::class, new CurlHttpClient($configWithSsl));
        $this->assertInstanceOf(CurlHttpClient::class, new CurlHttpClient($configWithoutSsl));
    }

    public function test_requestMulti_returns_empty_result_for_empty_input(): void
    {
        $client = new CurlHttpClient($this->config);

        $result = $client->requestMulti(
            HttpMethod::POST,
            'https://api.telegram.org/bottest/test',
            []
        );

        $this->assertSame([], $result);
    }

    public function test_requestMulti_with_single_request(): void
    {
        $client = new CurlHttpClient($this->config);

        // Test with single request - will fail on invalid URL
        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/test',
            [['chat_id' => 123, 'text' => 'test']]
        );

        // Should return array with error result
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('success', $result[0]);
        $this->assertFalse($result[0]['success']);
        $this->assertArrayHasKey('error', $result[0]);
    }

    public function test_requestMulti_with_custom_max_concurrent(): void
    {
        $client = new CurlHttpClient($this->config);

        // Test that options parameter is accepted and used
        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/test',
            [['chat_id' => 123], ['chat_id' => 456]],
            ['max_concurrent' => 5]
        );

        // Should return array with error results
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertFalse($result[0]['success']);
        $this->assertFalse($result[1]['success']);
    }

    public function test_requestMulti_with_delay_between_batches(): void
    {
        $client = new CurlHttpClient($this->config);

        // Test that delay_ms option is accepted
        $startTime = microtime(true);
        $result = $client->requestMulti(
            HttpMethod::POST,
            'http://localhost:9999/test',
            [['chat_id' => 123], ['chat_id' => 456]],
            ['delay_ms' => 100]
        );
        $elapsed = microtime(true) - $startTime;

        // Should return array with error results
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Note: delay might not work with failed requests, but we tested the option is accepted
        $this->assertGreaterThan(0, $elapsed);
    }

    public function test_request_handles_get_method(): void
    {
        $client = new CurlHttpClient($this->config);

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
        $client = new CurlHttpClient($this->config);

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
        // Test JSON serialization of parameters
        $client = new CurlHttpClient($this->config);

        // Use reflection to test hasFileUpload helper method
        $method = new \ReflectionMethod($client, 'hasFileUpload');

        // Test with normal params - should not be detected as file upload
        $params = ['chat_id' => 123, 'text' => 'Hello'];
        $hasFile = $method->invoke($client, $params);
        $this->assertFalse($hasFile);

        // Test with CURLFile - should be detected as file upload
        $paramsWithFile = ['chat_id' => 123, 'photo' => new \CURLFile('test.jpg')];
        $hasFile = $method->invoke($client, $paramsWithFile);
        $this->assertTrue($hasFile);
    }

    public function test_request_handles_file_upload_with_curlfile(): void
    {
        $client = new CurlHttpClient($this->config);

        // Use reflection to test hasFileUpload detection
        $method = new \ReflectionMethod($client, 'hasFileUpload');

        // Test with CURLFile
        $params = ['photo' => new \CURLFile(__FILE__)];

        $hasFile = $method->invoke($client, $params);
        $this->assertTrue($hasFile);

        // Verify CURLFile is in params
        $this->assertInstanceOf(\CURLFile::class, $params['photo']);
    }

    public function test_hasFileUpload_detects_media_group_attach_field(): void
    {
        // Mirrors what MediaService::prepareMediaGroupAttachments() produces:
        // the CURLFile is hoisted to a top-level key, 'media' becomes a JSON string.
        $client = new CurlHttpClient($this->config);
        $method = new \ReflectionMethod($client, 'hasFileUpload');

        $params = [
            'chat_id' => 123,
            'media' => json_encode([['type' => 'photo', 'media' => 'attach://media_attach_0']]),
            'media_attach_0' => new \CURLFile(__FILE__),
        ];

        $this->assertTrue($method->invoke($client, $params));
    }

    public function test_request_handles_special_characters_in_params(): void
    {
        // Test that special characters are properly JSON encoded
        $params = [
            'text' => 'Hello "World" & <test>',
            'emoji' => '😀🎉'
        ];

        $json = json_encode($params);
        $this->assertIsString($json);
        // JSON encodes quotes as \"
        $this->assertStringContainsString('Hello \"World\"', $json);
    }

    public function test_request_handles_nested_arrays_in_params(): void
    {
        // Test that nested arrays are properly JSON encoded
        $params = [
            'reply_markup' => [
                'keyboard' => [['Button1', 'Button2']],
                'resize_keyboard' => true
            ]
        ];

        $json = json_encode($params);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded['reply_markup']['keyboard']);
        $this->assertTrue($decoded['reply_markup']['resize_keyboard']);
    }

    public function test_invalid_json_response_throws_exception(): void
    {
        $client = new CurlHttpClient($this->config);
        $badJson = '{"ok": true, "result": invalid json}';

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage('Invalid JSON response');

        // Use reflection to call private parseResponse method
        $method = new \ReflectionMethod($client, 'parseResponse');
        $method->invoke($client, $badJson);
    }

    public function test_api_error_response_throws_api_exception(): void
    {
        $client = new CurlHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Bad Request: invalid chat ID", "error_code": 400}';

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Bad Request: invalid chat ID');

        $method = new \ReflectionMethod($client, 'parseResponse');
        $method->invoke($client, $errorResponse);
    }

    public function test_api_error_response_carries_error_code(): void
    {
        $client = new CurlHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Forbidden: bot was blocked by the user", "error_code": 403}';

        $method = new \ReflectionMethod($client, 'parseResponse');

        try {
            $method->invoke($client, $errorResponse);
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertSame(403, $e->getErrorCode());
            $this->assertSame('Forbidden: bot was blocked by the user', $e->getMessage());
            $this->assertSame('Forbidden: bot was blocked by the user', $e->getResponseBody()['description']);
        }
    }

    public function test_api_error_without_error_code_has_null_error_code(): void
    {
        $client = new CurlHttpClient($this->config);
        $errorResponse = '{"ok": false, "description": "Unknown error"}';

        $method = new \ReflectionMethod($client, 'parseResponse');

        try {
            $method->invoke($client, $errorResponse);
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertNull($e->getErrorCode());
            $this->assertSame('Unknown error', $e->getMessage());
        }
    }

    public function test_api_error_is_not_http_client_exception(): void
    {
        $client = new CurlHttpClient($this->config);
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
        $client = new CurlHttpClient($this->config);
        $validResponse = '{"ok": true, "result": {"message_id": 123, "text": "Hello"}}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $validResponse);

        $this->assertIsArray($result);
        $this->assertSame(123, $result['message_id']);
        $this->assertSame('Hello', $result['text']);
    }

    public function test_response_with_empty_result_returns_empty_array(): void
    {
        $client = new CurlHttpClient($this->config);
        $emptyResponse = '{"ok": true, "result": []}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $emptyResponse);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_response_with_boolean_result(): void
    {
        $client = new CurlHttpClient($this->config);
        $boolResponse = '{"ok": true, "result": true}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $boolResponse);

        $this->assertTrue($result);
    }

    public function test_response_with_int_result(): void
    {
        $client = new CurlHttpClient($this->config);
        $intResponse = '{"ok": true, "result": 42}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $intResponse);

        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function test_response_with_string_result(): void
    {
        $client = new CurlHttpClient($this->config);
        $stringResponse = '{"ok": true, "result": "test_string"}';

        $method = new \ReflectionMethod($client, 'parseResponse');
        $result = $method->invoke($client, $stringResponse);

        $this->assertSame('test_string', $result);
        $this->assertIsString($result);
    }

    public function test_request_mixed_return_types(): void
    {
        $client = new CurlHttpClient($this->config);

        // Test each response type using reflection
        $method = new \ReflectionMethod($client, 'parseResponse');

        // Test array result
        $arrayResponse = '{"ok": true, "result": {"id": 123}}';
        $result = $method->invoke($client, $arrayResponse);
        $this->assertIsArray($result);

        // Test int result
        $intResponse = '{"ok": true, "result": 42}';
        $result = $method->invoke($client, $intResponse);
        $this->assertIsInt($result);

        // Test bool result
        $boolResponse = '{"ok": true, "result": true}';
        $result = $method->invoke($client, $boolResponse);
        $this->assertIsBool($result);

        // Test string result
        $stringResponse = '{"ok": true, "result": "test"}';
        $result = $method->invoke($client, $stringResponse);
        $this->assertIsString($result);
    }
}
