<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Css;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class CssModulesExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'css_modules';
    }

    public function getLabel(): string
    {
        return 'CSS Modules Export';
    }

    public function getFileExtension(): string
    {
        return '.module.css';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts';
    }

    public function getDependencies(): array
    {
        return ['css_variables'];
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('css');

        // Import CSS variables first
        $output .= "@import './fonts-variables.css';\n\n";

        // Export for JavaScript consumption
        $output .= ":export {\n";

        // Font families
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $varName = '--font-family-' . $font->getSanitizedName();
            $output .= sprintf("  font%s: var(%s);\n", ucfirst($key), $varName);
        }

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("  fontWeight%s: var(--font-weight-%s);\n", ucfirst($name), $name);
            }
        }

        $output .= "}\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in JavaScript/TypeScript:
  import fonts from './fonts.module.css';

Usage:
  element.style.fontFamily = fonts.fontSans;
  element.style.fontWeight = fonts.fontWeightBold;
INSTRUCTIONS;
    }
}
