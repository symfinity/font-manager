<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

final class PairingConfigMerger
{
    /**
     * @param array<string, mixed> $existingConfig
     *
     * @return array<string, mixed>
     */
    public function merge(array $existingConfig, PairingImportResult $result): array
    {
        $fonts = $existingConfig['fonts'] ?? [];
        if (!is_array($fonts)) {
            $fonts = [];
        }

        foreach ($result->getFonts() as $slug => $fontEntry) {
            $fonts[$slug] = $this->mergeFontEntry($fonts[$slug] ?? null, $fontEntry);
        }

        $pairings = $existingConfig['pairings'] ?? [];
        if (!is_array($pairings)) {
            $pairings = [];
        }

        $catalog = $pairings['catalog'] ?? [];
        if (!is_array($catalog)) {
            $catalog = [];
        }

        if (!isset($catalog[$result->getId()])) {
            $catalog[$result->getId()] = [
                'source' => '@fonttrio/' . $result->getId(),
            ];
        }

        if (null !== $result->getLabel()) {
            $catalog[$result->getId()]['label'] = $result->getLabel();
        }

        if ([] !== $result->getCategories()) {
            $catalog[$result->getId()]['categories'] = $result->getCategories();
        }

        $pairings['catalog'] = $catalog;
        $pairings['active'] = $result->getId();
        $pairings['active_roles'] = $result->getRoles();
        $pairings['provenance'] = $result->getProvenance();

        $existingConfig['fonts'] = $fonts;
        $existingConfig['pairings'] = $pairings;

        return $existingConfig;
    }

    /**
     * @param array<string, mixed>|null $existing
     * @param array<string, mixed> $incoming
     *
     * @return array<string, mixed>
     */
    private function mergeFontEntry(?array $existing, array $incoming): array
    {
        if (null === $existing) {
            return $incoming;
        }

        foreach ($incoming as $key => $value) {
            $existing[$key] = $value;
        }

        return $existing;
    }
}
