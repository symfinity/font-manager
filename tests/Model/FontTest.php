<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Model;

use Symfinity\FontManager\Model\Font;
use PHPUnit\Framework\TestCase;

final class FontTest extends TestCase
{
    public function testGetName(): void
    {
        $font = new Font('Ubuntu', [400], ['normal']);

        $this->assertSame('Ubuntu', $font->getName());
    }

    public function testGetSanitizedName(): void
    {
        $font = new Font('JetBrains Mono', [400], ['normal']);

        $this->assertSame('jetbrains-mono', $font->getSanitizedName());
    }

    public function testGetCssValue(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false);

        $this->assertSame("'Ubuntu', sans-serif", $font->getCssValue());
    }

    public function testGetCssValueMonospace(): void
    {
        $font = new Font('JetBrains Mono', [400], ['normal'], true);

        $this->assertSame("'JetBrains Mono', monospace", $font->getCssValue());
    }

    public function testGetDefaultWeight(): void
    {
        $font = new Font('Ubuntu', [300, 400, 700], ['normal']);

        $this->assertSame(300, $font->getDefaultWeight());
    }

    public function testGetHeadingWeight(): void
    {
        $font = new Font('Ubuntu', [300, 400, 600], ['normal']);

        $this->assertSame(600, $font->getHeadingWeight());
    }

    public function testGetHeadingWeightFallback(): void
    {
        $font = new Font('Ubuntu', [300, 400], ['normal']);

        $this->assertSame(700, $font->getHeadingWeight());
    }

    public function testGetBoldWeight(): void
    {
        $font = new Font('Ubuntu', [300, 400, 700], ['normal']);

        $this->assertSame(700, $font->getBoldWeight());
    }

    public function testHasItalic(): void
    {
        $fontWithItalic = new Font('Ubuntu', [400], ['normal', 'italic']);
        $fontWithoutItalic = new Font('Ubuntu', [400], ['normal']);

        $this->assertTrue($fontWithItalic->hasItalic());
        $this->assertFalse($fontWithoutItalic->hasItalic());
    }

    public function testGetSemantic(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');

        $this->assertSame('sans', $font->getSemantic());
    }
}
