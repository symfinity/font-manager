<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter;

use Symfinity\FontManager\Exception\ExporterException;

final class ExporterRegistry
{
    /** @var array<string, ExporterInterface> */
    private array $exporters = [];

    public function register(ExporterInterface $exporter): void
    {
        $this->exporters[$exporter->getName()] = $exporter;
    }

    public function get(string $name): ExporterInterface
    {
        if (!isset($this->exporters[$name])) {
            throw new ExporterException(sprintf('Exporter "%s" not found', $name));
        }

        return $this->exporters[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->exporters[$name]);
    }

    /**
     * @return ExporterInterface[]
     */
    public function all(): array
    {
        return $this->exporters;
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->exporters);
    }
}
