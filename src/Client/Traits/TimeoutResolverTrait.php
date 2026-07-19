<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Client\Traits;

/**
 * Timeout Resolver Trait
 *
 * Shared by CurlHttpClient and StreamHttpClient. Classes using this trait
 * must provide a BotConfig via $this->config.
 */
trait TimeoutResolverTrait
{
    /**
     * Resolve the transport timeout for a request.
     *
     * Telegram's long-poll `timeout` param (used by getUpdates) tells the
     * server how long it may hold the connection open waiting for updates.
     * If the HTTP client timeout is not comfortably larger than that, the
     * transport aborts the connection right around when the long-poll
     * response is due, surfacing as a spurious timeout error.
     *
     * @param array<string, mixed> $params
     */
    private function resolveTimeout(array $params): int
    {
        $configTimeout = $this->config->getTimeout();

        if (!isset($params['timeout']) || !is_numeric($params['timeout'])) {
            return $configTimeout;
        }

        return max($configTimeout, (int) $params['timeout'] + 10);
    }
}
