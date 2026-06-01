<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service\Performance;

use Symfinity\FontManager\Provider\BunnyFontsProvider;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Service\Performance\FontPerformanceOptimizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class FontPerformanceOptimizerTest extends TestCase
{
    private FontPerformanceOptimizer $optimizer;

    protected function setUp(): void
    {
        $this->optimizer = new FontPerformanceOptimizer();
    }

    public function testGenerateResourceHintsForProviders(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient, []);
        $bunnyProvider = new BunnyFontsProvider($httpClient, []);

        $hints = $this->optimizer->generateResourceHints([$googleProvider, $bunnyProvider]);

        $this->assertArrayHasKey('https://fonts.googleapis.com', $hints);
        $this->assertArrayHasKey('https://fonts.bunny.net', $hints);
        $this->assertContains('preconnect', $hints['https://fonts.googleapis.com']);
    }

    public function testRenderResourceHints(): void
    {
        $hints = [
            'https://fonts.bunny.net' => ['preconnect', 'dns-prefetch'],
        ];

        $html = $this->optimizer->renderResourceHints($hints);

        $this->assertStringContainsString('rel="preconnect"', $html);
        $this->assertStringContainsString('href="https://fonts.bunny.net"', $html);
        $this->assertStringContainsString('crossorigin', $html);
    }

    public function testGeneratePreloadLinks(): void
    {
        /** @var array<array{url: string, as: string, type: string, crossorigin?: bool}> $fonts */
        $fonts = [
            [
                'url' => 'https://example.com/font.woff2',
                'as' => 'font',
                'type' => 'font/woff2',
                'crossorigin' => true,
            ],
        ];

        $html = $this->optimizer->generatePreloadLinks($fonts);

        $this->assertStringContainsString('rel="preload"', $html);
        $this->assertStringContainsString('href="https://example.com/font.woff2"', $html);
        $this->assertStringContainsString('as="font"', $html);
        $this->assertStringContainsString('type="font/woff2"', $html);
    }

    public function testExtractFontFilesFromCss(): void
    {
        $css = '@font-face { src: url("font.woff2") format("woff2"); }';

        $files = $this->optimizer->extractFontFilesFromCss($css);

        $this->assertCount(1, $files);
        $this->assertSame('font.woff2', $files[0]['url']);
        $this->assertSame('font', $files[0]['as']);
        $this->assertSame('font/woff2', $files[0]['type']);
    }
}
