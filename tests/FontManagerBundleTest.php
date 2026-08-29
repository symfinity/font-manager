<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests;

use Symfinity\FontManager\DependencyInjection\FontManagerExtension;
use Symfinity\FontManager\FontManagerBundle;
use PHPUnit\Framework\TestCase;

final class FontManagerBundleTest extends TestCase
{
    public function testGetContainerExtension(): void
    {
        $bundle = new FontManagerBundle();

        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(FontManagerExtension::class, $extension);
        self::assertSame('symfinity_font_manager', $extension->getAlias());
    }

    public function testGetPath(): void
    {
        $bundle = new FontManagerBundle();

        $path = $bundle->getPath();

        self::assertIsString($path);
        self::assertDirectoryExists($path);
    }
}
