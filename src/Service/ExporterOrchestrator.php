<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Exception\ExporterException;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Model\FontCollection;
use Symfony\Component\Filesystem\Filesystem;

final class ExporterOrchestrator
{
    public function __construct(
        private readonly ExporterRegistry $registry,
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    /**
     * Export fonts in correct dependency order.
     *
     * @param string[] $exporterNames
     * @param array<string, mixed> $options
     *
     * @return array<int, array{exporter: string, path: string, content: string, size: int, written: bool}>
     */
    public function export(
        FontCollection $fonts,
        array $exporterNames,
        string $baseDir,
        BuildToolType $buildTool,
        bool $write = true,
        array $options = []
    ): array {
        // Resolve dependencies
        $orderedExporters = $this->resolveDependencies($exporterNames);

        $results = [];

        foreach ($orderedExporters as $name) {
            $exporter = $this->registry->get($name);

            // Skip if build tool not supported
            if (!$exporter->supportsBuildTool($buildTool)) {
                continue;
            }

            $content = $exporter->export($fonts, $options);
            $path = $exporter->getOutputPath($baseDir, $buildTool);

            $result = [
                'exporter' => $name,
                'path' => $path,
                'content' => $content,
                'size' => strlen($content),
                'written' => false,
            ];

            // Write to file if requested
            if ($write) {
                $dir = dirname($path);
                if (!$this->filesystem->exists($dir)) {
                    $this->filesystem->mkdir($dir, 0755);
                }

                $this->filesystem->dumpFile($path, $content);
                $result['written'] = true;
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Resolve dependency order (topological sort).
     *
     * @param string[] $exporterNames
     *
     * @return string[]
     */
    private function resolveDependencies(array $exporterNames): array
    {
        $resolved = [];
        $seen = [];

        foreach ($exporterNames as $name) {
            $this->resolveDependency($name, $resolved, $seen);
        }

        return $resolved;
    }

    /**
     * @param string[] $resolved
     * @param string[] $seen
     */
    private function resolveDependency(string $name, array &$resolved, array &$seen): void
    {
        if (in_array($name, $resolved, true)) {
            return;
        }

        if (in_array($name, $seen, true)) {
            throw new ExporterException(sprintf('Circular dependency detected: %s', $name));
        }

        $seen[] = $name;
        $exporter = $this->registry->get($name);

        foreach ($exporter->getDependencies() as $dependency) {
            $this->resolveDependency($dependency, $resolved, $seen);
        }

        $resolved[] = $name;
    }

    /**
     * Get all exporters that are safe to run (no missing dependencies).
     *
     * @param string[] $exporterNames
     *
     * @return array{valid: string[], invalid: array<string, string[]>}
     */
    public function validateDependencies(array $exporterNames): array
    {
        $valid = [];
        $invalid = [];

        foreach ($exporterNames as $name) {
            if (!$this->registry->has($name)) {
                $invalid[$name] = ['Exporter not found'];

                continue;
            }

            $exporter = $this->registry->get($name);
            $missing = [];

            foreach ($exporter->getDependencies() as $dependency) {
                if (!in_array($dependency, $exporterNames, true)) {
                    $missing[] = $dependency;
                }
            }

            if ([] === $missing) {
                $valid[] = $name;
            } else {
                $invalid[$name] = $missing;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }
}
