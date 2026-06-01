<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Service\FontVariantHelper;

/**
 * Google Fonts provider.
 */
final class GoogleFontsProvider extends AbstractProvider
{
    private const API_BASE = 'https://www.googleapis.com/webfonts/v1/webfonts';
    private const CSS_API = 'https://fonts.googleapis.com/css2';

    protected const FEATURES = [
        'search' => true,
        'metadata' => true,
        'variable_fonts' => true,
        'cdn' => true,
    ];

    public function getName(): string
    {
        return 'google';
    }

    public function requiresAuth(): bool
    {
        return false; // API key optional (only for search)
    }

    protected function isAuthenticated(): bool
    {
        return isset($this->config['api_key']) && !empty($this->config['api_key']);
    }

    public function searchFonts(string $query, int $maxResults = 20): array
    {
        if (!$this->isAuthenticated()) {
            throw new ConfigurationException('Google Fonts API key is required for search. Get your free API key at https://console.cloud.google.com/apis/credentials and configure it in config/packages/font_manager.yaml under providers.google.api_key');
        }

        // Check cache for full fonts list
        $cacheKey = 'google_fonts_list';
        $cached = $this->getFromCache($cacheKey);
        if (null !== $cached && is_array($cached)) {
            $allFonts = $cached;
        } else {
            $response = $this->httpClient->request('GET', self::API_BASE, [
                'query' => [
                    'key' => $this->config['api_key'],
                    'sort' => 'popularity',
                ],
            ]);

            $data = $response->toArray();
            $allFonts = $data['items'] ?? [];

            // Cache the full result
            $this->putInCache($cacheKey, $allFonts);
        }

        // Apply filtering AFTER caching
        if ('' !== $query) {
            $queryLower = strtolower($query);
            $fonts = array_filter($allFonts, function (array $font) use ($queryLower): bool {
                $familyValue = $font['family'] ?? '';
                $family = is_string($familyValue) ? $familyValue : '';

                return false !== stripos($family, $queryLower);
            });
        } else {
            $fonts = $allFonts;
        }

        // Format results
        $results = [];
        foreach (array_slice($fonts, 0, $maxResults) as $font) {
            $results[] = [
                'family' => $font['family'] ?? '',
                'category' => $font['category'] ?? 'sans-serif',
                'variants' => $font['variants'] ?? ['regular'],
            ];
        }

        return $results;
    }

    public function getFontMetadata(string $fontName): ?array
    {
        if (!$this->isAuthenticated()) {
            // Return basic metadata without API call
            return [
                'family' => $fontName,
                'category' => 'unknown',
                'variants' => ['regular'],
                'provider' => 'google',
                'note' => 'API key required for full metadata',
            ];
        }

        $response = $this->httpClient->request('GET', self::API_BASE, [
            'query' => [
                'key' => $this->config['api_key'],
                'sort' => 'popularity',
            ],
        ]);

        $data = $response->toArray();
        $fonts = $data['items'] ?? [];

        foreach ($fonts as $font) {
            if ($font['family'] === $fontName) {
                return $font;
            }
        }

        return null;
    }

    public function getFontVariants(string $fontName): array
    {
        $metadata = $this->getFontMetadata($fontName);

        if ([] === $metadata) {
            return ['weights' => [400], 'styles' => ['normal']];
        }

        $variantsValue = $metadata['variants'] ?? null;
        $variants = is_array($variantsValue) ? $variantsValue : [];
        $weights = [];
        $styles = ['normal'];

        foreach ($variants as $variant) {
            if (!is_string($variant)) {
                continue;
            }
            // Parse variant like "300", "300italic", "regular", "italic", "700", "700italic"
            if (str_ends_with($variant, 'italic')) {
                $weightStr = substr($variant, 0, -6);
                $weight = '' !== $weightStr ? (int) $weightStr : 0;
                if (0 === $weight) {
                    $weight = 400;
                }
                $weights[] = $weight;
                if (!in_array('italic', $styles, true)) {
                    $styles[] = 'italic';
                }
            } else {
                $weight = '' !== $variant && 'regular' !== $variant && 'italic' !== $variant ? (int) $variant : 0;
                if (0 === $weight) {
                    $weight = 400; // "regular" variant
                }
                $weights[] = $weight;
            }
        }

        return [
            'weights' => array_values(array_unique($weights)),
            'styles' => array_values(array_unique($styles)),
        ];
    }

    public function downloadFontCss(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        $family = str_replace(' ', '+', $fontName);
        $variants = FontVariantHelper::generateVariants($weights, $styles);

        $url = sprintf(
            '%s?family=%s:%s&display=%s',
            self::CSS_API,
            $family,
            implode(';', $variants),
            $display->value
        );

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; Symfony FontManager)',
            ],
        ]);

        return $response->getContent();
    }

    public function renderCdnLinks(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        $family = str_replace(' ', '+', $fontName);
        $variants = FontVariantHelper::generateVariants($weights, $styles);

        $url = sprintf(
            '%s?family=%s:%s&display=%s',
            self::CSS_API,
            $family,
            implode(';', $variants),
            $display->value
        );

        $parts = [];
        $parts[] = '<link rel="preconnect" href="https://fonts.googleapis.com">';
        $parts[] = '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $parts[] = sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        return implode("\n", $parts);
    }
}
