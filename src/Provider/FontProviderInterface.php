<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;

/**
 * Interface for font providers (Google Fonts, Bunny Fonts, Local, etc.).
 */
interface FontProviderInterface
{
    /**
     * Get provider name (google, bunny, local, etc.).
     */
    public function getName(): string;

    /**
     * Check if provider requires authentication/API key.
     */
    public function requiresAuth(): bool;

    /**
     * Check if provider is authenticated and ready to use.
     */
    public function isReady(): bool;

    /**
     * Search fonts by name.
     *
     * @return array<int, array{family: string, category: string, variants: array<string>}>
     */
    public function searchFonts(string $query, int $maxResults = 20): array;

    /**
     * Download font CSS for given font family.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    public function downloadFontCss(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string;

    /**
     * Get font metadata.
     *
     * @return array<string, mixed>|null
     */
    public function getFontMetadata(string $fontName): ?array;

    /**
     * Check if provider supports a specific feature.
     */
    public function supports(ProviderFeature $feature): bool;

    /**
     * Get available font variants (weights and styles).
     *
     * @return array{weights: array<int>, styles: array<string>}
     */
    public function getFontVariants(string $fontName): array;

    /**
     * Render CDN stylesheet link tags (for development mode)
     * Should include preconnect hints and stylesheet link.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    public function renderCdnLinks(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string;
}
