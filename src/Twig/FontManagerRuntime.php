<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Twig;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Provider\FontProviderInterface;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Service\Fallback\FontFallbackGenerator;
use Symfinity\FontManager\Service\FontVariantHelper;
use Symfinity\FontManager\Service\Performance\FontLoadingApi;
use Symfinity\FontManager\Service\Performance\FontPerformanceOptimizer;
use Symfinity\FontManager\Service\VariableFonts\VariableFontDetector;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;

final class FontManagerRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, mixed>|null */
    private static ?array $manifestCache = null;

    public function __construct(
        private readonly ProviderRegistry $providerRegistry,
        private readonly bool $useLockedFonts,
        private readonly ?string $manifestFile = null,
        private readonly Filesystem $filesystem = new Filesystem(),
        /** @phpstan-ignore-next-line - Prepared for future 0.3.0 features */
        private readonly ?FontPerformanceOptimizer $performanceOptimizer = null,
        private readonly ?FontFallbackGenerator $fallbackGenerator = null,
        private readonly ?VariableFontDetector $variableFontDetector = null,
        private readonly ?FontLoadingApi $fontLoadingApi = null,
        private readonly bool $enableResourceHints = true,
        private readonly bool $enableIntelligentFallbacks = true
    ) {
        // Properties $performanceOptimizer, $variableFontDetector, $fontLoadingApi, $enableResourceHints
        // are prepared for future 0.3.0 features (resource hints rendering, variable font detection, etc.)
        // and will be fully integrated in subsequent commits.
    }

    /**
     * Render fonts using specified provider or default.
     *
     * @param string                   $name      Font family name (e.g., "Ubuntu", "Roboto")
     * @param array<int|string>|string $weights   Font weights (e.g., "300 400 700" or [300, 400, 700])
     * @param array<string>|string     $styles    Font styles (e.g., "normal italic" or ["normal", "italic"])
     * @param bool                     $monospace Whether this is a monospace font (default: false)
     * @param string|null              $display   Font display value (default: "swap")
     * @param string|null              $provider  Provider name (google, bunny, fontsource, local) or null for default from config
     *
     * @return string HTML string with font links and styles
     */
    public function renderFonts(
        string $name,
        array|string $weights = ['400'],
        array|string $styles = ['normal'],
        bool $monospace = false,
        ?string $display = null,
        ?string $provider = null
    ): string {
        // Normalize weights and styles
        $normalizedWeights = FontVariantHelper::normalizeArray($weights);
        $normalizedStylesRaw = FontVariantHelper::normalizeArray($styles);
        /** @var array<string> $normalizedStyles */
        $normalizedStyles = array_map('strval', $normalizedStylesRaw);

        $displayEnum = is_string($display) ? FontDisplay::tryFrom($display) ?? FontDisplay::SWAP : FontDisplay::SWAP;

        // Check if we should use locked fonts
        if ($this->useLockedFonts && $this->hasLockedFonts($name)) {
            return $this->renderLockedFonts($name);
        }

        // Get the provider (use default if not specified)
        $activeProvider = null !== $provider
            ? $this->providerRegistry->getProvider($provider)
            : $this->providerRegistry->getDefaultProvider();

        return $this->renderProviderFonts(
            $activeProvider,
            $name,
            $normalizedWeights,
            $normalizedStyles,
            $displayEnum,
            $monospace
        );
    }

    /**
     * Render fonts from provider CDN (development mode).
     *
     * @param array<int|string> $weights
     * @param array<string>     $styles
     */
    private function renderProviderFonts(
        FontProviderInterface $provider,
        string $name,
        array $weights,
        array $styles,
        FontDisplay $display,
        bool $monospace
    ): string {
        $fontVar = '--font-family-' . FontVariantHelper::sanitizeFontName($name);
        $defaultWeight = [] === $weights ? 400 : (int) reset($weights);
        $headingWeight = $this->findWeight($weights, 500, 700);
        $boldWeight = $this->findWeight($weights, 700, 700);

        $parts = [];

        // Let provider render its own CDN links (avoids hardcoding)
        $parts[] = $provider->renderCdnLinks($name, $weights, $styles, $display);

        // Inline CSS variables and styles
        $inlineStyles = $this->generateInlineStyles($fontVar, $name, $defaultWeight, $headingWeight, $boldWeight, $monospace);
        $parts[] = sprintf('<style>%s</style>', $inlineStyles);

        return implode("\n", $parts);
    }

    /**
     * Render locked fonts (production mode).
     */
    private function renderLockedFonts(string $name): string
    {
        $sanitizedName = FontVariantHelper::sanitizeFontName($name);
        $cssPath = '/assets/fonts/' . $sanitizedName . '.css';

        return sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Generate inline CSS styles.
     */
    private function generateInlineStyles(
        string $fontVar,
        string $fontName,
        int $defaultWeight,
        int $headingWeight,
        int $boldWeight,
        bool $monospace
    ): string {
        // Use intelligent fallbacks if enabled and available
        $fontFamily = null;
        if ($this->enableIntelligentFallbacks && null !== $this->fallbackGenerator) {
            // Create a temporary Font object for fallback generation
            $font = new Font(
                name: $fontName,
                weights: [],
                styles: [],
                monospace: $monospace,
                semantic: null,
                files: []
            );
            $fontFamily = $this->fallbackGenerator->generateFallbackCss($font);
        }

        // Fallback to simple fallback if intelligent fallbacks not available
        if (null === $fontFamily) {
            $fallbackFamily = $monospace ? 'monospace' : 'sans-serif';
            $fontFamily = sprintf("'%s', %s", $fontName, $fallbackFamily);
        }

        $css = sprintf(':root { %s: %s; }', $fontVar, $fontFamily);

        if ($monospace) {
            $css .= sprintf(' code, pre, kbd, samp { font-family: var(%s); font-weight: %d; }', $fontVar, $defaultWeight);
        } else {
            $css .= sprintf(' body { font-family: var(%s); font-weight: %d; }', $fontVar, $defaultWeight);
            $css .= sprintf(' h1, h2, h3, h4, h5, h6 { font-family: var(%s); font-weight: %d; }', $fontVar, $headingWeight);
            $css .= sprintf(' strong, b { font-weight: %d; }', $boldWeight);
        }

        return $css;
    }

    /**
     * Check if fonts are locked (manifest exists).
     */
    private function hasLockedFonts(string $name): bool
    {
        if (null === $this->manifestFile || '' === $this->manifestFile || !$this->filesystem->exists($this->manifestFile)) {
            return false;
        }

        // Load manifest if not cached
        if (null === self::$manifestCache) {
            $content = file_get_contents($this->manifestFile);
            if (false === $content) {
                return false;
            }
            $decoded = json_decode($content, true);
            self::$manifestCache = is_array($decoded) ? $decoded : [];
        }

        $fonts = self::$manifestCache['fonts'] ?? null;

        return is_array($fonts) && isset($fonts[$name]);
    }

    /**
     * Find appropriate weight from available weights.
     *
     * @param array<int|string> $weights
     */
    private function findWeight(array $weights, int $minWeight, int $fallback): int
    {
        foreach ($weights as $weight) {
            $w = (int) $weight;
            if ($w >= $minWeight) {
                return $w;
            }
        }

        return $fallback;
    }
}
