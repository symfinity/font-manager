<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Exception\FontDownloadException;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Service\FontDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontDownloaderTest extends TestCase
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

    public function testDownloadFontCreatesDirectory(): void
    {
        $cssResponse = new MockResponse('@font-face { src: url(https://example.com/font.woff2); }');
        $fontResponse = new MockResponse('fake-font-data');

        $httpClient = new MockHttpClient([$cssResponse, $fontResponse]);
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertTrue($this->filesystem->exists($this->tempDir));
        self::assertArrayHasKey('files', $result);
        self::assertArrayHasKey('css', $result);
        self::assertArrayHasKey('cssPath', $result);
    }

    public function testDownloadFontSavesFiles(): void
    {
        $cssResponse = new MockResponse('@font-face { src: url(https://example.com/font.woff2); }');
        $fontResponse = new MockResponse('fake-font-data');

        $httpClient = new MockHttpClient([$cssResponse, $fontResponse]);
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $result = $downloader->downloadFont('Roboto', [400], ['normal']);

        self::assertNotEmpty($result['files']);
        self::assertTrue($this->filesystem->exists($result['cssPath']));
    }

    public function testDownloadFontThrowsOnCssError(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 404]),
        ]);

        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $this->expectException(FontDownloadException::class);
        $this->expectExceptionMessage('Failed to download CSS');

        $downloader->downloadFont('NonExistent', [400], ['normal']);
    }

    public function testGetProviderRegistry(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir, $httpClient, $registry, $this->filesystem);

        $retrievedRegistry = $downloader->getProviderRegistry();

        self::assertSame($registry, $retrievedRegistry);
    }
}
