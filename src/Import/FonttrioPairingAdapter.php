<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

use Symfinity\FontManager\Exception\InvalidFonttrioRegistryException;

final class FonttrioPairingAdapter implements FontPairingImportPort
{
    public function __construct(
        private readonly FonttrioRegistryClient $registryClient,
    ) {
    }

    public function import(string $source): PairingImportResult
    {
        $styleUrl = $this->registryClient->resolveUrl($source);
        $style = $this->registryClient->fetch($source);

        $type = $style['type'] ?? null;
        if ('registry:style' !== $type) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Expected registry:style, got %s for source %s.',
                is_string($type) ? $type : 'unknown',
                $source
            ));
        }

        $pairingId = $style['name'] ?? null;
        if (!is_string($pairingId) || '' === $pairingId) {
            throw new InvalidFonttrioRegistryException('Fonttrio style registry item is missing a name.');
        }

        $dependencies = $style['registryDependencies'] ?? null;
        if (!is_array($dependencies) || [] === $dependencies) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Fonttrio pairing "%s" has no registryDependencies.',
                $pairingId
            ));
        }

        $fonts = [];
        $variableToSlug = [];

        foreach ($dependencies as $dependencyUrl) {
            if (!is_string($dependencyUrl) || '' === $dependencyUrl) {
                throw new InvalidFonttrioRegistryException(sprintf(
                    'Invalid dependency URL in pairing "%s".',
                    $pairingId
                ));
            }

            try {
                $fontItem = $this->registryClient->fetch($dependencyUrl);
            } catch (InvalidFonttrioRegistryException $exception) {
                throw new InvalidFonttrioRegistryException(sprintf(
                    'Broken dependency URL %s in pairing "%s": %s',
                    $dependencyUrl,
                    $pairingId,
                    $exception->getMessage()
                ), 0, $exception);
            }

            $fontType = $fontItem['type'] ?? null;
            if ('registry:font' !== $fontType) {
                throw new InvalidFonttrioRegistryException(sprintf(
                    'Dependency %s is not registry:font (got %s).',
                    $dependencyUrl,
                    is_string($fontType) ? $fontType : 'unknown'
                ));
            }

            $fontConfig = $this->parseFontItem($fontItem, $dependencyUrl);
            $slug = $fontConfig['slug'];
            $fonts[$slug] = $fontConfig['entry'];
            $variableToSlug[$fontConfig['css_variable']] = $slug;
        }

        $roles = $this->parseSemanticRoles($style, $variableToSlug, $pairingId);

        $title = $style['title'] ?? null;
        $categories = $style['categories'] ?? [];
        if (!is_array($categories)) {
            $categories = [];
        }

        return new PairingImportResult(
            id: $pairingId,
            fonts: $fonts,
            roles: $roles,
            provenance: [
                'source_url' => $styleUrl,
                'fetched_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'adapter' => 'fonttrio-v1',
            ],
            label: is_string($title) ? $title : null,
            categories: array_values(array_filter($categories, 'is_string')),
        );
    }

    /**
     * @param array<string, mixed> $fontItem
     *
     * @return array{slug: string, css_variable: string, entry: array<string, mixed>}
     */
    private function parseFontItem(array $fontItem, string $dependencyUrl): array
    {
        $name = $fontItem['name'] ?? null;
        if (!is_string($name) || '' === $name) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Font dependency at %s is missing a name.',
                $dependencyUrl
            ));
        }

        $font = $fontItem['font'] ?? null;
        if (!is_array($font)) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Font dependency "%s" is missing font metadata.',
                $name
            ));
        }

        $provider = $font['provider'] ?? null;
        if ('google' !== $provider) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Font "%s" uses unsupported provider "%s" (v1 supports google only).',
                $name,
                is_string($provider) ? $provider : 'unknown'
            ));
        }

        $family = $font['family'] ?? null;
        $import = $font['import'] ?? null;
        $variable = $font['variable'] ?? null;
        if (!is_string($family) || !is_string($import) || !is_string($variable)) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Font "%s" is missing required family/import/variable fields.',
                $name
            ));
        }

        $weights = $this->normalizeWeights($font['weight'] ?? []);
        $subsets = $this->normalizeSubsets($font['subsets'] ?? ['latin']);

        return [
            'slug' => $name,
            'css_variable' => $variable,
            'entry' => [
                'provider' => 'google',
                'family' => $family,
                'import' => $import,
                'weights' => $weights,
                'subsets' => $subsets,
                'css_variable' => $variable,
            ],
        ];
    }

    /**
     * @param array<string, string> $variableToSlug
     *
     * @return array{body: string, heading: string, mono: string}
     */
    private function parseSemanticRoles(array $style, array $variableToSlug, string $pairingId): array
    {
        $theme = $style['cssVars']['theme'] ?? null;
        if (!is_array($theme)) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Fonttrio pairing "%s" is missing cssVars.theme.',
                $pairingId
            ));
        }

        $roleMap = [
            'body' => '--font-body',
            'heading' => '--font-heading',
            'mono' => '--font-mono',
        ];

        $roles = [];
        foreach ($roleMap as $role => $themeKey) {
            $value = $theme[$themeKey] ?? null;
            if (!is_string($value)) {
                if ('body' === $role) {
                    throw new InvalidFonttrioRegistryException(sprintf(
                        'Fonttrio pairing "%s" is missing required cssVars.theme.%s.',
                        $pairingId,
                        $themeKey
                    ));
                }

                continue;
            }

            $variable = $this->extractCssVariableReference($value);
            if (null === $variable || !isset($variableToSlug[$variable])) {
                throw new InvalidFonttrioRegistryException(sprintf(
                    'Unable to resolve %s reference "%s" in pairing "%s".',
                    $themeKey,
                    $value,
                    $pairingId
                ));
            }

            $roles[$role] = $variableToSlug[$variable];
        }

        if (!isset($roles['body'])) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Fonttrio pairing "%s" requires a body role mapping.',
                $pairingId
            ));
        }

        return [
            'body' => $roles['body'],
            'heading' => $roles['heading'] ?? $roles['body'],
            'mono' => $roles['mono'] ?? $roles['body'],
        ];
    }

    private function extractCssVariableReference(string $value): ?string
    {
        if (preg_match('/var\((--[^)]+)\)/', $value, $matches) === 1) {
            return $matches[1];
        }

        if (str_starts_with($value, '--')) {
            return $value;
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function normalizeWeights(mixed $weights): array
    {
        if (!is_array($weights)) {
            return [400];
        }

        $normalized = [];
        foreach ($weights as $weight) {
            $normalized[] = (int) $weight;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return [] === $normalized ? [400] : $normalized;
    }

    /**
     * @return string[]
     */
    private function normalizeSubsets(mixed $subsets): array
    {
        if (!is_array($subsets)) {
            return ['latin'];
        }

        $filtered = array_values(array_unique(array_filter(
            array_map(static fn (mixed $subset): string => is_string($subset) ? $subset : '', $subsets),
            static fn (string $subset): bool => '' !== $subset && 'menu' !== $subset
        )));

        return [] === $filtered ? ['latin'] : $filtered;
    }
}
