<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Integration\Command;

use Symfinity\FontManager\Import\FontManagerConfigWriter;
use Symfinity\FontManager\Import\FonttrioPairingAdapter;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use Symfinity\FontManager\Import\PairingConfigMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final class ImportPairingCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;
    private string $fixtureDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/font-manager-import-' . uniqid();
        $this->filesystem->mkdir($this->tempDir . '/config/packages');
        $this->fixtureDir = dirname(__DIR__, 2) . '/Fixtures/Fonttrio';
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testImportEditorialFixtureWritesThreeFontSlugs(): void
    {
        $client = new FonttrioRegistryClient(null, $this->fixtureDir);
        $adapter = new FonttrioPairingAdapter($client);
        $result = $adapter->import($this->fixtureDir . '/editorial.json');

        $merged = (new PairingConfigMerger())->merge([], $result);
        (new FontManagerConfigWriter($this->filesystem))->write(
            $this->tempDir . '/config/packages/font_manager.yaml',
            $merged
        );

        $parsed = Yaml::parseFile($this->tempDir . '/config/packages/font_manager.yaml');
        self::assertIsArray($parsed);
        $fonts = $parsed['font_manager']['fonts'] ?? [];
        self::assertIsArray($fonts);
        self::assertArrayHasKey('playfair-display', $fonts);
        self::assertArrayHasKey('source-serif-4', $fonts);
        self::assertArrayHasKey('jetbrains-mono', $fonts);
        self::assertSame('editorial', $parsed['font_manager']['pairings']['active'] ?? null);
    }

    public function testDryRunMergePreviewDoesNotWriteConfig(): void
    {
        $client = new FonttrioRegistryClient(null, $this->fixtureDir);
        $adapter = new FonttrioPairingAdapter($client);
        $result = $adapter->import($this->fixtureDir . '/editorial.json');
        $merged = (new PairingConfigMerger())->merge([], $result);

        self::assertSame('editorial', $merged['pairings']['active'] ?? null);
        self::assertCount(3, $merged['fonts'] ?? []);
        self::assertFalse($this->filesystem->exists($this->tempDir . '/config/packages/font_manager.yaml'));
    }
}
