<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class EsmExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'esm_javascript';
    }

    public function getLabel(): string
    {
        return 'ES Modules (JavaScript)';
    }

    public function getFileExtension(): string
    {
        return '.js';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('js');

        // Font families
        $output .= "export const fontFamilies = {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $output .= sprintf("  %s: %s,\n", $key, json_encode($font->getCssValue()));
        }
        $output .= "};\n\n";

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "export const fontWeights = {\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("  %s: %d,\n", $name, $weight);
            }
            $output .= "};\n\n";
        }

        // Detailed fonts object
        $output .= "export const fonts = {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $output .= sprintf("  %s: {\n", $key);
            $output .= sprintf("    name: %s,\n", json_encode($font->getName()));
            $output .= sprintf("    family: %s,\n", json_encode($font->getCssValue()));
            $output .= sprintf("    weights: %s,\n", json_encode($font->getWeights()));
            $output .= sprintf("    styles: %s,\n", json_encode($font->getStyles()));
            $output .= sprintf("    monospace: %s,\n", $font->isMonospace() ? 'true' : 'false');

            if (null !== $font->getSemantic()) {
                $output .= sprintf("    semantic: %s,\n", json_encode($font->getSemantic()));
            }

            $output .= "  },\n";
        }
        $output .= "};\n\n";

        // Helper functions
        $output .= "export function getFont(family) {\n";
        $output .= "  return fonts[family] || null;\n";
        $output .= "}\n\n";

        $output .= "export function getFontFamily(family) {\n";
        $output .= "  const font = getFont(family);\n";
        $output .= "  return font ? font.family : null;\n";
        $output .= "}\n\n";

        $output .= "export function getFontWeights(family) {\n";
        $output .= "  const font = getFont(family);\n";
        $output .= "  return font ? font.weights : [];\n";
        $output .= "}\n\n";

        // Default export
        $output .= "export default {\n";
        $output .= "  fontFamilies,\n";
        if ([] !== $weights) {
            $output .= "  fontWeights,\n";
        }
        $output .= "  fonts,\n";
        $output .= "  getFont,\n";
        $output .= "  getFontFamily,\n";
        $output .= "  getFontWeights,\n";
        $output .= "};\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in JavaScript:
  import { fonts, fontFamilies, fontWeights } from './fonts.js';
  
  // Or default import
  import fontConfig from './fonts.js';

Usage:
  element.style.fontFamily = fontFamilies.sans;
  element.style.fontWeight = fontWeights.bold;
  
  // Get font details
  const font = fonts.sans;
  console.log(font.weights); // [300, 400, 700]
INSTRUCTIONS;
    }
}
