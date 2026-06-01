<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Css;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class CssVariablesExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'css_variables';
    }

    public function getLabel(): string
    {
        return 'CSS Custom Properties';
    }

    public function getFileExtension(): string
    {
        return '.css';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-variables';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('css');

        // CSS Variables
        $output .= ":root {\n";

        // Font families
        $output .= "  /* Font Families */\n";
        foreach ($fonts->all() as $font) {
            $varName = '--font-family-' . $font->getSanitizedName();
            $output .= sprintf("  %s: %s;\n", $varName, $font->getCssValue());
        }

        // Semantic aliases
        if ($fonts->hasSemantic('sans') || $fonts->hasSemantic('serif') || $fonts->hasSemantic('mono')) {
            $output .= "\n  /* Semantic Aliases */\n";

            $sansFont = $fonts->getSemantic('sans');
            if (null !== $sansFont) {
                $output .= sprintf(
                    "  --font-family-sans: var(--font-family-%s);\n",
                    $sansFont->getSanitizedName()
                );
            }

            $serifFont = $fonts->getSemantic('serif');
            if (null !== $serifFont) {
                $output .= sprintf(
                    "  --font-family-serif: var(--font-family-%s);\n",
                    $serifFont->getSanitizedName()
                );
            }

            $monoFont = $fonts->getSemantic('mono');
            if (null !== $monoFont) {
                $output .= sprintf(
                    "  --font-family-mono: var(--font-family-%s);\n",
                    $monoFont->getSanitizedName()
                );
            }
        }

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "\n  /* Font Weights */\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("  --font-weight-%s: %d;\n", $name, $weight);
            }
        }

        $output .= "}\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in your CSS:
  @import './fonts-variables.css';

Usage:
  body {
    font-family: var(--font-family-sans);
    font-weight: var(--font-weight-normal);
  }
INSTRUCTIONS;
    }
}
