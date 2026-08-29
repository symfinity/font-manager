<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Exception\FontDownloadException;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FontDownloader
{
    /**
     * @param array<string> $unicodeSubsets
     */
    public function __construct(
        private readonly string $fontsDir,
        private readonly HttpClientInterface $httpClient,
        private readonly ProviderRegistry $providerRegistry,
        private readonly Filesystem $filesystem,
        private readonly array $unicodeSubsets = ['latin', 'latin-ext']
    ) {
    }

    public function getProviderRegistry(): ProviderRegistry
    {
        return $this->providerRegistry;
    }

    /**
     * Download and save a font.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     *
     * @return array{files: array<string, string>, css: string, cssPath: string, downloadedWeights: array<int>, provider: string} Font files and CSS content
     */
    public function downloadFont(
        string $fontName,
        array $weights,
        array $styles,
        FontDisplay $display = FontDisplay::SWAP,
        bool $monospace = false,
        ?string $providerName = null
    ): array {
        // Ensure fonts directory exists
        $this->filesystem->mkdir($this->fontsDir, 0755);

        $sanitizedName = FontVariantHelper::sanitizeFontName($fontName);

        // Get provider (use default if not specified)
        $provider = null !== $providerName
            ? $this->providerRegistry->getProvider($providerName)
            : $this->providerRegistry->getDefaultProvider();

        // Store the actual provider name used
        $usedProviderName = $provider->getName();

        try {
            // Download CSS from provider
            $css = $provider->downloadFontCss($fontName, $weights, $styles, $display);
        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            throw new FontDownloadException(sprintf('Failed to download CSS for font "%s": %s', $fontName, $e->getMessage()), 0, $e);
        }

        // Check if we got valid CSS
        if ('' === $css || '0' === $css) {
            throw new FontDownloadException(sprintf('Provider returned empty CSS for font "%s"', $fontName));
        }

        // Filter CSS to include only relevant subsets and formats
        $css = $this->filterFontCss($css);

        // Prepare weight and style mappings for file naming
        $weightsMap = array_map(fn ($w): int => (int) $w, $weights);
        $hasItalic = in_array('italic', $styles, true);

        // Extract font URLs from CSS and download files
        // Process CSS block by block to extract subset, weight, and style information
        $files = [];
        $downloadedWeights = []; // Track actually downloaded weights
        $downloadedStyles = []; // Track actually downloaded styles

        // Check if CSS contains proper @font-face blocks with comments
        $hasSubsetComments = preg_match('/\/\*[^*]*(latin|greek|cyrillic)[^*]*\*\/\s*@font-face/', $css);

        if ($hasSubsetComments) {
            // Modern approach: Split CSS into @font-face blocks to extract metadata
            $cssBlocks = preg_split('/(?=\/\*[^*]*\*\/\s*@font-face)/', $css, -1, PREG_SPLIT_NO_EMPTY);

            if (false === $cssBlocks) {
                throw new FontDownloadException('Failed to split CSS into blocks');
            }

            $processedBlocks = [];

            foreach ($cssBlocks as $block) {
                if (empty(trim($block))) {
                    continue;
                }

                // Extract subset from comment
                // Matches: /* latin */, /* latin-ext */, /* ubuntu-latin-400-normal */, etc.
                $subset = null;
                if (preg_match('/\/\*[^*]*\b(latin-ext|latin|greek-ext|greek|cyrillic-ext|cyrillic)\b[^*]*\*\//', $block, $subsetMatch)) {
                    $subset = $subsetMatch[1];
                }

                // Extract weight from @font-face CSS
                $weight = 400;
                if (preg_match('/font-weight:\s*(\d+)/', $block, $weightMatch)) {
                    $weight = (int) $weightMatch[1];
                }

                // Extract style from @font-face CSS
                $style = 'normal';
                if (preg_match('/font-style:\s*(italic|oblique)/', $block, $styleMatch)) {
                    $style = 'italic';
                }

                // Process URLs in this block
                $processedBlock = preg_replace_callback(
                    '/url\(([^)]+)\)/',
                    function (array $matches) use (&$files, &$downloadedWeights, &$downloadedStyles, $sanitizedName, $monospace, $subset, $weight, $style): string {
                        $url = trim($matches[1], '\'"');

                        // Skip data URLs
                        if (str_starts_with($url, 'data:')) {
                            return 'url(' . $url . ')';
                        }

                        try {
                            // Download font file
                            $response = $this->httpClient->request('GET', $url);
                            $content = $response->getContent();
                        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
                            throw new FontDownloadException(sprintf('Failed to download font file "%s": %s', $url, $e->getMessage()), 0, $e);
                        }

                        // Determine file extension
                        $extension = '.woff2'; // Default to woff2
                        if (preg_match('/\.(woff2?|ttf|eot|otf)$/i', $url, $extMatch)) {
                            $extension = strtolower($extMatch[0]);
                        }

                        // Generate descriptive filename with subset
                        // Format: ubuntu-300-latin.woff2, ubuntu-mono-400-latin-ext.woff2
                        $parts = [$sanitizedName, (string) $weight];

                        if ($subset) {
                            $parts[] = $subset;
                        }

                        if ('italic' === $style) {
                            $parts[] = 'italic';
                        }

                        // Don't add "mono" suffix if font name already ends with "-mono"
                        if ($monospace && !str_ends_with($sanitizedName, '-mono')) {
                            $parts[] = 'mono';
                        }

                        $filename = implode('-', $parts) . $extension;

                        // Only download if not already downloaded (avoid duplicates)
                        if (!isset($files[$filename])) {
                            $filePath = $this->fontsDir . '/' . $filename;
                            $this->filesystem->dumpFile($filePath, $content);
                            $files[$filename] = $filePath;
                        }

                        // Track the actual weight and style that were downloaded
                        if (!in_array($weight, $downloadedWeights, true)) {
                            $downloadedWeights[] = $weight;
                        }
                        if (!in_array($style, $downloadedStyles, true)) {
                            $downloadedStyles[] = $style;
                        }

                        // Update CSS to use relative path
                        return sprintf('url("./%s")', $filename);
                    },
                    $block
                );

                if (is_string($processedBlock)) {
                    $processedBlocks[] = $processedBlock;
                }
            }

            $processedCss = implode("\n", $processedBlocks);
        } else {
            // Fallback: Legacy approach for CSS without subset comments (tests, simple CSS, local fonts)
            $weightIndex = 0;
            $result = preg_replace_callback(
                '/url\(([^)]+)\)/',
                function (array $matches) use (&$files, &$downloadedWeights, &$downloadedStyles, &$weightIndex, $sanitizedName, $weightsMap, $hasItalic, $monospace): string {
                    $url = trim($matches[1], '\'"');

                    // Skip data URLs
                    if (str_starts_with($url, 'data:')) {
                        return 'url(' . $url . ')';
                    }

                    try {
                        // Download font file
                        $response = $this->httpClient->request('GET', $url);
                        $content = $response->getContent();
                    } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
                        throw new FontDownloadException(sprintf('Failed to download font file "%s": %s', $url, $e->getMessage()), 0, $e);
                    }

                    // Determine file extension
                    $extension = '.woff2';
                    if (preg_match('/\.(woff2?|ttf|eot|otf)$/i', $url, $extMatch)) {
                        $extension = strtolower($extMatch[0]);
                    }

                    // Determine weight and style from URL or use from array
                    $weight = $weightsMap[$weightIndex % count($weightsMap)] ?? 400;
                    $isItalic = $hasItalic && (1 === $weightIndex % 2);

                    // Generate filename (no subset info available)
                    $parts = [$sanitizedName, (string) $weight];
                    if ($isItalic) {
                        $parts[] = 'italic';
                    }
                    // Don't add "mono" suffix if font name already ends with "-mono"
                    if ($monospace && !str_ends_with($sanitizedName, '-mono')) {
                        $parts[] = 'mono';
                    }
                    $baseFilename = implode('-', $parts) . $extension;

                    // Handle duplicate filenames by adding a counter
                    $filename = $baseFilename;
                    $counter = 1;
                    while (isset($files[$filename])) {
                        $filename = implode('-', $parts) . '-' . $counter . $extension;
                        ++$counter;
                    }

                    $filePath = $this->fontsDir . '/' . $filename;
                    $this->filesystem->dumpFile($filePath, $content);
                    $files[$filename] = $filePath;

                    // Track the actual weight and style
                    if (!in_array($weight, $downloadedWeights, true)) {
                        $downloadedWeights[] = $weight;
                    }
                    $style = $isItalic ? 'italic' : 'normal';
                    if (!in_array($style, $downloadedStyles, true)) {
                        $downloadedStyles[] = $style;
                    }

                    ++$weightIndex;

                    // Update CSS to use relative path
                    return sprintf('url("./%s")', $filename);
                },
                $css
            );

            if (!is_string($result)) {
                throw new FontDownloadException('Failed to process CSS file URLs');
            }

            $processedCss = $result;
        }

        if ('' === $processedCss) {
            throw new FontDownloadException('Failed to process CSS file URLs - no content generated');
        }

        // Generate intelligent CSS rules (use actually downloaded styles)
        $stylesheetCss = $this->generateStylesheetCss($fontName, $weights, $downloadedStyles, $monospace);

        // Combine @font-face declarations and intelligent styles
        $combinedCss = $processedCss . "\n\n" . $stylesheetCss;

        // Save combined CSS file
        $cssPath = $this->fontsDir . '/' . $sanitizedName . '.css';
        $this->filesystem->dumpFile($cssPath, $combinedCss);

        // Sort downloaded weights
        sort($downloadedWeights);

        return [
            'files' => $files,
            'css' => $combinedCss,
            'cssPath' => $cssPath,
            'downloadedWeights' => $downloadedWeights,
            'provider' => $usedProviderName,
        ];
    }

    /**
     * Generate intelligent CSS rules for the font.
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    private function generateStylesheetCss(
        string $fontName,
        array $weights,
        array $styles,
        bool $monospace = false
    ): string {
        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($fontName);
        $fallbackFamily = $monospace ? 'monospace' : 'sans-serif';
        $fontFamily = sprintf("'%s', %s", $fontName, $fallbackFamily);

        // Determine weights
        $defaultWeight = [] === $weights ? 400 : (int) reset($weights);

        $lines = [
            ':root {',
            "  {$fontVar}: {$fontFamily};",
            '}',
            '',
        ];

        if ($monospace) {
            // Apply to code elements
            $lines = array_merge($lines, [
                'code, pre, kbd, samp, var, tt {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$defaultWeight};",
                '}',
            ]);
        } else {
            // Find heading weight (first weight > 500, or 700)
            $headingWeight = 700;
            foreach ($weights as $weight) {
                $w = (int) $weight;
                if ($w > 500) {
                    $headingWeight = $w;

                    break;
                }
            }

            // Find bold weight (first weight >= 700, or 700)
            $boldWeight = 700;
            foreach ($weights as $weight) {
                $w = (int) $weight;
                if ($w >= 700) {
                    $boldWeight = $w;

                    break;
                }
            }

            // Apply to body and headings
            $lines = array_merge($lines, [
                'body {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$defaultWeight};",
                '}',
                '',
                'h1, h2, h3, h4, h5, h6 {',
                "  font-family: var({$fontVar});",
                "  font-weight: {$headingWeight};",
                '}',
                '',
                'strong, b {',
                "  font-weight: {$boldWeight};",
                '}',
            ]);
        }

        // Add italic support if italic style is included (only for non-monospace fonts)
        $hasItalic = in_array('italic', $styles, true);
        if ($hasItalic && !$monospace) {
            $lines = array_merge($lines, [
                '',
                'em, i, cite, dfn, var {',
                "  font-family: var({$fontVar});",
                '  font-style: italic;',
                '}',
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Filter CSS to include only relevant font subsets and formats.
     *
     * Keeps only:
     * - latin and latin-ext unicode ranges (most common use case)
     * - woff2 format (modern browsers, best compression)
     *
     * This reduces file count from ~48 to ~8 for typical fonts.
     * Works with Bunny/Google Fonts and Fontsource CSS comment styles.
     */
    private function filterFontCss(string $css): string
    {
        // Check if CSS contains unicode-range subset comments
        // Pattern 1: /* latin */ (Bunny Fonts, Google Fonts)
        // Pattern 2: /* ubuntu-latin-400-normal */ (Fontsource)
        if (!preg_match('/\/\*[^*]*(latin|greek|cyrillic)[^*]*\*\//', $css)) {
            // No subset comments found - return CSS as-is (e.g., local fonts without subsets)
            return $css;
        }

        // Split CSS into @font-face blocks
        $blocks = preg_split('/(?=\/\*[^*]*\*\/\s*@font-face)/', $css, -1, PREG_SPLIT_NO_EMPTY);

        if (false === $blocks) {
            // preg_split failed - return original CSS
            return $css;
        }

        $filteredBlocks = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // Check if this block is for one of the configured unicode subsets
            $hasAllowedSubset = false;
            foreach ($this->unicodeSubsets as $allowedSubset) {
                // Matches both:
                // - /* latin */ or /* latin-ext */ (Bunny/Google)
                // - /* ubuntu-latin-400-normal */ or /* ubuntu-latin-ext-400-normal */ (Fontsource)
                if (preg_match('/\/\*[^*]*-' . preg_quote($allowedSubset, '/') . '[^*]*\*\//', $block)
                    || preg_match('/\/\*\s*' . preg_quote($allowedSubset, '/') . '\s*\*\//', $block)) {
                    $hasAllowedSubset = true;

                    break;
                }
            }

            if (!$hasAllowedSubset) {
                continue; // Skip subsets not in configured unicode_subsets
            }

            // Remove woff format, keep only woff2
            // Example: url(...woff2) format('woff2'), url(...woff) format('woff')
            // → Keep only: url(...woff2) format('woff2')
            $block = preg_replace(
                '/,\s*url\([^)]+\.woff\)\s*format\([\'"]woff[\'"]\)/i',
                '',
                $block
            );

            $filteredBlocks[] = $block;
        }

        return implode("\n\n", $filteredBlocks);
    }
}
