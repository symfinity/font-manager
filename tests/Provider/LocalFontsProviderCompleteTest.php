<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Exception\ValidationException;
use Symfinity\FontManager\Provider\LocalFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;

final class LocalFontsProviderCompleteTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/font-manager-test-' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testGetName(): void
    {
        $provider = new LocalFontsProvider(new MockHttpClient());
        self::assertSame('local', $provider->getName());
    }

    public function testSupports(): void
    {
        $provider = new LocalFontsProvider(new MockHttpClient());

        self::assertTrue($provider->supports(ProviderFeature::SEARCH));
        self::assertTrue($provider->supports(ProviderFeature::METADATA));
        self::assertFalse($provider->supports(ProviderFeature::VARIABLE_FONTS));
        self::assertFalse($provider->supports(ProviderFeature::CDN));
    }

    public function testSearchFontsWithQuery(): void
    {
        $config = [
            'fonts' => [
                'Roboto' => [
                    'display_name' => 'Roboto',
                    'category' => 'sans-serif',
                    'weights' => [400, 700],
                    'styles' => ['normal'],
                ],
                'Open Sans' => [
                    'display_name' => 'Open Sans',
                    'category' => 'sans-serif',
                    'weights' => [400],
                    'styles' => ['normal', 'italic'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $results = $provider->searchFonts('Roboto');

        self::assertCount(1, $results);
        self::assertSame('Roboto', $results[0]['family']);
    }

    public function testSearchFontsReturnsAllWhenNoQuery(): void
    {
        $config = [
            'fonts' => [
                'Font1' => ['weights' => [400], 'styles' => ['normal']],
                'Font2' => ['weights' => [400], 'styles' => ['normal']],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $results = $provider->searchFonts('');

        self::assertCount(2, $results);
    }

    public function testSearchFontsRespectsMaxResults(): void
    {
        $config = [
            'fonts' => [
                'Font1' => ['weights' => [400], 'styles' => ['normal']],
                'Font2' => ['weights' => [400], 'styles' => ['normal']],
                'Font3' => ['weights' => [400], 'styles' => ['normal']],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $results = $provider->searchFonts('', 2);

        self::assertCount(2, $results);
    }

    public function testGetFontMetadataReturnsNullWhenNotFound(): void
    {
        $provider = new LocalFontsProvider(new MockHttpClient());

        $metadata = $provider->getFontMetadata('NonExistent');

        self::assertNull($metadata);
    }

    public function testGetFontMetadataWithAllFields(): void
    {
        $config = [
            'fonts' => [
                'TestFont' => [
                    'display_name' => 'Test Font',
                    'category' => 'serif',
                    'weights' => [300, 400, 700],
                    'styles' => ['normal', 'italic'],
                    'files' => ['300-normal' => 'test-300.woff2'],
                    'unicode_range' => 'U+0000-00FF',
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $metadata = $provider->getFontMetadata('TestFont');

        self::assertIsArray($metadata);
        self::assertSame('TestFont', $metadata['family']);
        self::assertSame('Test Font', $metadata['display_name']);
        self::assertSame('serif', $metadata['category']);
        self::assertSame('U+0000-00FF', $metadata['unicode_range']);
        self::assertSame('local', $metadata['provider']);
    }

    public function testDownloadFontCssThrowsWhenFontNotConfigured(): void
    {
        $provider = new LocalFontsProvider(new MockHttpClient());

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Local font 'NonExistent' not found");

        $provider->downloadFontCss('NonExistent', [400], ['normal']);
    }

    public function testDownloadFontCssThrowsWhenFileNotExists(): void
    {
        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [
                        '400-normal' => 'missing.woff2',
                    ],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Font file not found');

        $provider->downloadFontCss('TestFont', [400], ['normal']);
    }

    public function testDownloadFontCssWithWoffFormat(): void
    {
        $fontFile = $this->tempDir . '/test.woff';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.woff'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString("format('woff')", $css);
    }

    public function testDownloadFontCssWithTtfFormat(): void
    {
        $fontFile = $this->tempDir . '/test.ttf';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.ttf'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString("format('truetype')", $css);
    }

    public function testDownloadFontCssWithOtfFormat(): void
    {
        $fontFile = $this->tempDir . '/test.otf';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.otf'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString("format('opentype')", $css);
    }

    public function testDownloadFontCssWithEotFormat(): void
    {
        $fontFile = $this->tempDir . '/test.eot';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.eot'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString("format('embedded-opentype')", $css);
    }

    public function testDownloadFontCssWithCustomDisplay(): void
    {
        $fontFile = $this->tempDir . '/test.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.woff2'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal'], FontDisplay::BLOCK);

        self::assertStringContainsString('font-display: block', $css);
    }

    public function testDownloadFontCssWithMultipleStyles(): void
    {
        $file1 = $this->tempDir . '/test-400-normal.woff2';
        $file2 = $this->tempDir . '/test-400-italic.woff2';
        $this->filesystem->dumpFile($file1, 'fake-font');
        $this->filesystem->dumpFile($file2, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => [
                        '400-normal' => 'test-400-normal.woff2',
                        '400-italic' => 'test-400-italic.woff2',
                    ],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal', 'italic']);

        self::assertStringContainsString('font-style: normal', $css);
        self::assertStringContainsString('font-style: italic', $css);
    }

    public function testValidateFontsReturnsEmptyWhenAllExist(): void
    {
        $fontFile = $this->tempDir . '/test.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => ['400-normal' => 'test.woff2'],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $errors = $provider->validateFonts();

        self::assertEmpty($errors);
    }

    public function testValidateFontsReturnsMissingFiles(): void
    {
        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'files' => [
                        '400-normal' => 'missing1.woff2',
                        '700-normal' => 'missing2.woff2',
                    ],
                ],
            ],
        ];

        $provider = new LocalFontsProvider(new MockHttpClient(), $config);

        $errors = $provider->validateFonts();

        self::assertCount(2, $errors);
        self::assertSame('TestFont', $errors[0]['font']);
        self::assertSame('File not found', $errors[0]['error']);
    }
}
