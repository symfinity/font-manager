<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Exporter;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Model\FontCollection;

interface ExporterInterface
{
    /**
     * Unique identifier (e.g., 'css_variables', 'tailwind_config').
     */
    public function getName(): string;

    /**
     * Human-readable label.
     */
    public function getLabel(): string;

    /**
     * File extension (e.g., '.css', '.scss', '.js', '.json').
     */
    public function getFileExtension(): string;

    /**
     * Export fonts to string format.
     *
     * @param array<string, mixed> $options
     */
    public function export(FontCollection $fonts, array $options = []): string;

    /**
     * Get default output filename (without extension).
     */
    public function getDefaultFilename(): string;

    /**
     * Get output path based on build tool.
     */
    public function getOutputPath(string $baseDir, BuildToolType $buildTool): string;

    /**
     * Check if this exporter supports given build tool.
     */
    public function supportsBuildTool(BuildToolType $buildTool): bool;

    /**
     * Get usage instructions for developers.
     */
    public function getUsageInstructions(): string;

    /**
     * Dependencies (other exporters that must run first).
     *
     * @return string[] Array of exporter names
     */
    public function getDependencies(): array;
}
