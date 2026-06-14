<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

final class PairingImportResult
{
    /**
     * @param array<string, array<string, mixed>> $fonts
     * @param array{body: string, heading: string, mono: string} $roles
     * @param array<string, mixed> $provenance
     */
    public function __construct(
        private readonly string $id,
        private readonly array $fonts,
        private readonly array $roles,
        private readonly array $provenance,
        private readonly ?string $label = null,
        /** @var string[] */
        private readonly array $categories = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getFonts(): array
    {
        return $this->fonts;
    }

    /**
     * @return array{body: string, heading: string, mono: string}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProvenance(): array
    {
        return $this->provenance;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }
}
