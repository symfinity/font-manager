<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service\Fallback;

use Symfinity\FontManager\Model\Font;

/**
 * Service for generating intelligent font fallback chains.
 */
final class FontFallbackGenerator
{
    /**
     * Generate fallback chain for a font.
     *
     * @return array<string> List of fallback fonts in order
     */
    public function generateFallbackChain(Font $font): array
    {
        $chain = [];

        // Add the primary font (quoted)
        $chain[] = "'" . $font->getName() . "'";

        // Add system font fallbacks based on category
        $systemFonts = $this->getSystemFontFallbacks($font->isMonospace());
        $chain = array_merge($chain, $systemFonts);

        // Add generic fallback
        $chain[] = $font->isMonospace() ? 'monospace' : 'sans-serif';

        return array_unique($chain);
    }

    /**
     * Generate CSS font-family string with fallbacks.
     */
    public function generateFallbackCss(Font $font): string
    {
        $chain = $this->generateFallbackChain($font);

        // Quote font names that need it
        $quoted = array_map(function (string $fontName) {
            // Don't quote system fonts or generic names
            if (in_array($fontName, ['serif', 'sans-serif', 'monospace', 'cursive', 'fantasy'], true)) {
                return $fontName;
            }

            // Don't quote if already quoted
            if (str_starts_with($fontName, '"') || str_starts_with($fontName, "'")) {
                return $fontName;
            }

            return "'" . $fontName . "'";
        }, $chain);

        return implode(', ', $quoted);
    }

    /**
     * Get system font fallbacks based on font characteristics.
     *
     * @return array<string> List of system fonts
     */
    private function getSystemFontFallbacks(bool $monospace): array
    {
        if ($monospace) {
            // Monospace system fonts (ordered by availability)
            return [
                'ui-monospace',
                'SFMono-Regular',
                'Menlo',
                'Monaco',
                'Consolas',
                '"Liberation Mono"',
                '"Courier New"',
                'monospace',
            ];
        }

        // Sans-serif system fonts (ordered by availability)
        return [
            '-apple-system',
            'BlinkMacSystemFont',
            '"Segoe UI"',
            'Roboto',
            '"Helvetica Neue"',
            'Arial',
            'sans-serif',
        ];
    }

    /**
     * Generate unicode-range specific fallback chains.
     *
     * @return array<string, array<string>> Map of unicode-range => fallback fonts
     */
    public function generateUnicodeRangeFallbacks(): array
    {
        return [
            'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD' => ['sans-serif'], // Latin
            'U+0100-024F, U+0259, U+1E00-1EFF, U+2020, U+20A0-20AB, U+20AD-20CF, U+2113, U+2C60-2C7F, U+A720-A7FF' => ['sans-serif'], // Latin Extended
            'U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116' => ['sans-serif'], // Cyrillic
            'U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F' => ['sans-serif'], // Cyrillic Extended
            'U+0370-03FF' => ['sans-serif'], // Greek
            'U+1F00-1FFF' => ['sans-serif'], // Greek Extended
        ];
    }
}
