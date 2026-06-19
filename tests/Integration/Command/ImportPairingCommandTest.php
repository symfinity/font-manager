<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Integration\Command;

use Symfinity\FontManager\Command\ImportPairingCommand;
use Symfinity\FontManager\Import\FontManagerConfigWriter;
use Symfinity\FontManager\Import\FonttrioPairingAdapter;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use Symfinity\FontManager\Import\PairingConfigMerger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
            $this->tempDir . '/config/packages/symfinity_font_manager.yaml',
            $merged
        );

        $parsed = Yaml::parseFile($this->tempDir . '/config/packages/symfinity_font_manager.yaml');
        self::assertIsArray($parsed);
        $fonts = $parsed['font_manager']['fonts'] ?? [];
        self::assertIsArray($fonts);
        self::assertArrayHasKey('playfair-display', $fonts);
        self::assertArrayHasKey('source-serif-4', $fonts);
        self::assertArrayHasKey('jetbrains-mono', $fonts);
        self::assertSame('editorial', $parsed['font_manager']['pairings']['active'] ?? null);
    }

    public function testCommandWritesConfigFromFixtureSource(): void
    {
        $tester = new CommandTester($this->createCommand());
        $exit = $tester->execute(['source' => $this->fixtureDir . '/editorial.json']);

        self::assertSame(0, $exit);
        $configPath = $this->tempDir . '/config/packages/symfinity_font_manager.yaml';
        self::assertTrue($this->filesystem->exists($configPath));

        $parsed = Yaml::parseFile($configPath);
        self::assertSame('editorial', $parsed['font_manager']['pairings']['active'] ?? null);
        self::assertCount(3, $parsed['font_manager']['fonts'] ?? []);
        self::assertStringContainsString('editorial', $tester->getDisplay());
    }

    public function testCommandDryRunDoesNotWriteConfig(): void
    {
        $tester = new CommandTester($this->createCommand());
        $exit = $tester->execute([
            'source' => $this->fixtureDir . '/editorial.json',
            '--dry-run' => true,
        ]);

        self::assertSame(0, $exit);
        self::assertFalse($this->filesystem->exists($this->tempDir . '/config/packages/symfinity_font_manager.yaml'));
        self::assertStringContainsString('No files written', $tester->getDisplay());
    }

    public function testCommandImportsEveryCatalogEntry(): void
    {
        $tester = new CommandTester($this->createCommand([
            'font_manager.pairings' => [
                'catalog' => [
                    'editorial' => ['source' => $this->fixtureDir . '/editorial.json'],
                ],
            ],
        ]));
        $exit = $tester->execute(['--all-catalog' => true]);

        self::assertSame(0, $exit);
        $parsed = Yaml::parseFile($this->tempDir . '/config/packages/symfinity_font_manager.yaml');
        self::assertArrayHasKey('playfair-display', $parsed['font_manager']['fonts'] ?? []);
    }

    public function testCommandFailsWithoutSource(): void
    {
        $tester = new CommandTester($this->createCommand());
        $exit = $tester->execute([]);

        self::assertSame(1, $exit);
        self::assertStringContainsString('Missing source', $tester->getDisplay());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function createCommand(array $parameters = ['font_manager.pairings' => []]): ImportPairingCommand
    {
        $adapter = new FonttrioPairingAdapter(new FonttrioRegistryClient(null, $this->fixtureDir));

        return new ImportPairingCommand(
            $adapter,
            new PairingConfigMerger(),
            new FontManagerConfigWriter($this->filesystem),
            new ParameterBag($parameters),
            $this->tempDir,
        );
    }
}
