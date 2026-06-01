<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service\Performance;

/**
 * Service for Font Loading API integration.
 */
final class FontLoadingApi
{
    /**
     * Generate Font Loading API JavaScript code.
     *
     * @param array<string, array{family: string, url: string, descriptors?: array<string, mixed>}> $fonts Fonts to load
     *
     * @return string JavaScript code for Font Loading API
     */
    public function generateLoadingScript(array $fonts): string
    {
        if ([] === $fonts) {
            return '';
        }

        $script = '<script>';
        $script .= "if ('fonts' in document) {";

        foreach ($fonts as $font) {
            $family = $font['family'];
            $url = $font['url'];
            $descriptors = $font['descriptors'] ?? [];

            if ('' === $family || '' === $url) {
                continue;
            }

            $descJson = json_encode($descriptors, JSON_UNESCAPED_SLASHES);
            $script .= sprintf(
                "document.fonts.load('%s', '%s', %s).then(function() { document.documentElement.classList.add('fonts-loaded'); }).catch(function(e) { console.warn('Font loading failed:', e); });",
                $family,
                $url,
                $descJson
            );
        }

        $script .= '}';
        $script .= '</script>';

        return $script;
    }

    /**
     * Generate Font Loading API JavaScript with async loading.
     *
     * @param array<string, array{family: string, url: string, descriptors?: array<string, mixed>}> $fonts Fonts to load
     *
     * @return string JavaScript code for async Font Loading API
     */
    public function generateAsyncLoadingScript(array $fonts): string
    {
        if ([] === $fonts) {
            return '';
        }

        $fontsJson = json_encode($fonts, JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT);

        return <<<JS
<script>
if ('fonts' in document) {
  var fontsToLoad = {$fontsJson};
  Promise.all(fontsToLoad.map(function(font) {
    return document.fonts.load(font.family + ' ' + (font.descriptors.weight || '400'), font.url, font.descriptors);
  })).then(function() {
    document.documentElement.classList.add('fonts-loaded');
  }).catch(function(e) {
    console.warn('Font loading failed:', e);
  });
}
</script>
JS;
    }
}
