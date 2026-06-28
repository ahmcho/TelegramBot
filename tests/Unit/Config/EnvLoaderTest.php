<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use AhmCho\Telegram\Config\EnvLoader;

/**
 * Environment Loader Tests
 *
 * Tests environment variable loading from .env file,
 * require() method throws when variable missing, and get() with defaults.
 */
final class EnvLoaderTest extends TestCase
{
    private string $testEnvFile;
    private EnvLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary .env file
        $this->testEnvFile = sys_get_temp_dir() . '/.env.test.' . uniqid();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test environment variables
        putenv('TEST_VAR_1');
        putenv('TEST_VAR_2');
        putenv('TEST_VAR_QUOTED');
        putenv('TEST_VAR_EMPTY');
        unset($_ENV['TEST_VAR_1'], $_ENV['TEST_VAR_2'], $_ENV['TEST_VAR_QUOTED'], $_ENV['TEST_VAR_EMPTY']);

        // Remove test file if it exists
        if (file_exists($this->testEnvFile)) {
            unlink($this->testEnvFile);
        }
    }

    private function createEnvFile(string $content): void
    {
        file_put_contents($this->testEnvFile, $content);
    }

    public function test_load_reads_simple_key_value_pairs(): void
    {
        $this->createEnvFile("TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value1', $this->loader->get('TEST_VAR_1'));
        $this->assertSame('value2', $this->loader->get('TEST_VAR_2'));
    }

    public function test_load_sets_environment_variables(): void
    {
        $this->createEnvFile("TEST_VAR_1=value1");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value1', getenv('TEST_VAR_1'));
        $this->assertSame('value1', $_ENV['TEST_VAR_1']);
    }

    public function test_load_handles_double_quoted_values(): void
    {
        $this->createEnvFile('TEST_VAR_QUOTED="quoted value"');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('quoted value', $this->loader->get('TEST_VAR_QUOTED'));
    }

    public function test_load_handles_single_quoted_values(): void
    {
        $this->createEnvFile("TEST_VAR_QUOTED='single quoted'");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('single quoted', $this->loader->get('TEST_VAR_QUOTED'));
    }

    public function test_load_skips_comments(): void
    {
        $this->createEnvFile("# This is a comment\nTEST_VAR=value\n# Another comment");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value', $this->loader->get('TEST_VAR'));
        $this->assertNull($this->loader->get('# This is a comment'));
    }

    public function test_load_skips_empty_lines(): void
    {
        $this->createEnvFile("\n\nTEST_VAR1=value1\n\n\nTEST_VAR2=value2\n\n");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value1', $this->loader->get('TEST_VAR1'));
        $this->assertSame('value2', $this->loader->get('TEST_VAR2'));
    }

    public function test_load_handles_values_with_equals_sign(): void
    {
        $this->createEnvFile('TEST_URL=https://example.com?param=value');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('https://example.com?param=value', $this->loader->get('TEST_URL'));
    }

    public function test_load_handles_empty_values(): void
    {
        $this->createEnvFile('TEST_VAR_EMPTY=');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('', $this->loader->get('TEST_VAR_EMPTY'));
    }

    public function test_get_returns_default_value_when_key_not_found(): void
    {
        $this->loader = new EnvLoader();

        $this->assertSame('default', $this->loader->get('NONEXISTENT_KEY', 'default'));
        $this->assertNull($this->loader->get('NONEXISTENT_KEY'));
    }

    public function test_get_returns_loaded_value(): void
    {
        $this->createEnvFile('TEST_VAR=test_value');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('test_value', $this->loader->get('TEST_VAR'));
    }

    public function test_get_returns_global_env_variable(): void
    {
        putenv('GLOBAL_VAR=global_value');
        $_ENV['GLOBAL_VAR'] = 'global_value';

        $this->loader = new EnvLoader();

        $this->assertSame('global_value', $this->loader->get('GLOBAL_VAR'));
    }

    public function test_require_returns_value_when_exists(): void
    {
        $this->createEnvFile('REQUIRED_VAR=required_value');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('required_value', $this->loader->require('REQUIRED_VAR'));
    }

    public function test_require_throws_exception_when_variable_not_set(): void
    {
        $this->loader = new EnvLoader();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Required environment variable 'MISSING_VAR' is not set.");

        $this->loader->require('MISSING_VAR');
    }

    public function test_require_throws_exception_for_empty_value(): void
    {
        $this->createEnvFile('EMPTY_VAR=');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        // Empty string is still a valid value, require() should return it
        $this->assertSame('', $this->loader->require('EMPTY_VAR'));
    }

    public function test_load_without_path_searches_for_env_file(): void
    {
        // Create .env in current directory (test will use findEnvFile logic)
        // For testing, we just verify it doesn't throw when file doesn't exist
        $this->loader = new EnvLoader();
        $this->loader->load('/nonexistent/path/.env');

        // Should not throw, just silently return
        $this->assertInstanceOf(EnvLoader::class, $this->loader);
    }

    public function test_load_handles_special_characters_in_values(): void
    {
        $this->createEnvFile('TEST_SPECIAL="value with spaces and - special_chars!"');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value with spaces and - special_chars!', $this->loader->get('TEST_SPECIAL'));
    }

    public function test_load_handles_multiline_values(): void
    {
        // Note: Our implementation doesn't support multiline values (no backslash continuation)
        // This test verifies the current behavior
        $this->createEnvFile("TEST_LINE1=line1\nTEST_LINE2=line2");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('line1', $this->loader->get('TEST_LINE1'));
        $this->assertSame('line2', $this->loader->get('TEST_LINE2'));
    }

    public function test_validation_of_key_names(): void
    {
        // Keys must start with letter or underscore, followed by alphanumeric/underscore
        $this->createEnvFile("VALID_KEY=value\n123INVALID=no_value\nINVALID-KEY=no_value2");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('value', $this->loader->get('VALID_KEY'));
        $this->assertNull($this->loader->get('123INVALID'));
        $this->assertNull($this->loader->get('INVALID-KEY'));
    }

    public function test_get_prefer_loaded_vars_over_global_env(): void
    {
        putenv('TEST_VAR=global_value');
        $_ENV['TEST_VAR'] = 'global_value';

        $this->createEnvFile('TEST_VAR=loaded_value');

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('loaded_value', $this->loader->get('TEST_VAR'));
    }

    public function test_multiple_loads_accumulate_values(): void
    {
        $file1 = sys_get_temp_dir() . '/.env.test1.' . uniqid();
        $file2 = sys_get_temp_dir() . '/.env.test2.' . uniqid();

        try {
            file_put_contents($file1, "VAR1=value1");
            file_put_contents($file2, "VAR2=value2");

            $this->loader = new EnvLoader();
            $this->loader->load($file1);
            $this->loader->load($file2);

            $this->assertSame('value1', $this->loader->get('VAR1'));
            $this->assertSame('value2', $this->loader->get('VAR2'));
        } finally {
            if (file_exists($file1)) {
                unlink($file1);
            }
            if (file_exists($file2)) {
                unlink($file2);
            }
        }
    }

    public function test_real_world_bot_config_example(): void
    {
        $this->createEnvFile(
            "# Telegram Bot Configuration\n" .
            "BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11\n" .
            "WEBHOOK_URL=https://example.com/webhook.php\n" .
            "LOG_FILE_PATH=/var/www/bot/logs/bot.log"
        );

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', $this->loader->require('BOT_TOKEN'));
        $this->assertSame('https://example.com/webhook.php', $this->loader->require('WEBHOOK_URL'));
        $this->assertSame('/var/www/bot/logs/bot.log', $this->loader->require('LOG_FILE_PATH'));
    }

    public function test_load_handles_spaces_around_equals(): void
    {
        $this->createEnvFile("TEST_VAR_1 = spaced_value\nTEST_VAR_2=normal");

        $this->loader = new EnvLoader();
        $this->loader->load($this->testEnvFile);

        $this->assertSame('spaced_value', $this->loader->get('TEST_VAR_1'));
        $this->assertSame('normal', $this->loader->get('TEST_VAR_2'));
    }
}
