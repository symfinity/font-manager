<?php

declare(strict_types=1);

namespace Symfinity\FontManager;

use Symfinity\FontManager\DependencyInjection\FontManagerExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class FontManagerBundle extends Bundle
{
    /**
     * Org policy: config root is {@see symfinity_font_manager} (rule 22 triple alignment),
     * not Symfony's default underscored bundle name {@code font_manager}.
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new FontManagerExtension();
    }

    public function getContainerExtensionClass(): string
    {
        return FontManagerExtension::class;
    }
}
