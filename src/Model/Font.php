<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Model;

final class Font
{
    /**
     * @param string[] $styles
     * @param int[] $weights
     * @param array<string, string> $files
     */
    public function __construct(
        private readonly string $name,
        private readonly array $weights,
        private readonly array $styles,
        private readonly bool $monospace = false,
        private readonly ?string $semantic = null,
        private readonly array $files = [],
        private readonly ?string $cssVariable = null,
        private readonly ?string $category = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSanitizedName(): string
    {
        return str_replace(' ', '-', strtolower($this->name));
    }

    /**
     * @return int[]
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * @return string[]
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    public function isMonospace(): bool
    {
        return $this->monospace;
    }

    public function getSemantic(): ?string
    {
        return $this->semantic;
    }

    /**
     * @return array<string, string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Get CSS value for font-family property.
     */
    public function getCssValue(): string
    {
        return sprintf("'%s', %s", $this->name, $this->genericFamily());
    }

    /**
     * Resolve the generic CSS font family. Prefers an explicit category (e.g. from
     * a font registry) over guessing from the family name, which mislabels serif
     * faces such as "Playfair Display" that do not contain "serif".
     */
    private function genericFamily(): string
    {
        if ($this->monospace) {
            return 'monospace';
        }

        if (null !== $this->category && '' !== $this->category) {
            $category = strtolower($this->category);

            return match (true) {
                str_contains($category, 'mono') => 'monospace',
                str_contains($category, 'sans') => 'sans-serif',
                str_contains($category, 'serif') => 'serif',
                str_contains($category, 'hand'), str_contains($category, 'script'), str_contains($category, 'cursive') => 'cursive',
                str_contains($category, 'display') => 'sans-serif',
                default => $this->genericFamilyFromName(),
            };
        }

        return $this->genericFamilyFromName();
    }

    private function genericFamilyFromName(): string
    {
        return str_contains(strtolower($this->name), 'serif') ? 'serif' : 'sans-serif';
    }

    public function getCssVariableName(): string
    {
        if (null !== $this->cssVariable && '' !== $this->cssVariable) {
            return $this->cssVariable;
        }

        return '--font-family-' . $this->getSanitizedName();
    }

    /**
     * Get default weight (first in array or 400).
     */
    public function getDefaultWeight(): int
    {
        return [] === $this->weights ? 400 : $this->weights[0];
    }

    /**
     * Get heading weight (first weight >= 500, or 700 as fallback).
     */
    public function getHeadingWeight(): int
    {
        foreach ($this->weights as $weight) {
            if ($weight >= 500) {
                return $weight;
            }
        }

        return 700;
    }

    /**
     * Get bold weight (first weight >= 700, or 700 as fallback).
     */
    public function getBoldWeight(): int
    {
        foreach ($this->weights as $weight) {
            if ($weight >= 700) {
                return $weight;
            }
        }

        return 700;
    }

    public function hasItalic(): bool
    {
        return in_array('italic', $this->styles, true);
    }
}
