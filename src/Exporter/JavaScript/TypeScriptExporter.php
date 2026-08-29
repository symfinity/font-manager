<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class TypeScriptExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'typescript_definitions';
    }

    public function getLabel(): string
    {
        return 'TypeScript Type Definitions';
    }

    public function getFileExtension(): string
    {
        return '.d.ts';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function getDependencies(): array
    {
        return ['esm_javascript'];
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('ts');

        // Font interface
        $output .= "export interface Font {\n";
        $output .= "  name: string;\n";
        $output .= "  family: string;\n";
        $output .= "  weights: number[];\n";
        $output .= "  styles: readonly ('normal' | 'italic')[];\n";
        $output .= "  monospace: boolean;\n";
        $output .= "  semantic?: 'sans' | 'serif' | 'mono';\n";
        $output .= "}\n\n";

        // Font families constant
        $output .= "export const fontFamilies: {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $output .= sprintf("  %s: string;\n", $key);
        }
        $output .= "};\n\n";

        // Font weights constant
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "export const fontWeights: {\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("  %s: number;\n", $name);
            }
            $output .= "};\n\n";
        }

        // Fonts object with literal types
        $output .= "export const fonts: {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $weightsStr = implode(' | ', $font->getWeights());
            $stylesStr = implode("' | '", $font->getStyles());

            $output .= sprintf("  %s: {\n", $key);
            $output .= sprintf("    name: '%s';\n", $font->getName());
            $output .= sprintf("    family: '%s';\n", addslashes($font->getCssValue()));
            $output .= sprintf("    weights: [%s];\n", implode(', ', $font->getWeights()));
            $output .= sprintf("    styles: readonly ['%s'];\n", $stylesStr);
            $output .= sprintf("    monospace: %s;\n", $font->isMonospace() ? 'true' : 'false');

            if (null !== $font->getSemantic()) {
                $output .= sprintf("    semantic: '%s';\n", $font->getSemantic());
            }

            $output .= "  };\n";
        }
        $output .= "};\n\n";

        // Type helpers
        $fontKeys = [];
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $fontKeys[] = "'{$key}'";
        }
        $fontKeysStr = implode(' | ', $fontKeys);

        $weightsStr = implode(' | ', $weights);

        $output .= sprintf("export type FontFamily = %s;\n", $fontKeysStr);
        $output .= sprintf("export type FontWeight = %s;\n\n", $weightsStr);

        // Function signatures
        $output .= "export function getFont(family: FontFamily): Font | null;\n";
        $output .= "export function getFontFamily(family: FontFamily): string | null;\n";
        $output .= "export function getFontWeights(family: FontFamily): number[];\n\n";

        // Default export
        $output .= "declare const _default: {\n";
        $output .= "  fontFamilies: typeof fontFamilies;\n";
        if ([] !== $weights) {
            $output .= "  fontWeights: typeof fontWeights;\n";
        }
        $output .= "  fonts: typeof fonts;\n";
        $output .= "  getFont: typeof getFont;\n";
        $output .= "  getFontFamily: typeof getFontFamily;\n";
        $output .= "  getFontWeights: typeof getFontWeights;\n";
        $output .= "};\n\n";
        $output .= "export default _default;\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in TypeScript:
  import { fonts, type FontFamily, type FontWeight } from './fonts';

Usage with type safety:
  function applyFont(element: HTMLElement, family: FontFamily) {
    element.style.fontFamily = fonts[family].family;
  }
  
  const font = fonts.sans;
  console.log(font.weights); // Type: number[]
  
  // Type-safe font family selection
  const myFont: FontFamily = 'sans'; // ✓ Valid
  const invalid: FontFamily = 'invalid'; // ✗ TypeScript error
INSTRUCTIONS;
    }
}
