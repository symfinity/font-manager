<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class FigmaTokensExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'figma_tokens';
    }

    public function getLabel(): string
    {
        return 'Figma Tokens Studio';
    }

    public function getFileExtension(): string
    {
        return '.figma.json';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $tokens = [
            'global' => [
                'fontFamilies' => [],
                'fontWeights' => [],
            ],
        ];

        // Font families (Figma Tokens format)
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $tokens['global']['fontFamilies'][$key] = [
                'value' => $font->getName(),
                'type' => 'fontFamilies',
            ];
        }

        // Font weights (Figma naming)
        foreach ($fonts->getUniqueWeights() as $weight) {
            $figmaName = $this->getFigmaWeightName($weight);
            $tokens['global']['fontWeights'][$this->getWeightName($weight)] = [
                'value' => $figmaName,
                'type' => 'fontWeights',
            ];
        }

        $json = json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return false === $json ? '{}' : $json;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Figma Tokens Studio format (https://tokens.studio/)

Import in Figma:
  1. Install Figma Tokens plugin
  2. Settings → Import tokens
  3. Select fonts.figma.json

Sync with your design system tokens.
INSTRUCTIONS;
    }

    private function getFigmaWeightName(int $weight): string
    {
        return match ($weight) {
            100 => 'Thin',
            200 => 'Extra Light',
            300 => 'Light',
            400 => 'Regular',
            500 => 'Medium',
            600 => 'Semi Bold',
            700 => 'Bold',
            800 => 'Extra Bold',
            900 => 'Black',
            default => (string) $weight,
        };
    }
}
