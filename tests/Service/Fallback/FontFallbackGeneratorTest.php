<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service\Fallback;

use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Service\Fallback\FontFallbackGenerator;
use PHPUnit\Framework\TestCase;

final class FontFallbackGeneratorTest extends TestCase
{
    private FontFallbackGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new FontFallbackGenerator();
    }

    public function testGenerateFallbackChainForSansSerif(): void
    {
        $font = new Font(
            name: 'Roboto',
            weights: [400, 700],
            styles: ['normal'],
            monospace: false,
            semantic: null,
            files: []
        );

        $chain = $this->generator->generateFallbackChain($font);

        $this->assertContains("'Roboto'", $chain);
        $this->assertContains('-apple-system', $chain);
        $this->assertContains('sans-serif', $chain);
    }

    public function testGenerateFallbackChainForMonospace(): void
    {
        $font = new Font(
            name: 'JetBrains Mono',
            weights: [400],
            styles: ['normal'],
            monospace: true,
            semantic: null,
            files: []
        );

        $chain = $this->generator->generateFallbackChain($font);

        $this->assertContains("'JetBrains Mono'", $chain);
        $this->assertContains('monospace', $chain);
    }

    public function testGenerateFallbackCss(): void
    {
        $font = new Font(
            name: 'Ubuntu',
            weights: [400],
            styles: ['normal'],
            monospace: false,
            semantic: null,
            files: []
        );

        $css = $this->generator->generateFallbackCss($font);

        $this->assertStringStartsWith("'Ubuntu'", $css);
        $this->assertStringEndsWith('sans-serif', $css);
        $this->assertStringContainsString(', ', $css);
    }
}
