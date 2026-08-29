<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Css;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class CssLayerExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'css_layer';
    }

    public function getLabel(): string
    {
        return 'CSS @layer Integration';
    }

    public function getFileExtension(): string
    {
        return '.css';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-layer';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('css');

        // CSS Layers for cascade control
        $output .= "@layer design-tokens {\n";
        $output .= "  :root {\n";

        // Font families
        $output .= "    /* Font Families */\n";
        foreach ($fonts->all() as $font) {
            $varName = '--font-family-' . $font->getSanitizedName();
            $output .= sprintf("    %s: %s;\n", $varName, $font->getCssValue());
        }

        // Semantic aliases
        if ($fonts->hasSemantic('sans') || $fonts->hasSemantic('serif') || $fonts->hasSemantic('mono')) {
            $output .= "\n    /* Semantic Aliases */\n";

            $sansFont = $fonts->getSemantic('sans');
            if (null !== $sansFont) {
                $output .= sprintf(
                    "    --font-family-sans: var(--font-family-%s);\n",
                    $sansFont->getSanitizedName()
                );
            }

            $serifFont = $fonts->getSemantic('serif');
            if (null !== $serifFont) {
                $output .= sprintf(
                    "    --font-family-serif: var(--font-family-%s);\n",
                    $serifFont->getSanitizedName()
                );
            }

            $monoFont = $fonts->getSemantic('mono');
            if (null !== $monoFont) {
                $output .= sprintf(
                    "    --font-family-mono: var(--font-family-%s);\n",
                    $monoFont->getSanitizedName()
                );
            }
        }

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "\n    /* Font Weights */\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("    --font-weight-%s: %d;\n", $name, $weight);
            }
        }

        $output .= "  }\n";
        $output .= "}\n\n";

        // Base layer for default application
        $output .= "@layer base {\n";

        if ($fonts->hasSemantic('sans')) {
            $output .= "  body {\n";
            $output .= "    font-family: var(--font-family-sans);\n";
            $output .= "    font-weight: var(--font-weight-normal);\n";
            $output .= "  }\n\n";

            $output .= "  h1, h2, h3, h4, h5, h6 {\n";
            $output .= "    font-family: var(--font-family-sans);\n";
            $output .= "  }\n";
        }

        if ($fonts->hasSemantic('mono')) {
            $output .= "\n  code, pre, kbd, samp {\n";
            $output .= "    font-family: var(--font-family-mono);\n";
            $output .= "  }\n";
        }

        $output .= "}\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in your CSS:
  @import './fonts-layer.css';

Cascade Layers (CSS Level 5):
  @layer design-tokens → Font variables
  @layer base → Default application

Override in your CSS:
  @layer overrides {
    body { font-family: custom; }
  }
INSTRUCTIONS;
    }
}
