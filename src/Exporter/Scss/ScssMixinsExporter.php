<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter\Scss;

use Symfinity\FontManager\Exporter\AbstractExporter;
use Symfinity\FontManager\Model\FontCollection;

final class ScssMixinsExporter extends AbstractExporter
{
    public function getName(): string
    {
        return 'scss_mixins';
    }

    public function getLabel(): string
    {
        return 'SCSS Mixins & Functions';
    }

    public function getFileExtension(): string
    {
        return '.scss';
    }

    public function getDefaultFilename(): string
    {
        return 'fonts-mixins';
    }

    public function getDependencies(): array
    {
        return ['scss_variables'];
    }

    public function export(FontCollection $fonts, array $options = []): string
    {
        $output = $this->generateHeader('scss');

        $output .= "@import './fonts-variables';\n\n";

        // Font map
        $output .= "// Font Map\n";
        $output .= "\$fonts: (\n";
        foreach ($fonts->all() as $font) {
            $key = $font->getSemantic() ?? $font->getSanitizedName();
            $output .= sprintf(
                "  '%s': \$font-family-%s,\n",
                $key,
                $font->getSanitizedName()
            );
        }
        $output .= ");\n\n";

        // Font weights map
        $weights = $fonts->getUniqueWeights();
        $output .= "// Font Weights Map\n";
        $output .= "\$font-weights: (\n";
        foreach ($weights as $weight) {
            $name = $this->getWeightName($weight);
            $output .= sprintf("  '%s': %d,\n", $name, $weight);
        }
        $output .= ");\n\n";

        // Functions
        $scssNameVar = '$name';
        $output .= "// Get font family by name\n";
        $output .= "@function font-family({$scssNameVar}) {\n";
        $output .= "  @if map-has-key(\$fonts, {$scssNameVar}) {\n";
        $output .= "    @return map-get(\$fonts, {$scssNameVar});\n";
        $output .= "  }\n";
        $output .= "  @warn \"Font '#{" . '{$name}' . "}' not found. Available: #{map-keys(\$fonts)}\";\n";
        $output .= "  @return null;\n";
        $output .= "}\n\n";

        $output .= "// Get font weight by name\n";
        $output .= "@function font-weight({$scssNameVar}) {\n";
        $output .= "  @if map-has-key(\$font-weights, {$scssNameVar}) {\n";
        $output .= "    @return map-get(\$font-weights, {$scssNameVar});\n";
        $output .= "  }\n";
        $output .= "  @warn \"Font weight '#{" . '{$name}' . "}' not found. Available: #{map-keys(\$font-weights)}\";\n";
        $output .= "  @return null;\n";
        $output .= "}\n\n";

        // Mixins
        $output .= "// Apply font family and weight\n";
        $output .= "@mixin apply-font(\$family, \$weight: normal) {\n";
        $output .= "  font-family: font-family(\$family);\n";
        $output .= "  \n";
        $output .= "  @if type-of(\$weight) == 'string' {\n";
        $output .= "    font-weight: font-weight(\$weight);\n";
        $output .= "  } @else {\n";
        $output .= "    font-weight: \$weight;\n";
        $output .= "  }\n";
        $output .= "}\n\n";

        $output .= "// Apply font with size\n";
        $output .= "@mixin font(\$family, \$size, \$weight: normal, \$line-height: 1.5) {\n";
        $output .= "  @include apply-font(\$family, \$weight);\n";
        $output .= "  font-size: \$size;\n";
        $output .= "  line-height: \$line-height;\n";
        $output .= "}\n";

        return $output;
    }

    public function getUsageInstructions(): string
    {
        return <<<INSTRUCTIONS
Import in your SCSS:
  @import './fonts-mixins';

Usage with functions:
  .my-class {
    font-family: font-family('sans');
    font-weight: font-weight('bold');
  }

Usage with mixins:
  .my-class {
    @include apply-font('sans', 'bold');
  }
  
  .my-heading {
    @include font('sans', 2rem, 'bold', 1.2);
  }
INSTRUCTIONS;
    }
}
