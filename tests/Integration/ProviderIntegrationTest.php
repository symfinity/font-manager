<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Integration;

use Symfinity\FontManager\Provider\BunnyFontsProvider;
use Symfinity\FontManager\Provider\FontsourceProvider;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\LocalFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ProviderIntegrationTest extends TestCase
{
    public function testAllProvidersCanBeRegistered(): void
    {
        $httpClient = new MockHttpClient();

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);
        $fontsourceProvider = new FontsourceProvider($httpClient);
        $localProvider = new LocalFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);
        $registry->registerProvider($fontsourceProvider);
        $registry->registerProvider($localProvider);

        self::assertTrue($registry->hasProvider('google'));
        self::assertTrue($registry->hasProvider('bunny'));
        self::assertTrue($registry->hasProvider('fontsource'));
        self::assertTrue($registry->hasProvider('local'));
    }

    public function testProviderSwitching(): void
    {
        $httpClient = new MockHttpClient();

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);

        $default = $registry->getDefaultProvider();
        self::assertSame('google', $default->getName());

        $bunny = $registry->getProvider('bunny');
        self::assertSame('bunny', $bunny->getName());
    }

    public function testAllProvidersGenerateCdnLinks(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) json_encode(['dist-tags' => ['latest' => '5.0.0']])), // For Fontsource
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);
        $fontsourceProvider = new FontsourceProvider($httpClient);

        $googleHtml = $googleProvider->renderCdnLinks('Roboto', [400], ['normal']);
        self::assertStringContainsString('googleapis', $googleHtml);

        $bunnyHtml = $bunnyProvider->renderCdnLinks('Roboto', [400], ['normal']);
        self::assertStringContainsString('bunny.net', $bunnyHtml);

        $fontsourceHtml = $fontsourceProvider->renderCdnLinks('roboto', [400], ['normal']);
        self::assertStringContainsString('jsdelivr', $fontsourceHtml);
    }
}
