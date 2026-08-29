<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class DesignTokensExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'design_tokens';
    }

    public function getLabel(): string
    {
        return 'W3C Design Tokens';
    }

    public function getFileExtension(): string
    {
        return '.tokens.json';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $tokens = [
            'font' => [
                'family' => [],
                'weight' => [],
            ],
        ];

        // Font families (W3C Design Tokens format)
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $tokens['font']['family'][$key] = [
                '$value' => $font->getCssValue(),
                '$type' => 'fontFamily',
                '$description' => sprintf('%s font family', $font->getName()),
            ];
        }

        // Font weights
        foreach ($fonts->getUniqueWeights() as $weight) {
            $name = $this->getWeightName($weight);
            $tokens['font']['weight'][$name] = [
                '$value' => $weight,
                '$type' => 'fontWeight',
            ];
        }

        $json = json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return false === $json ? '{}' : $json;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
W3C Design Tokens format (https://design-tokens.github.io/community-group/format/)

Compatible with:
  - Style Dictionary (https://amzn.github.io/style-dictionary/)
  - Figma Tokens Studio
  - Design system tools

Process with Style Dictionary:
  npx style-dictionary build

Or import in your design tools.
INSTRUCTIONS;
    }
}
