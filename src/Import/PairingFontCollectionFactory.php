<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;

final class PairingFontCollectionFactory
{
    /**
     * @param array<string, array<string, mixed>> $fontsConfig
     * @param array{body?: string, heading?: string, mono?: string} $activeRoles
     * @param array<string, array<string, mixed>> $manifestFonts downloaded font manifest keyed by family or slug
     */
    public function fromConfig(array $fontsConfig, array $activeRoles, array $manifestFonts = []): FontCollection
    {
        $collection = new FontCollection();
        $semanticBySlug = [];

        foreach (['body' => 'sans', 'heading' => 'heading', 'mono' => 'mono'] as $role => $semantic) {
            $slug = $activeRoles[$role] ?? null;
            if (is_string($slug) && '' !== $slug) {
                $semanticBySlug[$slug] = $semantic;
            }
        }

        foreach ($fontsConfig as $slug => $fontConfig) {
            if (!is_array($fontConfig)) {
                continue;
            }

            $family = $fontConfig['family'] ?? $slug;
            if (!is_string($family)) {
                continue;
            }

            $weights = $fontConfig['weights'] ?? [400];
            if (!is_array($weights)) {
                $weights = [400];
            }

            $cssVariable = $fontConfig['css_variable'] ?? null;
            $cssVariable = is_string($cssVariable) ? $cssVariable : null;

            $category = $fontConfig['category'] ?? null;
            $category = is_string($category) ? $category : null;

            $manifestEntry = $manifestFonts[$family] ?? $manifestFonts[$slug] ?? null;
            $files = is_array($manifestEntry) && is_array($manifestEntry['files'] ?? null)
                ? $manifestEntry['files']
                : [];

            $collection->add(new Font(
                name: $family,
                weights: array_map('intval', $weights),
                styles: ['normal'],
                monospace: 'mono' === ($semanticBySlug[$slug] ?? null),
                semantic: $semanticBySlug[$slug] ?? null,
                files: $files,
                cssVariable: $cssVariable,
                category: $category,
            ));
        }

        return $collection;
    }

    public function fromImportResult(PairingImportResult $result): FontCollection
    {
        $fontsConfig = [];
        foreach ($result->getFonts() as $slug => $entry) {
            $fontsConfig[$slug] = $entry;
        }

        return $this->fromConfig($fontsConfig, $result->getRoles());
    }
}
