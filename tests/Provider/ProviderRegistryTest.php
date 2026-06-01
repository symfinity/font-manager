<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Exception\ProviderException;
use Symfinity\FontManager\Provider\BunnyFontsProvider;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class ProviderRegistryTest extends TestCase
{
    public function testRegisterAndGetProvider(): void
    {
        $registry = new ProviderRegistry();
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $registry->registerProvider($provider);

        $retrieved = $registry->getProvider('google');

        self::assertSame($provider, $retrieved);
    }

    public function testGetProviderThrowsForUnregistered(): void
    {
        $registry = new ProviderRegistry();

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Font provider "nonexistent" not found');

        $registry->getProvider('nonexistent');
    }

    public function testGetDefaultProvider(): void
    {
        $registry = new ProviderRegistry('bunny');
        $httpClient = new MockHttpClient();

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);

        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);

        $default = $registry->getDefaultProvider();

        self::assertSame($bunnyProvider, $default);
    }

    public function testHasProvider(): void
    {
        $registry = new ProviderRegistry();
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $registry->registerProvider($provider);

        self::assertTrue($registry->hasProvider('google'));
        self::assertFalse($registry->hasProvider('nonexistent'));
    }

    public function testGetAllProviders(): void
    {
        $registry = new ProviderRegistry();
        $httpClient = new MockHttpClient();

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);

        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);

        $all = $registry->getAllProviders();

        self::assertCount(2, $all);
        self::assertArrayHasKey('google', $all);
        self::assertArrayHasKey('bunny', $all);
    }

    public function testGetEnabledProviders(): void
    {
        $registry = new ProviderRegistry();
        $httpClient = new MockHttpClient();

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);

        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);

        $enabled = $registry->getEnabledProviders();

        self::assertCount(2, $enabled);
    }

    public function testGetDefaultProviderName(): void
    {
        $registry = new ProviderRegistry('bunny');

        self::assertSame('bunny', $registry->getDefaultProviderName());
    }
}
