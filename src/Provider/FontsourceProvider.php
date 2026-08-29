<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\ProviderException;

/**
 * Fontsource provider - Self-hosted Google Fonts via npm packages and CDN
 * Uses jsdelivr or unpkg CDN to access @fontsource packages without npm.
 */
final class FontsourceProvider extends AbstractProvider
{
    private const CDN_BASE = 'https://cdn.jsdelivr.net/npm';
    private const NPM_REGISTRY = 'https://registry.npmjs.org';

    protected const FEATURES = [
        'search' => true,
        'metadata' => true,
        'variable_fonts' => true,
        'cdn' => true,
    ];

    public function getName(): string
    {
        return 'fontsource';
    }

    public function searchFonts(string $query, int $maxResults = 20): array
    {
        // Search npm registry for @fontsource packages
        $response = $this->httpClient->request('GET', self::NPM_REGISTRY . '/-/v1/search', [
            'query' => [
                'text' => '@fontsource/' . $query,
                'size' => $maxResults,
            ],
        ]);

        $data = $response->toArray();
        $results = [];

        foreach ($data['objects'] ?? [] as $object) {
            $package = $object['package'] ?? [];
            $name = $package['name'] ?? '';

            // Extract font name from @fontsource/font-name
            if (str_starts_with((string) $name, '@fontsource/')) {
                $fontName = substr((string) $name, 12); // Remove '@fontsource/' prefix

                $results[] = [
                    'family' => $fontName,
                    'category' => 'unknown', // npm doesn't provide category
                    'variants' => ['regular'], // Simplified, would need to fetch package details
                ];
            }
        }

        return array_slice($results, 0, $maxResults);
    }

    public function getFontMetadata(string $fontName): ?array
    {
        // Fontsource uses lowercase-kebab-case for package names
        $normalizedFontName = $this->normalizeFontName($fontName);
        $packageName = '@fontsource/' . $normalizedFontName;

        try {
            $response = $this->httpClient->request('GET', self::NPM_REGISTRY . '/' . $packageName);
            $data = $response->toArray();

            return [
                'family' => $fontName,
                'provider' => 'fontsource',
                'category' => 'unknown',
                'variants' => ['regular'], // Simplified
                'version' => $data['dist-tags']['latest'] ?? 'unknown',
                'description' => $data['description'] ?? '',
                'license' => $data['license'] ?? 'unknown',
            ];
        } catch (\Exception) {
            return null;
        }
    }

    public function getFontVariants(string $fontName): array
    {
        // Return default variants (Fontsource has standardized weights)
        return [
            'weights' => [100, 200, 300, 400, 500, 600, 700, 800, 900],
            'styles' => ['normal', 'italic'],
        ];
    }

    public function downloadFontCss(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        // Fontsource uses lowercase-kebab-case for package names
        // Convert "Ubuntu Mono" -> "ubuntu-mono"
        $normalizedFontName = $this->normalizeFontName($fontName);
        $version = $this->getLatestVersion($normalizedFontName);
        $packageName = '@fontsource/' . $normalizedFontName;
        $css = '';

        // Download CSS for each weight (Fontsource has separate CSS per weight)
        foreach ($weights as $weight) {
            try {
                $url = sprintf(
                    '%s/%s@%s/%s.css',
                    self::CDN_BASE,
                    $packageName,
                    $version,
                    $weight
                );

                $response = $this->httpClient->request('GET', $url);
                $weightCss = $response->getContent();

                // Convert relative URLs to absolute CDN URLs
                // Fontsource uses relative paths like "./files/ubuntu-latin-400-normal.woff2"
                $baseUrl = sprintf('%s/%s@%s', self::CDN_BASE, $packageName, $version);
                $weightCss = $this->convertRelativeUrls($weightCss, $baseUrl);

                $css .= $weightCss . "\n";
            } catch (\Exception) {
                // Skip if weight not available
                continue;
            }
        }

        if ('' === $css || '0' === $css) {
            throw new ProviderException(sprintf('Failed to download CSS for font "%s" from Fontsource. Font may not be available or weights may not exist.', $fontName));
        }

        return $css;
    }

    public function renderCdnLinks(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        // Fontsource uses lowercase-kebab-case for package names
        $normalizedFontName = $this->normalizeFontName($fontName);
        $version = $this->getLatestVersion($normalizedFontName);
        $packageName = '@fontsource/' . $normalizedFontName;
        $parts = [];

        // Preconnect to jsdelivr
        $parts[] = '<link rel="preconnect" href="https://cdn.jsdelivr.net">';

        // Add stylesheet link for each weight
        foreach ($weights as $weight) {
            $url = sprintf(
                '%s/%s@%s/%s.css',
                self::CDN_BASE,
                $packageName,
                $version,
                $weight
            );
            $parts[] = sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
        }

        return implode("\n", $parts);
    }

    /**
     * Convert relative URLs in CSS to absolute CDN URLs.
     *
     * Fontsource uses relative paths like "./files/ubuntu-latin-400-normal.woff2"
     * which need to be converted to absolute URLs for downloading.
     */
    private function convertRelativeUrls(string $css, string $baseUrl): string
    {
        // Replace relative URLs: url(./files/...) or url("./files/...")
        $css = preg_replace_callback(
            '/url\([\'"]?\.\/([^\)\'\"]+)[\'"]?\)/i',
            function (array $matches) use ($baseUrl): string {
                $relativePath = $matches[1];
                $absoluteUrl = $baseUrl . '/' . $relativePath;

                return 'url(' . $absoluteUrl . ')';
            },
            $css
        );

        return $css ?? '';
    }

    /**
     * Normalize font name to Fontsource package name format.
     *
     * Fontsource uses lowercase-kebab-case for package names:
     * - "Ubuntu" -> "ubuntu"
     * - "Ubuntu Mono" -> "ubuntu-mono"
     * - "JetBrains Mono" -> "jetbrains-mono"
     */
    private function normalizeFontName(string $fontName): string
    {
        // Convert to lowercase
        $normalized = strtolower($fontName);

        // Replace spaces with hyphens
        $normalized = str_replace(' ', '-', $normalized);

        // Remove any non-alphanumeric characters except hyphens
        $normalized = preg_replace('/[^a-z0-9\-]/', '', $normalized) ?? $normalized;

        return $normalized;
    }

    /**
     * Get latest version of Fontsource package from npm registry.
     */
    private function getLatestVersion(string $fontName): string
    {
        $packageName = '@fontsource/' . $fontName;
        $cacheKey = 'fontsource_version_' . $fontName;

        // Check cache
        $cached = $this->getFromCache($cacheKey);
        if (null !== $cached && is_string($cached)) {
            return $cached;
        }

        try {
            $response = $this->httpClient->request('GET', self::NPM_REGISTRY . '/' . $packageName);
            $data = $response->toArray();
            $version = $data['dist-tags']['latest'] ?? 'latest';

            // Cache the version
            $this->putInCache($cacheKey, $version);

            return $version;
        } catch (\Exception) {
            // Fallback to 'latest' if can't fetch version
            return 'latest';
        }
    }
}
