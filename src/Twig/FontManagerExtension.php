<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FontManagerExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('font_manager', [FontManagerRuntime::class, 'renderFonts'], [
                'is_safe' => ['html'],
                'needs_runtime' => true,
            ]),
        ];
    }
}
