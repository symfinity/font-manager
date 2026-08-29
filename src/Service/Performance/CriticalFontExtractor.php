<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service\Performance;

/**
 * Service for extracting critical font CSS for above-the-fold content.
 */
final class CriticalFontExtractor
{
    /**
     * Extract critical font CSS (only for above-the-fold fonts).
     *
     * @param array<string, string> $fontCssMap Map of font name => CSS content
     * @param array<string>         $criticalFonts List of critical font names
     *
     * @return string Combined critical CSS
     */
    public function extractCriticalCss(array $fontCssMap, array $criticalFonts): string
    {
        if ([] === $criticalFonts) {
            return '';
        }

        $criticalCss = [];

        foreach ($criticalFonts as $fontName) {
            if (isset($fontCssMap[$fontName])) {
                $criticalCss[] = $this->minifyCss($fontCssMap[$fontName]);
            }
        }

        return implode("\n", $criticalCss);
    }

    /**
     * Extract @font-face rules from CSS.
     */
    public function extractFontFaceRules(string $css): string
    {
        // Extract all @font-face rules
        if (preg_match_all('/@font-face\s*\{[^}]+\}/i', $css, $matches)) {
            return implode("\n", $matches[0]);
        }

        return '';
    }

    /**
     * Minify CSS by removing unnecessary whitespace.
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*[^*]*\*+([^\/][^*]*\*+)*\//', '', $css);
        if (null === $css) {
            return '';
        }

        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        if (null === $css) {
            return '';
        }

        // Remove whitespace around specific characters
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        if (null === $css) {
            return '';
        }

        return trim($css);
    }
}
