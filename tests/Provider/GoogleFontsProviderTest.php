<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GoogleFontsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        GoogleFontsProvider::clearCache();
    }

    public function testGetName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        self::assertSame('google', $provider->getName());
    }

    public function testSupportsSearch(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        self::assertTrue($provider->supports(ProviderFeature::SEARCH));
        self::assertTrue($provider->supports(ProviderFeature::METADATA));
        self::assertTrue($provider->supports(ProviderFeature::VARIABLE_FONTS));
        self::assertTrue($provider->supports(ProviderFeature::CDN));
    }

    public function testSearchFontsRequiresApiKey(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient, []);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Google Fonts API key is required for search');

        $provider->searchFonts('Roboto');
    }

    public function testSearchFontsReturnsResults(): void
    {
        $jsonData = json_encode([
            'items' => [
                ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
                ['family' => 'Open Sans', 'variants' => ['regular', '600'], 'category' => 'sans-serif'],
            ],
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $results = $provider->searchFonts('Roboto', 10);

        self::assertIsArray($results);
        self::assertNotEmpty($results);
        self::assertCount(1, $results);
        self::assertSame('Roboto', $results[0]['family']);
        self::assertSame('sans-serif', $results[0]['category']);
    }

    public function testSearchFontsRespectsMaxResults(): void
    {
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = ['family' => "Font{$i}", 'variants' => ['regular'], 'category' => 'sans-serif'];
        }

        $mockResponse = new MockResponse((string) json_encode(['items' => $items]));
        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $results = $provider->searchFonts('Font', 5);

        self::assertCount(5, $results);
    }

    public function testGetFontMetadata(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
                    ['family' => 'Ubuntu', 'variants' => ['300', 'regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $metadata = $provider->getFontMetadata('Ubuntu');

        self::assertIsArray($metadata);
        self::assertSame('Ubuntu', $metadata['family']);
        self::assertSame('sans-serif', $metadata['category']);
    }

    public function testGetFontMetadataReturnsNullWhenNotFound(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['regular'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $metadata = $provider->getFontMetadata('NonExistent');

        self::assertNull($metadata);
    }

    public function testDownloadFontCss(): void
    {
        $mockResponse = new MockResponse('/* Font CSS */');
        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient);

        $css = $provider->downloadFontCss('Roboto', [400, 700], ['normal']);

        self::assertStringContainsString('Font CSS', $css);
    }

    public function testGetFontVariants(): void
    {
        $mockResponse = new MockResponse(
            (string) json_encode([
                'items' => [
                    ['family' => 'Roboto', 'variants' => ['300', 'regular', '700', '300italic', 'italic', '700italic'], 'category' => 'sans-serif'],
                ],
            ])
        );

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $variants = $provider->getFontVariants('Roboto');

        self::assertArrayHasKey('weights', $variants);
        self::assertArrayHasKey('styles', $variants);
        self::assertContains(300, $variants['weights']);
        self::assertContains(400, $variants['weights']);
        self::assertContains(700, $variants['weights']);
        self::assertContains('normal', $variants['styles']);
        self::assertContains('italic', $variants['styles']);
    }

    public function testRenderCdnLinks(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $html = $provider->renderCdnLinks('Roboto', [400, 700], ['normal'], FontDisplay::SWAP);

        self::assertStringContainsString('<link rel="preconnect" href="https://fonts.googleapis.com">', $html);
        self::assertStringContainsString('<link rel="preconnect" href="https://fonts.gstatic.com"', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="https://fonts.googleapis.com/css2', $html);
        self::assertStringContainsString('family=Roboto', $html);
    }

    public function testSearchFontsThrowsConfigurationExceptionWhenApiKeyMissing(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Google Fonts API key is required');

        $provider->searchFonts('Roboto');
    }
}
