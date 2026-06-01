<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class JsonExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'json';
    }

    public function getLabel(): string
    {
        return 'Generic JSON';
    }

    public function getFileExtension(): string
    {
        return '.json';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $data = [
            'fonts' => [],
            'variables' => [
                'css' => [],
                'scss' => [],
            ],
            'metadata' => [
                'generated_at' => date('c'),
                'generator' => 'font-manager',
                'count' => $fonts->count(),
            ],
        ];

        // Fonts
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $data['fonts'][$key] = [
                'name' => $font->getName(),
                'family' => $font->getCssValue(),
                'weights' => $font->getWeights(),
                'styles' => $font->getStyles(),
                'monospace' => $font->isMonospace(),
                'semantic' => $font->getSemantic(),
                'files' => $font->getFiles(),
            ];
        }

        // CSS Variables
        foreach ($fonts->all() as $font) {
            $varName = '--font-family-' . $font->getSanitizedName();
            $data['variables']['css'][$varName] = $font->getCssValue();
        }

        // Semantic CSS variables
        $sansFont = $fonts->getSemantic('sans');
        if (null !== $sansFont) {
            $data['variables']['css']['--font-family-sans'] = sprintf(
                'var(--font-family-%s)',
                $sansFont->getSanitizedName()
            );
        }

        $serifFont = $fonts->getSemantic('serif');
        if (null !== $serifFont) {
            $data['variables']['css']['--font-family-serif'] = sprintf(
                'var(--font-family-%s)',
                $serifFont->getSanitizedName()
            );
        }

        $monoFont = $fonts->getSemantic('mono');
        if (null !== $monoFont) {
            $data['variables']['css']['--font-family-mono'] = sprintf(
                'var(--font-family-%s)',
                $monoFont->getSanitizedName()
            );
        }

        // Font weights
        foreach ($fonts->getUniqueWeights() as $weight) {
            $name = $this->getWeightName($weight);
            $data['variables']['css']['--font-weight-' . $name] = $weight;
        }

        // SCSS Variables
        foreach ($fonts->all() as $font) {
            $varName = '$font-family-' . $font->getSanitizedName();
            $data['variables']['scss'][$varName] = $font->getCssValue();
        }

        // Semantic SCSS variables
        $sansFont = $fonts->getSemantic('sans');
        if (null !== $sansFont) {
            $data['variables']['scss']['$font-family-base'] = $sansFont->getCssValue();
        }

        $monoFont = $fonts->getSemantic('mono');
        if (null !== $monoFont) {
            $data['variables']['scss']['$font-family-monospace'] = $monoFont->getCssValue();
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return false === $json ? '{}' : $json;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Generic JSON format for custom integrations.

Usage in Node.js:
  const fonts = require('./fonts.json');
  console.log(fonts.fonts.sans.family);

Usage in PHP:
  \$fonts = json_decode(file_get_contents('fonts.json'), true);
  echo \$fonts['fonts']['sans']['family'];
INSTRUCTIONS;
    }
}
