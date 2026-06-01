<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Scss;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class ScssBootstrapExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'scss_bootstrap';
    }

    public function getLabel(): string
    {
        return 'SCSS Bootstrap Variables';
    }

    public function getFileExtension(): string
    {
        return '.scss';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-bootstrap';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('scss');

        $output .= "// Bootstrap Font Integration\n";
        $output .= "// Import this file BEFORE Bootstrap\n\n";

        // Font Families
        $output .= "// Font Families\n";
        foreach ($fonts->all() as $font) {
            $varName = '$font-family-' . $font->getSanitizedName();
            $output .= sprintf("%s: %s !default;\n", $varName, $font->getCssValue());
        }

        // Bootstrap-specific mappings
        $output .= "\n// Bootstrap Integration\n";

        $sansFont = $fonts->getSemantic('sans');
        if (null !== $sansFont) {
            $output .= sprintf(
                "\$font-family-base: \$font-family-%s !default;\n",
                $sansFont->getSanitizedName()
            );
        }

        $monoFont = $fonts->getSemantic('mono');
        if (null !== $monoFont) {
            $output .= sprintf(
                "\$font-family-monospace: \$font-family-%s !default;\n",
                $monoFont->getSanitizedName()
            );
        }

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "\n// Font Weights\n";

            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("\$font-weight-%s: %d !default;\n", $name, $weight);
            }

            // Map to Bootstrap weight variables
            if (in_array(300, $weights, true)) {
                $output .= "\n\$font-weight-lighter: \$font-weight-light !default;\n";
            }
            if (in_array(400, $weights, true)) {
                $output .= "\$font-weight-normal: \$font-weight-normal !default;\n";
            }
            if (in_array(700, $weights, true)) {
                $output .= "\$font-weight-bold: \$font-weight-bold !default;\n";
                $output .= "\$font-weight-bolder: \$font-weight-bold !default;\n";
            }
        }

        // Typography settings (if sans font available)
        $sansFont = $fonts->getSemantic('sans');
        if (null !== $sansFont) {
            $output .= "\n// Typography Settings\n";
            $output .= sprintf("\$headings-font-weight: %d !default;\n", $sansFont->getHeadingWeight());
            $output .= sprintf(
                "\$headings-font-family: \$font-family-%s !default;\n",
                $sansFont->getSanitizedName()
            );

            $output .= "\n// Line Heights\n";
            $output .= "\$line-height-base: 1.5 !default;\n";
            $output .= "\$line-height-sm: 1.25 !default;\n";
            $output .= "\$line-height-lg: 2 !default;\n";
        }

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in your SCSS BEFORE Bootstrap:
  @import './fonts-bootstrap';
  @import 'bootstrap/scss/bootstrap';

This will automatically apply fonts to:
  - Body text (\$font-family-base)
  - Headings (\$headings-font-family)
  - Code elements (\$font-family-monospace)
INSTRUCTIONS;
    }
}
