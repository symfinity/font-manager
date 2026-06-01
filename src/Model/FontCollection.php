<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Model;

final class FontCollection
{
    /** @var Font[] */
    private array $fonts = [];

    /** @var array<string, Font> */
    private array $semanticMap = [];

    /**
     * @param Font[] $fonts
     */
    public function __construct(array $fonts = [])
    {
        foreach ($fonts as $font) {
            $this->add($font);
        }
    }

    public function add(Font $font): void
    {
        $this->fonts[] = $font;

        if (null !== $font->getSemantic()) {
            $this->semanticMap[$font->getSemantic()] = $font;
        }
    }

    /**
     * @return Font[]
     */
    public function all(): array
    {
        return $this->fonts;
    }

    public function hasSemantic(string $semantic): bool
    {
        return isset($this->semanticMap[$semantic]);
    }

    public function getSemantic(string $semantic): ?Font
    {
        return $this->semanticMap[$semantic] ?? null;
    }

    /**
     * Get all unique weights across all fonts.
     *
     * @return int[]
     */
    public function getUniqueWeights(): array
    {
        $weights = [];

        foreach ($this->fonts as $font) {
            foreach ($font->getWeights() as $weight) {
                $weights[$weight] = true;
            }
        }

        $result = array_keys($weights);
        sort($result);

        return $result;
    }

    /**
     * Get all unique styles across all fonts.
     *
     * @return string[]
     */
    public function getUniqueStyles(): array
    {
        $styles = [];

        foreach ($this->fonts as $font) {
            foreach ($font->getStyles() as $style) {
                $styles[$style] = true;
            }
        }

        return array_keys($styles);
    }

    public function isEmpty(): bool
    {
        return [] === $this->fonts;
    }

    public function count(): int
    {
        return count($this->fonts);
    }
}
