<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfony\Component\Filesystem\Filesystem;

final class BuildToolDetector
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    /**
     * Auto-detect build tool from project files.
     */
    public function detect(string $projectDir): BuildToolType
    {
        // Check for Vite
        if ($this->filesystem->exists($projectDir . '/vite.config.js')
            || $this->filesystem->exists($projectDir . '/vite.config.ts')
            || $this->filesystem->exists($projectDir . '/vite.config.mjs')
        ) {
            return BuildToolType::VITE;
        }

        // Check for Webpack/Encore
        if ($this->filesystem->exists($projectDir . '/webpack.config.js')
            || $this->filesystem->exists($projectDir . '/webpack.config.ts')
            || $this->filesystem->exists($projectDir . '/encore.config.js')
        ) {
            return BuildToolType::WEBPACK;
        }

        // Check for AssetMapper (Symfony 6.3+)
        if ($this->filesystem->exists($projectDir . '/config/packages/asset_mapper.yaml')
            || $this->filesystem->exists($projectDir . '/importmap.php')
        ) {
            return BuildToolType::ASSET_MAPPER;
        }

        // Default fallback
        return BuildToolType::UNKNOWN;
    }

    /**
     * Get default output paths for build tool.
     *
     * @return array{fonts: string, styles: string, config: string}
     */
    public function getDefaultPaths(BuildToolType $buildTool): array
    {
        return match ($buildTool) {
            BuildToolType::ASSET_MAPPER => [
                'fonts' => 'assets/fonts',
                'styles' => 'assets/styles',
                'config' => 'assets',
            ],
            BuildToolType::WEBPACK, BuildToolType::VITE => [
                'fonts' => 'assets/fonts',
                'styles' => 'assets',
                'config' => 'assets',
            ],
            BuildToolType::UNKNOWN => [
                'fonts' => 'assets/fonts',
                'styles' => 'assets',
                'config' => 'assets',
            ],
        };
    }

    /**
     * Get human-readable name for build tool.
     */
    public function getName(BuildToolType $buildTool): string
    {
        return match ($buildTool) {
            BuildToolType::ASSET_MAPPER => 'AssetMapper',
            BuildToolType::WEBPACK => 'Webpack/Encore',
            BuildToolType::VITE => 'Vite',
            BuildToolType::UNKNOWN => 'Unknown',
        };
    }
}
