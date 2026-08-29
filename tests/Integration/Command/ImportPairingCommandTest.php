<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Integration\Command;

use Symfinity\FontManager\Command\ImportPairingCommand;
use Symfinity\FontManager\Import\FontManagerConfigWriter;
use Symfinity\FontManager\Import\FonttrioPairingAdapter;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use Symfinity\FontManager\Import\PairingConfigMerger;
use Symfinity\FontManager\Tests\FonttrioTestFixtures;
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
        $this->fixtureDir = FonttrioTestFixtures::directory();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testImportEditorialFixtureWritesThreeFontSlugs(): void
    {
        $client = new FonttrioRegistryClient(null, $this->fixtureDir);
        $adapter = new FonttrioPairingAdapter($client);
        $result = $adapter->import(FonttrioTestFixtures::EDITORIAL_SOURCE);

        $merged = (new PairingConfigMerger())->merge([], $result);
        (new FontManagerConfigWriter($this->filesystem))->write(
            $this->tempDir . '/config/packages/symfinity_font_manager.yaml',
            $merged
        );

        $config = $this->readBundleConfig($this->tempDir . '/config/packages/symfinity_font_manager.yaml');
        self::assertArrayHasKey('fonts', $config);
        $fonts = $config['fonts'];
        self::assertIsArray($fonts);
        self::assertArrayHasKey('playfair-display', $fonts);
        self::assertArrayHasKey('source-serif-4', $fonts);
        self::assertArrayHasKey('jetbrains-mono', $fonts);
        self::assertArrayHasKey('pairings', $config);
        $pairings = $config['pairings'];
        self::assertIsArray($pairings);
        self::assertSame('editorial', $pairings['active'] ?? null);
    }

    public function testCommandWritesConfigFromFixtureSource(): void
    {
        $tester = new CommandTester($this->createCommand());
        $exit = $tester->execute(['source' => FonttrioTestFixtures::EDITORIAL_SOURCE]);

        self::assertSame(0, $exit);
        $configPath = $this->tempDir . '/config/packages/symfinity_font_manager.yaml';
        self::assertTrue($this->filesystem->exists($configPath));

        $config = $this->readBundleConfig($configPath);
        self::assertArrayHasKey('pairings', $config);
        $pairings = $config['pairings'];
        self::assertIsArray($pairings);
        self::assertSame('editorial', $pairings['active'] ?? null);
        self::assertArrayHasKey('fonts', $config);
        $fonts = $config['fonts'];
        self::assertIsArray($fonts);
        self::assertCount(3, $fonts);
        self::assertStringContainsString('editorial', $tester->getDisplay());
    }

    public function testCommandDryRunDoesNotWriteConfig(): void
    {
        $tester = new CommandTester($this->createCommand());
        $exit = $tester->execute([
            'source' => FonttrioTestFixtures::EDITORIAL_SOURCE,
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
                    'editorial' => ['source' => FonttrioTestFixtures::EDITORIAL_SOURCE],
                ],
            ],
        ]));
        $exit = $tester->execute(['--all-catalog' => true]);

        self::assertSame(0, $exit);
        $config = $this->readBundleConfig($this->tempDir . '/config/packages/symfinity_font_manager.yaml');
        self::assertArrayHasKey('fonts', $config);
        $fonts = $config['fonts'];
        self::assertIsArray($fonts);
        self::assertArrayHasKey('playfair-display', $fonts);
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

    /**
     * @return array<string, mixed>
     */
    private function readBundleConfig(string $configPath): array
    {
        $parsed = Yaml::parseFile($configPath);
        self::assertIsArray($parsed);
        self::assertArrayHasKey(FontManagerConfigWriter::CONFIG_ROOT_KEY, $parsed);

        $config = $parsed[FontManagerConfigWriter::CONFIG_ROOT_KEY];
        self::assertIsArray($config);

        /** @var array<string, mixed> $config */
        return $config;
    }
}
