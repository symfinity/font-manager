<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Twig;

use Symfinity\FontManager\Provider\BunnyFontsProvider;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Twig\FontManagerRuntime;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;

final class FontManagerRuntimeTest extends TestCase
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

    public function testRenderFontsWithDefaultProvider(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $runtime = new FontManagerRuntime($registry, false, null, $this->filesystem);

        $html = $runtime->renderFonts('Roboto', [400, 700], ['normal']);

        self::assertStringContainsString('fonts.googleapis.com', $html);
        self::assertStringContainsString('Roboto', $html);
    }

    public function testRenderFontsWithSpecificProvider(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);
        $bunnyProvider = new BunnyFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);
        $registry->registerProvider($bunnyProvider);

        $runtime = new FontManagerRuntime($registry, false, null, $this->filesystem);

        // New parameter order: name, weights, styles, monospace, display, provider
        $html = $runtime->renderFonts('Roboto', [400, 700], ['normal'], false, 'swap', 'bunny');

        self::assertStringContainsString('fonts.bunny.net', $html);
        self::assertStringNotContainsString('googleapis', $html);
    }

    public function testRenderFontsWithStringWeights(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $runtime = new FontManagerRuntime($registry, false, null, $this->filesystem);

        $html = $runtime->renderFonts('Roboto', '400 700', 'normal italic');

        self::assertStringContainsString('Roboto', $html);
    }

    public function testRenderFontsMonospace(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $runtime = new FontManagerRuntime($registry, false, null, $this->filesystem);

        // New parameter order: name, weights, styles, monospace, provider, display
        $html = $runtime->renderFonts('JetBrains Mono', [400], ['normal'], true);

        self::assertStringContainsString('code', $html);
        self::assertStringContainsString('pre', $html);
    }

    public function testRenderFontsGeneratesInlineStyles(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $runtime = new FontManagerRuntime($registry, false, null, $this->filesystem);

        $html = $runtime->renderFonts('Inter', [300, 400, 700], ['normal']);

        self::assertStringContainsString('<style>', $html);
        self::assertStringContainsString('--font-family-inter', $html);
        self::assertStringContainsString('body', $html);
        self::assertStringContainsString('h1', $html);
    }

    public function testRenderLockedFonts(): void
    {
        $manifestFile = $this->tempDir . '/manifest.json';
        $manifest = [
            'fonts' => [
                'Roboto' => [
                    'weights' => [400],
                    'css' => 'assets/fonts/roboto.css',
                ],
            ],
        ];

        $this->filesystem->dumpFile($manifestFile, (string) json_encode($manifest));

        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $runtime = new FontManagerRuntime($registry, true, $manifestFile, $this->filesystem);

        $html = $runtime->renderFonts('Roboto', [400], ['normal']);

        self::assertStringContainsString('/assets/fonts/roboto.css', $html);
        self::assertStringNotContainsString('googleapis', $html);
    }
}
