<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class TailwindConfigExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'tailwind_config';
    }

    public function getLabel(): string
    {
        return 'Tailwind CSS Configuration';
    }

    public function getFileExtension(): string
    {
        return '.js';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-tailwind.config';
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('js');

        $output .= "module.exports = {\n";

        // Font families
        $output .= "  fontFamily: {\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $fallback = $font->isMonospace() ? 'monospace' : 'sans-serif';
            $output .= sprintf(
                "    '%s': ['%s', '%s'],\n",
                $key,
                $font->getName(),
                $fallback
            );
        }
        $output .= "  },\n";

        // Font weights
        $weights = $fonts->getUniqueWeights();
        if ([] !== $weights) {
            $output .= "  fontWeight: {\n";
            foreach ($weights as $weight) {
                $name = $this->getWeightName($weight);
                $output .= sprintf("    %s: '%d',\n", $name, $weight);
            }
            $output .= "  },\n";
        }

        $output .= "};\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in tailwind.config.js:
  const fontConfig = require('./assets/fonts-tailwind.config.js');
  
  module.exports = {
    theme: {
      extend: {
        fontFamily: fontConfig.fontFamily,
        fontWeight: fontConfig.fontWeight,
      },
    },
  };

Usage in HTML:
  <p class="font-sans font-normal">Text with custom font</p>
  <code class="font-mono">Code with monospace font</code>
INSTRUCTIONS;
    }
}
