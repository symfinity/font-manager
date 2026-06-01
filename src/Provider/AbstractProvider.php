<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\ProviderFeature;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Abstract base class for font providers.
 */
abstract class AbstractProvider implements FontProviderInterface
{
    /** @var array<string, array{data: mixed, expires: int}>|null */
    private static ?array $cache = null;
    private static int $cacheTtl = 3600; // 1 hour

    /**
     * @var array<string, bool>
     */
    protected const FEATURES = [
        'search' => false,
        'metadata' => false,
        'variable_fonts' => false,
        'cdn' => true,
    ];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly array $config = []
    ) {
        if (isset($config['cache_ttl']) && is_int($config['cache_ttl'])) {
            self::$cacheTtl = $config['cache_ttl'];
        }
    }

    /**
     * Clear the API cache.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public function requiresAuth(): bool
    {
        return false;
    }

    public function isReady(): bool
    {
        if (!$this->requiresAuth()) {
            return true;
        }

        return $this->isAuthenticated();
    }

    public function supports(ProviderFeature $feature): bool
    {
        return static::FEATURES[$feature->value] ?? false;
    }

    /**
     * Check if provider is authenticated (override if auth required).
     */
    protected function isAuthenticated(): bool
    {
        return true;
    }

    /**
     * Get data from cache.
     *
     * @return mixed|null
     */
    protected function getFromCache(string $key): mixed
    {
        if (null === self::$cache) {
            self::$cache = [];
        }

        if (!isset(self::$cache[$key])) {
            return null;
        }

        $entry = self::$cache[$key];
        if ($entry['expires'] < time()) {
            unset(self::$cache[$key]);

            return null;
        }

        return $entry['data'];
    }

    /**
     * Put data in cache.
     */
    protected function putInCache(string $key, mixed $data): void
    {
        if (null === self::$cache) {
            self::$cache = [];
        }

        self::$cache[$key] = [
            'data' => $data,
            'expires' => time() + self::$cacheTtl,
        ];
    }
}
