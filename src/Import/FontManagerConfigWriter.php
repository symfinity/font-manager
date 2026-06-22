<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class FontManagerConfigWriter
{
    public const CONFIG_ROOT_KEY = 'symfinity_font_manager';

    private const LEGACY_CONFIG_ROOT_KEY = 'font_manager';

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

        $config = $parsed[self::CONFIG_ROOT_KEY] ?? $parsed[self::LEGACY_CONFIG_ROOT_KEY] ?? null;
        if (!is_array($config)) {
            return [];
        }

        /** @var array<string, mixed> $config */
        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function write(string $configPath, array $config): void
    {
        $this->filesystem->mkdir(dirname($configPath));

        $yaml = Yaml::dump([self::CONFIG_ROOT_KEY => $config], 6, 2);
        $this->filesystem->dumpFile($configPath, $yaml);
    }
}
