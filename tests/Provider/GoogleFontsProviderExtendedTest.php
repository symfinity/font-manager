<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GoogleFontsProviderExtendedTest extends TestCase
{
    protected function setUp(): void
    {
        GoogleFontsProvider::clearCache();
    }

    public function testGetFontMetadataWithApiKey(): void
    {
        $jsonData = (string) json_encode([
            'items' => [
                [
                    'family' => 'Roboto',
                    'variants' => ['regular', '700', 'italic', '700italic'],
                    'category' => 'sans-serif',
                    'version' => 'v30',
                ],
            ],
        ]);

        $httpClient = new MockHttpClient([
            new MockResponse($jsonData),
        ]);

        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $metadata = $provider->getFontMetadata('Roboto');

        self::assertIsArray($metadata);
        self::assertSame('Roboto', $metadata['family']);
        self::assertSame('sans-serif', $metadata['category']);
    }

    public function testDownloadFontCssWithoutApiKey(): void
    {
        $cssResponse = '@font-face { font-family: Roboto; }';

        $httpClient = new MockHttpClient([
            new MockResponse($cssResponse),
        ]);

        $provider = new GoogleFontsProvider($httpClient);

        $css = $provider->downloadFontCss('Roboto', [400], ['normal']);

        self::assertStringContainsString('font-family', $css);
    }

    public function testRenderCdnLinksWithMultipleWeights(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $html = $provider->renderCdnLinks('Roboto', [300, 400, 700], ['normal']);

        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('Roboto', $html);
        self::assertStringContainsString('300', $html);
        self::assertStringContainsString('700', $html);
    }

    public function testRenderCdnLinksWithItalic(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        $html = $provider->renderCdnLinks('Roboto', [400], ['normal', 'italic']);

        self::assertStringContainsString('ital,wght', $html);
    }

    public function testSupportsFeatures(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test']);

        self::assertTrue($provider->supports(ProviderFeature::SEARCH));
        self::assertTrue($provider->supports(ProviderFeature::METADATA));
        self::assertTrue($provider->supports(ProviderFeature::CDN));
    }

    public function testRequiresAuthWithoutApiKey(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient);

        self::assertFalse($provider->requiresAuth());
        self::assertTrue($provider->isReady());
    }

    public function testRequiresAuthWithApiKey(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new GoogleFontsProvider($httpClient, ['api_key' => 'test']);

        self::assertFalse($provider->requiresAuth());
        self::assertTrue($provider->isReady());
    }
}
