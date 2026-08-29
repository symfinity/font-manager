<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Exception\ProviderException;
use Symfinity\FontManager\Provider\BunnyFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BunnyFontsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        BunnyFontsProvider::clearCache();
    }

    public function testGetName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        self::assertSame('bunny', $provider->getName());
    }

    public function testSupportsFeatures(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        self::assertFalse($provider->supports(ProviderFeature::SEARCH));
        self::assertFalse($provider->supports(ProviderFeature::METADATA));
        self::assertTrue($provider->supports(ProviderFeature::VARIABLE_FONTS));
        self::assertTrue($provider->supports(ProviderFeature::CDN));
    }

    public function testRequiresAuthReturnsFalse(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        self::assertFalse($provider->requiresAuth());
    }

    public function testIsReadyReturnsTrue(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        self::assertTrue($provider->isReady());
    }

    public function testSearchFontsThrowsException(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Search API is not available for Bunny Fonts');

        $provider->searchFonts('Roboto');
    }

    public function testGetFontMetadataReturnsBasicInfo(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        $metadata = $provider->getFontMetadata('Roboto');

        self::assertIsArray($metadata);
        self::assertSame('Roboto', $metadata['family']);
        self::assertSame('bunny', $metadata['provider']);
    }

    public function testDownloadFontCss(): void
    {
        $mockResponse = new MockResponse('/* Bunny Font CSS */');
        $httpClient = new MockHttpClient($mockResponse);
        $provider = new BunnyFontsProvider($httpClient);

        $css = $provider->downloadFontCss('Roboto', [400, 700], ['normal']);

        self::assertStringContainsString('Bunny Font CSS', $css);
    }

    public function testRenderCdnLinks(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        $html = $provider->renderCdnLinks('Roboto', [400, 700], ['normal'], FontDisplay::SWAP);

        self::assertStringContainsString('<link rel="preconnect" href="https://fonts.bunny.net">', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="https://fonts.bunny.net/css2', $html);
        self::assertStringContainsString('family=Roboto', $html);
        self::assertStringNotContainsString('googleapis', $html);
    }

    public function testGetFontVariantsReturnsDefaults(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new BunnyFontsProvider($httpClient);

        $variants = $provider->getFontVariants('Roboto');

        self::assertArrayHasKey('weights', $variants);
        self::assertArrayHasKey('styles', $variants);
        self::assertContains(400, $variants['weights']);
        self::assertContains(700, $variants['weights']);
    }
}
