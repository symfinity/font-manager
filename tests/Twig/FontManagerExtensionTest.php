<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Twig;

use Symfinity\FontManager\Twig\FontManagerExtension;
use Symfinity\FontManager\Twig\FontManagerRuntime;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class FontManagerExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $extension = new FontManagerExtension();

        $functions = $extension->getFunctions();

        self::assertIsArray($functions);
        self::assertNotEmpty($functions);
        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('font_manager', $functions[0]->getName());
    }

    public function testFunctionCallsRuntime(): void
    {
        $extension = new FontManagerExtension();

        $functions = $extension->getFunctions();
        $function = $functions[0];

        $callable = $function->getCallable();

        self::assertIsArray($callable);
        self::assertSame(FontManagerRuntime::class, $callable[0]);
        self::assertSame('renderFonts', $callable[1]);
    }

    public function testFunctionIsConfiguredCorrectly(): void
    {
        $extension = new FontManagerExtension();

        $functions = $extension->getFunctions();
        $function = $functions[0];

        // Verify the function has correct name and callable
        self::assertSame('font_manager', $function->getName());

        $callable = $function->getCallable();
        self::assertIsArray($callable);
        self::assertCount(2, $callable);
    }
}
