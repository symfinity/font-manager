<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service\VariableFonts;

/**
 * Service for detecting and working with variable fonts.
 */
final class VariableFontDetector
{
    /**
     * Check if CSS contains variable font declarations.
     */
    public function hasVariableFonts(string $css): bool
    {
        // Variable fonts typically use wght and/or slnt axes
        return preg_match('/font-weight:\s*\d+\s+\d+/', $css) > 0
            || preg_match('/variations\s*:/', $css) > 0
            || preg_match('/woff2-variations/i', $css) > 0;
    }

    /**
     * Extract variable font axes from CSS.
     *
     * @return array<string, array{min: int|float, max: int|float, default?: int|float}> Map of axis name => range
     */
    public function extractVariableFontAxes(string $css): array
    {
        $axes = [];

        // Extract wght (weight) axis
        if (preg_match('/font-weight:\s*(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)/', $css, $matches)) {
            $axes['wght'] = [
                'min' => (float) $matches[1],
                'max' => (float) $matches[2],
            ];
        }

        // Extract slnt (slant) axis
        if (preg_match('/font-style:\s*oblique\s+(-?\d+(?:\.\d+)?)deg\s+(-?\d+(?:\.\d+)?)deg/', $css, $matches)) {
            $axes['slnt'] = [
                'min' => (float) $matches[1],
                'max' => (float) $matches[2],
            ];
        }

        return $axes;
    }

    /**
     * Check if a font supports variable fonts based on provider metadata.
     *
     * @param array<string, mixed>|null $metadata Font metadata
     */
    public function isVariableFontAvailable(?array $metadata): bool
    {
        if (null === $metadata) {
            return false;
        }

        // Check for variable font indicators in metadata
        $variants = $metadata['variants'] ?? [];
        if (!is_array($variants)) {
            return false;
        }

        // Look for variable font variants
        foreach ($variants as $variant) {
            if (is_string($variant) && str_contains($variant, 'variable')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate variable font CSS with weight range.
     *
     * @param array<int> $weights Requested weights
     *
     * @return string CSS weight range string (e.g., "wght@100;400;700" or "wght@100..900")
     */
    public function generateWeightRange(array $weights): string
    {
        if ([] === $weights) {
            return 'wght@400';
        }

        if (1 === count($weights)) {
            return 'wght@' . reset($weights);
        }

        // For multiple weights, use range notation if sequential
        $sorted = array_unique($weights);
        sort($sorted);

        $min = min($sorted);
        $max = max($sorted);

        // If weights span a range, use range notation
        if (count($sorted) > 2 && ($max - $min) === (count($sorted) - 1) * 100) {
            return sprintf('wght@%d..%d', $min, $max);
        }

        // Otherwise use semicolon-separated list
        return 'wght@' . implode(';', $sorted);
    }
}
