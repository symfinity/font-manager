<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Service\FontDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontDownloaderExtendedTest extends TestCase
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

    public function testExtractFontUrlsFromCss(): void
    {
        $css = <<<'CSS'
@font-face {
  font-family: 'Roboto';
  src: url(https://fonts.gstatic.com/font1.woff2) format('woff2');
}
@font-face {
  font-family: 'Roboto';
  src: url(https://fonts.gstatic.com/font2.woff2) format('woff2');
}
CSS;

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font1-data'),
            new MockResponse('font2-data'),
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertCount(2, $result['files']);
    }

    public function testSanitizeFontName(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('@font-face { src: url(https://example.com/font.woff2); }'),
            new MockResponse('font-data'),
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Open Sans', [400], ['normal']);

        // Check that font name is sanitized in path
        self::assertStringContainsString('open-sans', $result['cssPath']);
    }

    public function testProcessedCssWithRelativePaths(): void
    {
        $css = <<<'CSS'
@font-face {
  font-family: 'Roboto';
  src: url(https://fonts.gstatic.com/s/roboto/v30/font.woff2) format('woff2');
}
CSS;

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-data'),
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        // CSS should have local paths
        self::assertStringNotContainsString('https://', $result['css']);
        self::assertStringContainsString('url(', $result['css']);
    }

    public function testDownloadFontWithMultipleWeights(): void
    {
        $css = <<<'CSS'
@font-face {
  font-family: 'Roboto';
  font-weight: 400;
  src: url(https://fonts.gstatic.com/roboto-400.woff2) format('woff2');
}
@font-face {
  font-family: 'Roboto';
  font-weight: 700;
  src: url(https://fonts.gstatic.com/roboto-700.woff2) format('woff2');
}
CSS;

        $httpClient = new MockHttpClient([
            new MockResponse($css),
            new MockResponse('font-400'),
            new MockResponse('font-700'),
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400, 700], ['normal']);

        self::assertGreaterThanOrEqual(2, count($result['files']));
    }
}
