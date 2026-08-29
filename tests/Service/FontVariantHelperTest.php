<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Service\FontVariantHelper;
use PHPUnit\Framework\TestCase;

final class FontVariantHelperTest extends TestCase
{
    public function testGenerateVariantsNormalOnly(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 700], ['normal']);

        self::assertSame(['wght@400;700'], $variants);
    }

    public function testGenerateVariantsWithItalic(): void
    {
        $variants = FontVariantHelper::generateVariants([400, 700], ['normal', 'italic']);

        // Format: ital,wght@0,400;0,700;1,400;1,700 (all normals first, then all italics)
        self::assertSame(['ital,wght@0,400;0,700;1,400;1,700'], $variants);
    }

    public function testBuildFontFamily(): void
    {
        $family = FontVariantHelper::buildFontFamily('Roboto', [400, 700], ['normal']);

        self::assertSame('Roboto:wght@400;700', $family);
    }

    public function testBuildFontFamilyWithItalic(): void
    {
        $family = FontVariantHelper::buildFontFamily('Open Sans', [400, 700], ['normal', 'italic']);

        // Format: ital,wght@0,400;0,700;1,400;1,700 (all normals first, then all italics)
        self::assertSame('Open Sans:ital,wght@0,400;0,700;1,400;1,700', $family);
    }

    public function testSanitizeFontName(): void
    {
        self::assertSame('roboto', FontVariantHelper::sanitizeFontName('Roboto'));
        self::assertSame('open-sans', FontVariantHelper::sanitizeFontName('Open Sans'));
        self::assertSame('jetbrains-mono', FontVariantHelper::sanitizeFontName('JetBrains Mono'));
    }

    public function testNormalizeArrayFromString(): void
    {
        $result = FontVariantHelper::normalizeArray('400 700 900');

        self::assertSame(['400', '700', '900'], $result);
    }

    public function testNormalizeArrayFromArray(): void
    {
        $result = FontVariantHelper::normalizeArray([400, 700, 900]);

        self::assertSame(['400', '700', '900'], $result);
    }

    public function testNormalizeArrayTrimsSpaces(): void
    {
        $result = FontVariantHelper::normalizeArray('  400   700  ');

        self::assertCount(2, $result);
        self::assertContains('400', $result);
        self::assertContains('700', $result);
    }
}
