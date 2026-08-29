<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Provider\LocalFontsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;

final class LocalFontsProviderExtendedTest extends TestCase
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

    public function testDownloadFontCssGeneratesValidCss(): void
    {
        $fontFile = $this->tempDir . '/test-regular.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font-data');

        $config = [
            'directory' => $this->tempDir,
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

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString('@font-face', $css);
        self::assertStringContainsString('TestFont', $css);
        self::assertStringContainsString('test-regular.woff2', $css);
        self::assertStringContainsString('font-weight: 400', $css);
    }

    public function testDownloadFontCssWithUnicodeRange(): void
    {
        $fontFile = $this->tempDir . '/test-regular.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font-data');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [
                        '400-normal' => 'test-regular.woff2',
                    ],
                    'unicode_range' => 'U+0000-00FF',
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $css = $provider->downloadFontCss('TestFont', [400], ['normal']);

        self::assertStringContainsString('unicode-range: U+0000-00FF', $css);
    }

    public function testDownloadFontCssSkipsUnavailableVariants(): void
    {
        $fontFile = $this->tempDir . '/test-regular.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font-data');

        $config = [
            'directory' => $this->tempDir,
            'fonts' => [
                'TestFont' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [
                        '400-normal' => 'test-regular.woff2',
                        // 700 is missing
                    ],
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        // Request 700 which doesn't exist - should skip it
        $css = $provider->downloadFontCss('TestFont', [400, 700], ['normal']);

        self::assertStringContainsString('font-weight: 400', $css);
        self::assertStringNotContainsString('font-weight: 700', $css);
    }

    public function testRenderCdnLinksGeneratesInlineCss(): void
    {
        $fontFile = $this->tempDir . '/test-regular.woff2';
        $this->filesystem->dumpFile($fontFile, 'fake-font-data');

        $config = [
            'directory' => $this->tempDir,
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

        $html = $provider->renderCdnLinks('TestFont', [400], ['normal']);

        self::assertStringContainsString('<style>', $html);
        self::assertStringContainsString('@font-face', $html);
        self::assertStringContainsString('</style>', $html);
    }

    public function testGetFormatFromFilename(): void
    {
        $config = ['fonts' => []];
        $httpClient = new MockHttpClient();
        $provider = new LocalFontsProvider($httpClient, $config);

        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('getFormatFromFilename');
        $method->setAccessible(true);

        self::assertSame('woff2', $method->invoke($provider, 'font.woff2'));
        self::assertSame('woff', $method->invoke($provider, 'font.woff'));
        self::assertSame('truetype', $method->invoke($provider, 'font.ttf'));
        self::assertSame('opentype', $method->invoke($provider, 'font.otf'));
        self::assertSame('embedded-opentype', $method->invoke($provider, 'font.eot'));
        self::assertSame('woff2', $method->invoke($provider, 'font.unknown'));
    }
}
