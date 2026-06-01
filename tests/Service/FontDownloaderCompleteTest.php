<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\FontDownloadException;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Service\FontDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontDownloaderCompleteTest extends TestCase
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

    public function testDownloadFontWithSingleFile(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('fake-font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertArrayHasKey('files', $result);
        self::assertArrayHasKey('css', $result);
        self::assertArrayHasKey('cssPath', $result);
        self::assertNotEmpty($result['files']);
    }

    public function testDownloadFontWithMultipleFiles(): void
    {
        $css = <<<'CSS'
@font-face {
  src: url(https://example.com/font1.woff2);
}
@font-face {
  src: url(https://example.com/font2.woff2);
}
@font-face {
  src: url(https://example.com/font3.woff2);
}
CSS;

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font1'),
            new MockResponse('font2'),
            new MockResponse('font3'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400, 700], ['normal']);

        self::assertCount(3, $result['files']);
    }

    public function testDownloadFontSanitizesFilenames(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Open Sans', [400], ['normal']);

        // Font name should be sanitized (spaces replaced with dashes)
        self::assertStringContainsString('open-sans', $result['cssPath']);
    }

    public function testDownloadFontReplacesUrlsInCss(): void
    {
        $css = '@font-face { src: url(https://fonts.gstatic.com/font.woff2) format("woff2"); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        // CSS should not contain original URL
        self::assertStringNotContainsString('fonts.gstatic.com', $result['css']);
        // Should contain local path
        self::assertStringContainsString('url(', $result['css']);
    }

    public function testDownloadFontThrowsOnHttpError(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $this->expectException(FontDownloadException::class);
        $this->expectExceptionMessage('Failed to download CSS');

        $downloader->downloadFont('Roboto', [400], ['normal']);
    }

    public function testDownloadFontThrowsWhenFontFileDownloadFails(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('', ['http_code' => 404]),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $this->expectException(FontDownloadException::class);
        $this->expectExceptionMessage('Failed to download font file');

        $downloader->downloadFont('Roboto', [400], ['normal']);
    }

    public function testDownloadFontCreatesDirectoryStructure(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertTrue($this->filesystem->exists($this->tempDir));
        self::assertTrue($this->filesystem->exists(dirname($result['cssPath'])));
    }

    public function testDownloadFontWithQueryParametersInUrl(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2?v=1.0&hash=abc123); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertNotEmpty($result['files']);
        // Filename should not contain query parameters
        foreach ($result['files'] as $file) {
            self::assertStringNotContainsString('?', $file);
        }
    }

    public function testDownloadFontUsesCorrectDisplay(): void
    {
        $css = '@font-face { src: url(https://example.com/font.woff2); }';

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $provider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($provider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        // Test with different display values
        $result1 = $downloader->downloadFont('Roboto', [400], ['normal'], FontDisplay::BLOCK);
        $result2 = $downloader->downloadFont('Roboto', [400], ['normal'], FontDisplay::SWAP);

        // Both should succeed
        self::assertNotEmpty($result1['files']);
        self::assertNotEmpty($result2['files']);
    }
}
