<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Scss;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class ScssVariablesExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'scss_variables';
    }

    public function getLabel(): string
    {
        return 'SCSS Variables';
    }

    public function getFileExtension(): string
    {
        return '.scss';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-variables';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('scss');

        // Font families
        $output .= "// Font Families\n";
        foreach ($fonts->all() as $font) {
            $varName = '$font-family-' . $font->getSanitizedName();
            $output .= sprintf("%s: %s !default;\n", $varName, $font->getCssValue());
        }

        // Semantic aliases
        if ($fonts->hasSemantic('sans') || $fonts->hasSemantic('serif') || $fonts->hasSemantic('mono')) {
            $output .= "\n// Semantic Aliases\n";

            $sansFont = $fonts->getSemantic('sans');
            if (null !== $sansFont) {
                $output .= sprintf(
                    "\$font-family-sans: \$font-family-%s !default;\n",
                    $sansFont->getSanitizedName()
                );
            }

            $serifFont = $fonts->getSemantic('serif');
            if (null !== $serifFont) {
                $output .= sprintf(
                    "\$font-family-serif: \$font-family-%s !default;\n",
                    $serifFont->getSanitizedName()
                );
            }

            $monoFont = $fonts->getSemantic('mono');
            if (null !== $monoFont) {
                $output .= sprintf(
                    "\$font-family-mono: \$font-family-%s !default;\n",
                    $monoFont->getSanitizedName()
                );
            }
        }

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "\n// Font Weights\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("\$font-weight-%s: %d !default;\n", $name, $weight);
            }
        }

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in your SCSS:
  @import './fonts-variables';

Usage:
  body {
    font-family: \$font-family-sans;
    font-weight: \$font-weight-normal;
  }
INSTRUCTIONS;
    }
}
