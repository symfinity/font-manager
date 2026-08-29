<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Provider\FontsourceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsourceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        FontsourceProvider::clearCache();
    }

    public function testGetName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new FontsourceProvider($httpClient);

        self::assertSame('fontsource', $provider->getName());
    }

    public function testSupportsFeatures(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new FontsourceProvider($httpClient);

        self::assertTrue($provider->supports(ProviderFeature::SEARCH));
        self::assertTrue($provider->supports(ProviderFeature::METADATA));
        self::assertTrue($provider->supports(ProviderFeature::VARIABLE_FONTS));
        self::assertTrue($provider->supports(ProviderFeature::CDN));
    }

    public function testSearchFonts(): void
    {
        $jsonData = json_encode([
            'objects' => [
                [
                    'package' => [
                        'name' => '@fontsource/roboto',
                        'description' => 'Roboto font',
                    ],
                ],
                [
                    'package' => [
                        'name' => '@fontsource/open-sans',
                        'description' => 'Open Sans font',
                    ],
                ],
            ],
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new FontsourceProvider($httpClient);

        $results = $provider->searchFonts('roboto', 10);

        self::assertIsArray($results);
        self::assertNotEmpty($results);
        self::assertSame('roboto', $results[0]['family']);
    }

    public function testGetFontMetadata(): void
    {
        $jsonData = json_encode([
            'name' => '@fontsource/roboto',
            'description' => 'Self-host the Roboto font',
            'dist-tags' => ['latest' => '5.0.8'],
            'license' => 'MIT',
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new FontsourceProvider($httpClient);

        $metadata = $provider->getFontMetadata('roboto');

        self::assertIsArray($metadata);
        self::assertSame('roboto', $metadata['family']);
        self::assertSame('fontsource', $metadata['provider']);
        self::assertSame('5.0.8', $metadata['version']);
    }

    public function testDownloadFontCss(): void
    {
        $responses = [
            // Version lookup
            new MockResponse((string) json_encode([
                'dist-tags' => ['latest' => '5.0.0'],
            ])),
            // CSS download for weight 400
            new MockResponse('/* Fontsource 400 CSS */'),
            // CSS download for weight 700
            new MockResponse('/* Fontsource 700 CSS */'),
        ];

        $httpClient = new MockHttpClient($responses);
        $provider = new FontsourceProvider($httpClient);

        $css = $provider->downloadFontCss('roboto', [400, 700], ['normal']);

        self::assertStringContainsString('Fontsource 400 CSS', $css);
        self::assertStringContainsString('Fontsource 700 CSS', $css);
    }

    public function testRenderCdnLinks(): void
    {
        $mockResponse = new MockResponse((string) json_encode([
            'dist-tags' => ['latest' => '5.0.0'],
        ]));

        $httpClient = new MockHttpClient($mockResponse);
        $provider = new FontsourceProvider($httpClient);

        $html = $provider->renderCdnLinks('roboto', [400, 700], ['normal'], FontDisplay::SWAP);

        self::assertStringContainsString('<link rel="preconnect" href="https://cdn.jsdelivr.net">', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/roboto', $html);
        self::assertStringContainsString('400.css', $html);
        self::assertStringContainsString('700.css', $html);
    }

    public function testGetFontVariants(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new FontsourceProvider($httpClient);

        $variants = $provider->getFontVariants('roboto');

        self::assertArrayHasKey('weights', $variants);
        self::assertArrayHasKey('styles', $variants);
        // Fontsource supports all standard weights
        self::assertContains(400, $variants['weights']);
        self::assertContains(700, $variants['weights']);
    }
}
