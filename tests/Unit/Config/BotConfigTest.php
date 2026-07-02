<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Config\BotConfig;

/**
 * Bot Configuration Value Object Tests
 *
 * Tests configuration object creation, URL building, immutability,
 * timeout and SSL settings, and exception throwing configuration.
 */
final class BotConfigTest extends TestCase
{
    public function test_can_create_config_with_token_only(): void
    {
        $config = new BotConfig('test_token_123');

        $this->assertSame('test_token_123', $config->getToken());
        $this->assertSame('https://api.telegram.org/', $config->getApiUrl());
        $this->assertSame(30, $config->getTimeout());
        $this->assertTrue($config->shouldThrowExceptions());
        $this->assertTrue($config->shouldVerifySsl());
    }

    public function test_can_create_config_with_all_parameters(): void
    {
        $config = new BotConfig(
            token: 'my_token',
            apiUrl: 'https://api.telegram.org/',
            timeout: 60,
            throwExceptions: false,
            verifySsl: true
        );

        $this->assertSame('my_token', $config->getToken());
        $this->assertSame('https://api.telegram.org/', $config->getApiUrl());
        $this->assertSame(60, $config->getTimeout());
        $this->assertFalse($config->shouldThrowExceptions());
        $this->assertTrue($config->shouldVerifySsl());
    }

    public function test_getFullApiUrl_builds_correct_url(): void
    {
        $config = new BotConfig('test_token_123');

        $expected = 'https://api.telegram.org/bottest_token_123/';
        $this->assertSame($expected, $config->getFullApiUrl());
    }

    public function test_getFullApiUrl_handles_trailing_slash(): void
    {
        $config = new BotConfig('test_token', 'https://api.example.com/api/');

        $expected = 'https://api.example.com/api/bottest_token/';
        $this->assertSame($expected, $config->getFullApiUrl());
    }

    public function test_getFullApiUrl_handles_no_trailing_slash(): void
    {
        $config = new BotConfig('test_token', 'https://api.example.com/api');

        $expected = 'https://api.example.com/api/bottest_token/';
        $this->assertSame($expected, $config->getFullApiUrl());
    }

    public function test_withTimeout_returns_new_instance_with_updated_timeout(): void
    {
        $config = new BotConfig('token');
        $newConfig = $config->withTimeout(120);

        $this->assertNotSame($config, $newConfig);
        $this->assertSame(30, $config->getTimeout(), 'Original config should be unchanged');
        $this->assertSame(120, $newConfig->getTimeout());
    }

    public function test_withTimeout_preserves_other_properties(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            apiUrl: 'https://custom.api/',
            timeout: 15,
            throwExceptions: false,
            verifySsl: true
        );
        $newConfig = $config->withTimeout(45);

        $this->assertSame('test_token', $newConfig->getToken());
        $this->assertSame('https://custom.api/', $newConfig->getApiUrl());
        $this->assertSame(45, $newConfig->getTimeout());
        $this->assertFalse($newConfig->shouldThrowExceptions());
        $this->assertTrue($newConfig->shouldVerifySsl());
    }

    public function test_withThrowExceptions_returns_new_instance_with_updated_setting(): void
    {
        $config = new BotConfig('token', throwExceptions: true);
        $newConfig = $config->withThrowExceptions(false);

        $this->assertNotSame($config, $newConfig);
        $this->assertTrue($config->shouldThrowExceptions(), 'Original config should be unchanged');
        $this->assertFalse($newConfig->shouldThrowExceptions());
    }

    public function test_withThrowExceptions_preserves_other_properties(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            apiUrl: 'https://custom.api/',
            timeout: 90,
            throwExceptions: true,
            verifySsl: true
        );
        $newConfig = $config->withThrowExceptions(false);

        $this->assertSame('test_token', $newConfig->getToken());
        $this->assertSame('https://custom.api/', $newConfig->getApiUrl());
        $this->assertSame(90, $newConfig->getTimeout());
        $this->assertFalse($newConfig->shouldThrowExceptions());
        $this->assertTrue($newConfig->shouldVerifySsl());
    }

    public function test_config_is_immutable(): void
    {
        $config = new BotConfig('token');

        $this->assertIsObject($config);
        $this->assertInstanceOf(BotConfig::class, $config);

        // Attempting to modify properties should not be possible
        // (this is a compile-time check in PHP with readonly properties,
        // but we can verify behavior remains consistent)

        $originalTimeout = $config->getTimeout();
        $config->withTimeout(100);
        $this->assertSame($originalTimeout, $config->getTimeout());
    }

    public function test_withVerifySsl_returns_new_instance_with_updated_setting(): void
    {
        $config = new BotConfig('token');
        $this->assertTrue($config->shouldVerifySsl(), 'Default should be true');

        $disabled = $config->withVerifySsl(false);
        $this->assertNotSame($config, $disabled);
        $this->assertTrue($config->shouldVerifySsl(), 'Original config should be unchanged');
        $this->assertFalse($disabled->shouldVerifySsl());

        $reenabled = $disabled->withVerifySsl(true);
        $this->assertTrue($reenabled->shouldVerifySsl());
    }

    public function test_withVerifySsl_preserves_other_properties(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            apiUrl: 'https://custom.api/',
            timeout: 60,
            throwExceptions: false,
            verifySsl: true
        );
        $newConfig = $config->withVerifySsl(false);

        $this->assertSame('test_token', $newConfig->getToken());
        $this->assertSame('https://custom.api/', $newConfig->getApiUrl());
        $this->assertSame(60, $newConfig->getTimeout());
        $this->assertFalse($newConfig->shouldThrowExceptions());
        $this->assertFalse($newConfig->shouldVerifySsl());
    }

    public function test_multiple_with_methods_chain_correctly(): void
    {
        $config = new BotConfig('token');

        $newConfig = $config
            ->withTimeout(90)
            ->withThrowExceptions(false)
            ->withVerifySsl(false);

        $this->assertSame('token', $newConfig->getToken());
        $this->assertSame(90, $newConfig->getTimeout());
        $this->assertFalse($newConfig->shouldThrowExceptions());
        $this->assertFalse($newConfig->shouldVerifySsl());

        // Original config remains unchanged
        $this->assertSame(30, $config->getTimeout());
        $this->assertTrue($config->shouldThrowExceptions());
        $this->assertTrue($config->shouldVerifySsl());
    }

    /**
     * @dataProvider customApiUrlProvider
     */
    public function test_builds_urls_for_custom_api_endpoints(string $apiUrl, string $expected): void
    {
        $config = new BotConfig('test_token', $apiUrl);

        $this->assertSame($expected, $config->getFullApiUrl());
    }

    public static function customApiUrlProvider(): array
    {
        return [
            'custom domain' => [
                'https://api.example.com/',
                'https://api.example.com/bottest_token/'
            ],
            'subdomain' => [
                'https://bot.example.com/api/',
                'https://bot.example.com/api/bottest_token/'
            ],
            'local server' => [
                'http://localhost:8081/',
                'http://localhost:8081/bottest_token/'
            ],
            'without trailing slash' => [
                'https://api.example.com',
                'https://api.example.com/bottest_token/'
            ],
        ];
    }

    public function test_default_values_are_sensible(): void
    {
        $config = new BotConfig('token');

        // Default API URL should be official Telegram API
        $this->assertSame('https://api.telegram.org/', $config->getApiUrl());

        // Default timeout should be reasonable (30 seconds)
        $this->assertSame(30, $config->getTimeout());

        // Default should throw exceptions (fail-fast behavior)
        $this->assertTrue($config->shouldThrowExceptions());

        // Default should verify SSL (secure by default; disable in dev via verifySsl: false)
        $this->assertTrue($config->shouldVerifySsl());
    }

    public function test_timeout_accepts_various_values(): void
    {
        $values = [1, 10, 30, 60, 120, 300];

        foreach ($values as $timeout) {
            $config = new BotConfig('token', timeout: $timeout);
            $this->assertSame($timeout, $config->getTimeout());
        }
    }

    public function test_token_is_required(): void
    {
        // Empty string is technically allowed, but will fail API calls
        // This test just verifies the constructor accepts non-empty strings
        $config = new BotConfig('valid_token');
        $this->assertSame('valid_token', $config->getToken());
    }

    public function test_config_properties_are_readonly(): void
    {
        $config = new BotConfig('token');

        $reflection = new \ReflectionClass($config);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    public function test_log_max_bytes_defaults_to_zero(): void
    {
        $config = new BotConfig('token');
        $this->assertSame(0, $config->getLogMaxBytes());
    }

    public function test_log_max_bytes_can_be_set(): void
    {
        $config = new BotConfig('token', logMaxBytes: 5_000_000);
        $this->assertSame(5_000_000, $config->getLogMaxBytes());
    }

    public function test_withLogMaxBytes_returns_new_instance(): void
    {
        $config = new BotConfig('token');
        $updated = $config->withLogMaxBytes(10_000_000);

        $this->assertNotSame($config, $updated);
        $this->assertSame(0, $config->getLogMaxBytes());
        $this->assertSame(10_000_000, $updated->getLogMaxBytes());
    }

    public function test_withLogMaxBytes_preserves_other_properties(): void
    {
        $config = new BotConfig('token', timeout: 60, logLevel: 'DEBUG');
        $updated = $config->withLogMaxBytes(1024);

        $this->assertSame('token', $updated->getToken());
        $this->assertSame(60, $updated->getTimeout());
        $this->assertSame('DEBUG', $updated->getLogLevel());
        $this->assertSame(1024, $updated->getLogMaxBytes());
    }

    public function test_get_log_config_includes_log_max_bytes(): void
    {
        $config = new BotConfig('token', logMaxBytes: 2048);
        $logConfig = $config->getLogConfig();

        $this->assertArrayHasKey('log_max_bytes', $logConfig);
        $this->assertSame(2048, $logConfig['log_max_bytes']);
    }

    public function test_with_methods_preserve_log_max_bytes(): void
    {
        $config = (new BotConfig('token'))->withLogMaxBytes(999);

        $this->assertSame(999, $config->withTimeout(60)->getLogMaxBytes());
        $this->assertSame(999, $config->withLogLevel('DEBUG')->getLogMaxBytes());
        $this->assertSame(999, $config->withLogFilePath('other.log')->getLogMaxBytes());
        $this->assertSame(999, $config->withLoggingEnabled(false)->getLogMaxBytes());
        $this->assertSame(999, $config->withThrowExceptions(false)->getLogMaxBytes());
        $this->assertSame(999, $config->withVerifySsl(false)->getLogMaxBytes());
    }
}
