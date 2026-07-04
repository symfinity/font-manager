<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service;

/**
 * Collects sans/mono font CSS variables from font_manager() calls for ui-kernel token bridging.
 */
final class UiKernelFontBridgeRegistry
{
    private ?string $sansFontVar = null;

    private ?string $monoFontVar = null;

    public function register(string $fontCssVariable, bool $monospace): void
    {
        if ($monospace) {
            $this->monoFontVar = $fontCssVariable;

            return;
        }

        $this->sansFontVar = $fontCssVariable;
    }

    public function hasMappings(): bool
    {
        return null !== $this->sansFontVar || null !== $this->monoFontVar;
    }

    public function renderBridgeStylesheet(): string
    {
        if (!$this->hasMappings()) {
            return '';
        }

        $declarations = [];

        if (null !== $this->sansFontVar) {
            $declarations[] = sprintf('--ui-font-family-sans:var(%s)', $this->sansFontVar);
        }

        if (null !== $this->monoFontVar) {
            $declarations[] = sprintf('--ui-font-family-mono:var(%s)', $this->monoFontVar);
        }

        return sprintf(
            '<style id="font-manager-ui-kernel-bridge">@layer kernel.profile{[data-ui-root]{%s}}</style>',
            implode(';', $declarations)
        );
    }
}
