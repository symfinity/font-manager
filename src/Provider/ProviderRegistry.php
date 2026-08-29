<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Exception\ProviderException;

/**
 * Registry for managing font providers.
 */
final class ProviderRegistry
{
    /** @var array<string, FontProviderInterface> */
    private array $providers = [];

    private string $defaultProvider = 'google';

    public function __construct(
        ?string $defaultProvider = null
    ) {
        if (null !== $defaultProvider) {
            $this->defaultProvider = $defaultProvider;
        }
    }

    /**
     * Register a provider.
     */
    public function registerProvider(FontProviderInterface $provider): void
    {
        $this->providers[$provider->getName()] = $provider;
    }

    /**
     * Get a provider by name.
     */
    public function getProvider(string $name): FontProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new ProviderException(sprintf('Font provider "%s" not found. Available providers: %s', $name, implode(', ', array_keys($this->providers))));
        }

        $provider = $this->providers[$name];

        if (!$provider->isReady()) {
            throw new ProviderException(sprintf('Font provider "%s" is not ready. Check configuration (API key, etc.)', $name));
        }

        return $provider;
    }

    /**
     * Get the default provider.
     */
    public function getDefaultProvider(): FontProviderInterface
    {
        return $this->getProvider($this->defaultProvider);
    }

    /**
     * Check if provider exists.
     */
    public function hasProvider(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * Get all registered providers.
     *
     * @return array<string, FontProviderInterface>
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get all enabled providers.
     *
     * @return array<string, FontProviderInterface>
     */
    public function getEnabledProviders(): array
    {
        return array_filter(
            $this->providers,
            fn (FontProviderInterface $provider): bool => $provider->isReady()
        );
    }

    /**
     * Get default provider name.
     */
    public function getDefaultProviderName(): string
    {
        return $this->defaultProvider;
    }
}
