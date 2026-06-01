<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\ConfigurationException;
use Symfinity\FontManager\Exception\ValidationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Local fonts provider for self-hosted custom fonts.
 */
final class LocalFontsProvider extends AbstractProvider
{
    protected const FEATURES = [
        'search' => true,
        'metadata' => true,
        'variable_fonts' => false,
        'cdn' => false,
    ];

    private readonly Filesystem $filesystem;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        HttpClientInterface $httpClient,
        array $config = []
    ) {
        parent::__construct($httpClient, $config);
        $this->filesystem = new Filesystem();
    }

    public function getName(): string
    {
        return 'local';
    }

    public function searchFonts(string $query, int $maxResults = 20): array
    {
        /** @var array<array-key, mixed> $fonts */
        $fonts = $this->config['fonts'] ?? [];
        $results = [];

        foreach ($fonts as $fontKey => $fontConfig) {
            if (!is_string($fontKey)) {
                continue;
            }
            if (!is_array($fontConfig)) {
                continue;
            }
            $displayName = $fontConfig['display_name'] ?? $fontKey;
            $displayNameStr = is_string($displayName) ? $displayName : $fontKey;

            if ('' === $query || false !== stripos($displayNameStr, $query) || false !== stripos($fontKey, $query)) {
                $variants = $this->buildVariantList($fontConfig);

                $category = $fontConfig['category'] ?? 'unknown';
                $results[] = [
                    'family' => $fontKey,
                    'category' => is_string($category) ? $category : 'unknown',
                    'variants' => $variants,
                ];

                if (count($results) >= $maxResults) {
                    break;
                }
            }
        }

        return $results;
    }

    public function getFontMetadata(string $fontName): ?array
    {
        /** @var array<string, mixed> $fonts */
        $fonts = $this->config['fonts'] ?? [];

        if (!isset($fonts[$fontName])) {
            return null;
        }

        $fontConfig = $fonts[$fontName];
        if (!is_array($fontConfig)) {
            return null;
        }

        $displayName = $fontConfig['display_name'] ?? $fontName;
        $category = $fontConfig['category'] ?? 'unknown';
        $unicodeRange = $fontConfig['unicode_range'] ?? null;

        return [
            'family' => $fontName,
            'display_name' => is_string($displayName) ? $displayName : $fontName,
            'category' => is_string($category) ? $category : 'unknown',
            'variants' => $this->buildVariantList($fontConfig),
            'weights' => $fontConfig['weights'] ?? [400],
            'styles' => $fontConfig['styles'] ?? ['normal'],
            'files' => $fontConfig['files'] ?? [],
            'unicode_range' => is_string($unicodeRange) ? $unicodeRange : null,
            'provider' => 'local',
        ];
    }

    public function getFontVariants(string $fontName): array
    {
        $metadata = $this->getFontMetadata($fontName);

        if (null === $metadata || [] === $metadata) {
            return ['weights' => [400], 'styles' => ['normal']];
        }

        $weights = $metadata['weights'] ?? [400];
        $styles = $metadata['styles'] ?? ['normal'];

        return [
            'weights' => is_array($weights) ? array_map('intval', $weights) : [400],
            'styles' => is_array($styles) ? array_map('strval', $styles) : ['normal'],
        ];
    }

    public function downloadFontCss(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        /** @var array<string, mixed> $fonts */
        $fonts = $this->config['fonts'] ?? [];

        if (!isset($fonts[$fontName])) {
            throw new ConfigurationException(sprintf("Local font '%s' not found in configuration. " . 'Add it to config/packages/font_manager.yaml under providers.local.fonts', $fontName));
        }

        $fontConfig = $fonts[$fontName];
        if (!is_array($fontConfig)) {
            throw new ConfigurationException(sprintf("Invalid configuration for font '%s'", $fontName));
        }

        $directory = $this->config['directory'] ?? '';
        $directoryStr = is_string($directory) ? $directory : '';
        $css = '';

        foreach ($weights as $weight) {
            foreach ($styles as $style) {
                $key = "{$weight}-{$style}";

                $files = $fontConfig['files'] ?? [];
                if (!is_array($files) || !isset($files[$key])) {
                    continue; // Skip unavailable variants
                }

                $filename = $files[$key];
                if (!is_string($filename)) {
                    continue;
                }

                $filepath = $directoryStr . '/' . $filename;

                // Check if file exists
                if (!$this->filesystem->exists($filepath)) {
                    throw new ValidationException(sprintf("Font file not found: %s\n" . 'Make sure the file exists in: %s', $filepath, $directoryStr));
                }

                // Determine format from extension
                $format = $this->getFormatFromFilename($filename);

                // Generate @font-face declaration
                $css .= "@font-face {\n";
                $css .= "  font-family: '{$fontName}';\n";
                $css .= "  font-style: {$style};\n";
                $css .= "  font-weight: {$weight};\n";
                $css .= "  font-display: {$display->value};\n";
                $css .= sprintf("  src: url('/assets/fonts/custom/%s') format('%s');\n", $filename, $format);

                $unicodeRange = $fontConfig['unicode_range'] ?? null;
                if (is_string($unicodeRange)) {
                    $css .= "  unicode-range: {$unicodeRange};\n";
                }

                $css .= "}\n\n";
            }
        }

        return $css;
    }

    /**
     * Validate that all configured font files exist.
     *
     * @return array<int, array{font: string, variant: string, file: string, path: string, error: string}>
     */
    public function validateFonts(): array
    {
        /** @var array<array-key, mixed> $fonts */
        $fonts = $this->config['fonts'] ?? [];
        $directory = $this->config['directory'] ?? '';
        $directoryStr = is_string($directory) ? $directory : '';
        $errors = [];

        foreach ($fonts as $fontKey => $fontConfig) {
            if (!is_string($fontKey)) {
                continue;
            }
            if (!is_array($fontConfig)) {
                continue;
            }
            $files = $fontConfig['files'] ?? [];
            if (!is_array($files)) {
                continue;
            }

            foreach ($files as $variant => $filename) {
                if (!is_string($variant)) {
                    continue;
                }
                if (!is_string($filename)) {
                    continue;
                }
                $filepath = $directoryStr . '/' . $filename;

                if (!$this->filesystem->exists($filepath)) {
                    $errors[] = [
                        'font' => $fontKey,
                        'variant' => $variant,
                        'file' => $filename,
                        'path' => $filepath,
                        'error' => 'File not found',
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Build variant list from font configuration.
     *
     * @param array<string, mixed> $fontConfig
     *
     * @return array<string>
     */
    private function buildVariantList(array $fontConfig): array
    {
        $weightsRaw = $fontConfig['weights'] ?? [400];
        $stylesRaw = $fontConfig['styles'] ?? ['normal'];

        $weights = is_array($weightsRaw) ? $weightsRaw : [400];
        $styles = is_array($stylesRaw) ? $stylesRaw : ['normal'];
        $variants = [];

        foreach ($weights as $weight) {
            foreach ($styles as $style) {
                $weightStr = is_int($weight) || is_string($weight) ? (string) $weight : '400';
                $styleStr = is_string($style) ? $style : 'normal';
                $variants[] = 'normal' === $styleStr ? $weightStr : "{$weightStr}{$styleStr}";
            }
        }

        return $variants;
    }

    /**
     * Get font format from filename extension.
     */
    private function getFormatFromFilename(string $filename): string
    {
        return match (true) {
            str_ends_with($filename, '.woff2') => 'woff2',
            str_ends_with($filename, '.woff') => 'woff',
            str_ends_with($filename, '.ttf') => 'truetype',
            str_ends_with($filename, '.otf') => 'opentype',
            str_ends_with($filename, '.eot') => 'embedded-opentype',
            default => 'woff2',
        };
    }

    public function renderCdnLinks(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP
    ): string {
        // Local fonts don't use CDN - generate inline CSS
        $css = $this->downloadFontCss($fontName, $weights, $styles, $display);

        return sprintf('<style>%s</style>', $css);
    }
}
