<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Provider\LocalFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class LocalFontsProviderTest extends TestCase
{
    public function testGetName(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient);

        self::assertSame('local', $provider->getName());
    }

    public function testSupportsFeatures(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient);

        self::assertTrue($provider->supports(ProviderFeature::SEARCH));
        self::assertTrue($provider->supports(ProviderFeature::METADATA));
        self::assertFalse($provider->supports(ProviderFeature::VARIABLE_FONTS));
        self::assertFalse($provider->supports(ProviderFeature::CDN));
    }

    public function testSearchFonts(): void
    {
        $config = [
            'fonts' => [
                'BrandSerif' => [
                    'display_name' => 'Brand Serif',
                    'category' => 'serif',
                    'weights' => [400, 700],
                    'styles' => ['normal', 'italic'],
                ],
                'BrandSans' => [
                    'display_name' => 'Brand Sans',
                    'category' => 'sans-serif',
                    'weights' => [300, 400],
                    'styles' => ['normal'],
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $results = $provider->searchFonts('Brand');

        self::assertCount(2, $results);
        self::assertSame('BrandSerif', $results[0]['family']);
        self::assertSame('serif', $results[0]['category']);
    }

    public function testSearchFontsFilters(): void
    {
        $config = [
            'fonts' => [
                'BrandSerif' => ['category' => 'serif', 'weights' => [400], 'styles' => ['normal']],
                'BrandSans' => ['category' => 'sans-serif', 'weights' => [400], 'styles' => ['normal']],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $results = $provider->searchFonts('Serif');

        self::assertCount(1, $results);
        self::assertSame('BrandSerif', $results[0]['family']);
    }

    public function testGetFontMetadata(): void
    {
        $config = [
            'fonts' => [
                'BrandSerif' => [
                    'display_name' => 'Brand Serif',
                    'category' => 'serif',
                    'weights' => [400, 700],
                    'styles' => ['normal', 'italic'],
                    'files' => [
                        '400-normal' => 'brand-regular.woff2',
                    ],
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $metadata = $provider->getFontMetadata('BrandSerif');

        self::assertIsArray($metadata);
        self::assertSame('BrandSerif', $metadata['family']);
        self::assertSame('Brand Serif', $metadata['display_name']);
        self::assertSame('serif', $metadata['category']);
        self::assertSame('local', $metadata['provider']);
    }

    public function testGetFontMetadataReturnsNullForMissingFont(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, ['fonts' => []]);

        $metadata = $provider->getFontMetadata('NonExistent');

        self::assertNull($metadata);
    }

    public function testDownloadFontCssThrowsForMissingFont(): void
    {
        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, ['fonts' => []]);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Local font 'NonExistent' not found");

        $provider->downloadFontCss('NonExistent', [400], ['normal']);
    }

    public function testValidateFonts(): void
    {
        $config = [
            'directory' => '/tmp/fonts',
            'fonts' => [
                'TestFont' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [
                        '400-normal' => 'test-regular.woff2',
                    ],
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $errors = $provider->validateFonts();

        self::assertNotEmpty($errors);
        self::assertSame('TestFont', $errors[0]['font']);
        self::assertSame('400-normal', $errors[0]['variant']);
    }

    public function testDownloadFontCssThrowsConfigurationExceptionWhenFontNotConfigured(): void
    {
        $provider = new LocalFontsProvider(new MockHttpClient());

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Local font 'NonExistent' not found");

        $provider->downloadFontCss('NonExistent', [400], ['normal']);
    }
}
