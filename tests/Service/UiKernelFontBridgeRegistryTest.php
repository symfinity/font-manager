<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Service\UiKernelFontBridgeRegistry;
use PHPUnit\Framework\TestCase;

final class UiKernelFontBridgeRegistryTest extends TestCase
{
    public function testRenderBridgeStylesheetMapsSansAndMono(): void
    {
        $registry = new UiKernelFontBridgeRegistry();
        $registry->register('--font-family-ubuntu', false);
        $registry->register('--font-family-ubuntu-mono', true);

        $html = $registry->renderBridgeStylesheet();

        self::assertStringContainsString('font-manager-ui-kernel-bridge', $html);
        self::assertStringContainsString('--ui-font-family-sans:var(--font-family-ubuntu)', $html);
        self::assertStringContainsString('--ui-font-family-mono:var(--font-family-ubuntu-mono)', $html);
        self::assertStringContainsString('[data-ui-root]', $html);
    }

    public function testRenderBridgeStylesheetReturnsEmptyWhenUnmapped(): void
    {
        self::assertSame('', (new UiKernelFontBridgeRegistry())->renderBridgeStylesheet());
    }
}
