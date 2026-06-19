<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\ProviderException;
use Symfinity\FontManager\Service\FontVariantHelper;

/**
 * Bunny Fonts provider - Privacy-friendly, GDPR-compliant alternative to Google Fonts
 * Uses the same font catalog as Google Fonts but hosted on EU-based CDN with zero tracking.
 */
final class BunnyFontsProvider extends AbstractProvider
{
    private const CSS_API = 'https://fonts.bunny.net/css2';

    protected const FEATURES = [
        'search' => false,
        'metadata' => false,
        'variable_fonts' => true,
        'cdn' => true,
    ];

    public function getName(): string
    {
        return 'bunny';
    }

    public function searchFonts(string $query, int $maxResults = 20): array
    {
        throw new ProviderException('Search API is not available for Bunny Fonts provider. Bunny Fonts uses the same font catalog as Google Fonts. To search fonts, temporarily switch provider to "google" in config/packages/symfinity_font_manager.yaml, search for fonts, then switch back to "bunny" for production use.');
    }

    public function getFontMetadata(string $fontName): ?array
    {
        // Return basic metadata without API call
        return [
            'family' => $fontName,
            'provider' => 'bunny',
            'category' => 'Unknown',
            'variants' => ['regular'],
            'note' => 'Bunny Fonts uses the same catalog as Google Fonts. ' .
                     'For full metadata, use Google Fonts provider temporarily.',
        ];
    }

    public function getFontVariants(string $fontName): array
    {
        // Return default variants (user must specify what they need)
        return [
            'weights' => [400, 700],
            'styles' => ['normal', 'italic'],
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
        $parts[] = '<link rel="preconnect" href="https://fonts.bunny.net">';
        $parts[] = sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        return implode("\n", $parts);
    }
}
