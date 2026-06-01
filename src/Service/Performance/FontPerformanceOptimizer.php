<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service\Performance;

use Symfinity\FontManager\Provider\FontProviderInterface;

/**
 * Service for optimizing font loading performance with resource hints and preload.
 */
final class FontPerformanceOptimizer
{
    /**
     * Generate resource hints (preconnect, dns-prefetch) for font providers.
     *
     * @param array<FontProviderInterface> $providers Used font providers
     *
     * @return array<string, array<string>> Map of domain => resource hints
     */
    public function generateResourceHints(array $providers): array
    {
        $hints = [];

        foreach ($providers as $provider) {
            $domains = $this->getProviderDomains($provider);
            foreach ($domains as $domain) {
                if (!isset($hints[$domain])) {
                    $hints[$domain] = [];
                }
                $hints[$domain][] = 'preconnect';
                $hints[$domain][] = 'dns-prefetch';
            }
        }

        // Remove duplicates
        foreach ($hints as $domain => &$hintTypes) {
            $hintTypes = array_unique($hintTypes);
            $hintTypes = array_values($hintTypes);
        }

        return $hints;
    }

    /**
     * Render resource hints as HTML tags.
     *
     * @param array<string, array<string>> $hints Map of domain => resource hints
     *
     * @return string HTML string with resource hint tags
     */
    public function renderResourceHints(array $hints): string
    {
        $tags = [];
        $seen = []; // Track to avoid duplicates

        foreach ($hints as $domain => $hintTypes) {
            foreach ($hintTypes as $hintType) {
                $key = "{$hintType}:{$domain}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $crossorigin = 'preconnect' === $hintType ? ' crossorigin' : '';
                $tags[] = sprintf(
                    '<link rel="%s" href="%s"%s>',
                    htmlspecialchars($hintType, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'),
                    $crossorigin
                );
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Generate preload links for critical fonts.
     *
     * @param array<array{url: string, 'as': string, type: string, crossorigin?: bool}> $fonts Font files to preload
     *
     * @return string HTML string with preload tags
     */
    public function generatePreloadLinks(array $fonts): string
    {
        $tags = [];

        foreach ($fonts as $font) {
            $url = $font['url'];
            $as = $font['as'];
            $type = $font['type'];
            $crossorigin = ($font['crossorigin'] ?? true) ? ' crossorigin' : '';

            if ('' === $url) {
                continue;
            }

            $attributes = [
                'rel="preload"',
                'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"',
                'as="' . htmlspecialchars($as, ENT_QUOTES, 'UTF-8') . '"',
            ];

            if ('' !== $type) {
                $attributes[] = 'type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"';
            }

            if ($crossorigin) {
                $attributes[] = 'crossorigin';
            }

            $tags[] = '<link ' . implode(' ', $attributes) . '>';
        }

        return implode("\n", $tags);
    }

    /**
     * Extract font file URLs from CSS for preload generation.
     *
     * @param string $css Font CSS content
     *
     * @return array<int, array{url: string, 'as': string, type: string, crossorigin: bool}> Array of font file info (key 'as' is HTML preload attribute)
     */
    public function extractFontFilesFromCss(string $css): array
    {
        $files = [];
        $pattern = '/url\(["\']?([^"\']+\.woff2?)["\']?\)/i';

        $matchResult = preg_match_all($pattern, $css, $matches, PREG_SET_ORDER);
        if (false !== $matchResult && $matchResult > 0) {
            foreach ($matches as $match) {
                $url = $match[1];
                $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                $type = 'woff2' === $ext ? 'font/woff2' : ('woff' === $ext ? 'font/woff' : 'font/woff2');

                // Skip if already added
                $key = md5($url);
                if (isset($files[$key])) {
                    continue;
                }

                $files[$key] = [
                    'url' => $url,
                    'as' => 'font',
                    'type' => $type,
                    'crossorigin' => true,
                ];
            }
        }

        return array_values($files);
    }

    /**
     * Get domains for a font provider.
     *
     * @return array<string> List of domains
     */
    private function getProviderDomains(FontProviderInterface $provider): array
    {
        return match ($provider->getName()) {
            'google' => ['https://fonts.googleapis.com', 'https://fonts.gstatic.com'],
            'bunny' => ['https://fonts.bunny.net'],
            'fontsource' => ['https://cdn.jsdelivr.net'],
            default => [],
        };
    }
}
