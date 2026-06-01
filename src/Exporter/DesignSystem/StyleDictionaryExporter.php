<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class StyleDictionaryExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'style_dictionary';
    }

    public function getLabel(): string
    {
        return 'Style Dictionary Format';
    }

    public function getFileExtension(): string
    {
        return '.js';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts.style-dict';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('js');

        $output .= "module.exports = {\n";
        $output .= "  font: {\n";

        // Font families
        $output .= "    family: {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $output .= sprintf("      %s: {\n", $key);
            $output .= sprintf("        value: %s,\n", json_encode($font->getCssValue()));
            $output .= sprintf("        comment: %s,\n", json_encode(sprintf('%s font family', $font->getName())));
            $output .= "      },\n";
        }
        $output .= "    },\n";

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "    weight: {\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("      %s: {\n", $name);
                $output .= sprintf("        value: %d,\n", $weight);
                $output .= sprintf("        comment: %s,\n", json_encode(sprintf('Font weight %d', $weight)));
                $output .= "      },\n";
            }
            $output .= "    },\n";
        }

        $output .= "  },\n";
        $output .= "};\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Style Dictionary format (https://amzn.github.io/style-dictionary/)

Create config/config.json:
  {
    "source": ["fonts.style-dict.js"],
    "platforms": {
      "css": {
        "transformGroup": "css",
        "buildPath": "build/css/",
        "files": [{
          "destination": "fonts.css",
          "format": "css/variables"
        }]
      },
      "scss": {
        "transformGroup": "scss",
        "buildPath": "build/scss/",
        "files": [{
          "destination": "_fonts.scss",
          "format": "scss/variables"
        }]
      }
    }
  }

Build:
  npx style-dictionary build
INSTRUCTIONS;
    }
}
