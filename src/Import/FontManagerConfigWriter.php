<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class FontManagerConfigWriter
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $configPath): array
    {
        if (!$this->filesystem->exists($configPath)) {
            return [];
        }

        $content = file_get_contents($configPath);
        if (false === $content) {
            return [];
        }

        $parsed = Yaml::parse($content);
        if (!is_array($parsed)) {
            return [];
        }

        $config = $parsed['font_manager'] ?? [];
        if (!is_array($config)) {
            return [];
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function write(string $configPath, array $config): void
    {
        $this->filesystem->mkdir(dirname($configPath));

        $yaml = Yaml::dump(['font_manager' => $config], 6, 2);
        $this->filesystem->dumpFile($configPath, $yaml);
    }
}
