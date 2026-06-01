<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsLockCommand;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\FontManager\Service\BuildToolDetector;
use Symfinity\FontManager\Service\ExporterOrchestrator;
use Symfinity\FontManager\Service\FontDownloader;
use Symfinity\FontManager\Service\FontLockManager;
use Symfinity\FontManager\Service\FormatAutoDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;

final class FontsLockCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/font-manager-test-' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testExecuteWithNoTemplates(): void
    {
        $httpClient = new MockHttpClient();
        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry();
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir . '/fonts', $httpClient, $registry, $this->filesystem);
        $lockManager = new FontLockManager(
            $this->tempDir . '/manifest.json',
            $downloader,
            $this->filesystem
        );

        $exporterRegistry = new ExporterRegistry();
        $orchestrator = new ExporterOrchestrator($exporterRegistry, $this->filesystem);
        $buildToolDetector = new BuildToolDetector($this->filesystem);
        $formatAutoDetector = new FormatAutoDetector($this->filesystem);
        $params = new ParameterBag([
            'font_manager.export.formats' => [],
            'font_manager.export.auto_detect' => false,
        ]);

        $command = new FontsLockCommand($lockManager, $orchestrator, $buildToolDetector, $formatAutoDetector, $params, $this->tempDir);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No template directories found', $output);
    }

    public function testExecuteScansTemplates(): void
    {
        $templateDir = $this->tempDir . '/templates';
        $this->filesystem->mkdir($templateDir);
        $this->filesystem->dumpFile(
            $templateDir . '/test.html.twig',
            "{{ font_manager('Roboto', '400', 'normal') }}"
        );

        $httpClient = new MockHttpClient([
            new \Symfony\Component\HttpClient\Response\MockResponse('@font-face { src: url(https://example.com/font.woff2); }'),
            new \Symfony\Component\HttpClient\Response\MockResponse('font-data'),
        ]);
        $googleProvider = new GoogleFontsProvider($httpClient);
        $registry = new ProviderRegistry();
        $registry->registerProvider($googleProvider);

        $downloader = new FontDownloader($this->tempDir . '/fonts', $httpClient, $registry, $this->filesystem);
        $lockManager = new FontLockManager(
            $this->tempDir . '/manifest.json',
            $downloader,
            $this->filesystem
        );

        $exporterRegistry = new ExporterRegistry();
        $orchestrator = new ExporterOrchestrator($exporterRegistry, $this->filesystem);
        $buildToolDetector = new BuildToolDetector($this->filesystem);
        $formatAutoDetector = new FormatAutoDetector($this->filesystem);
        $params = new ParameterBag([
            'font_manager.export.formats' => [],
            'font_manager.export.auto_detect' => false,
        ]);

        $command = new FontsLockCommand($lockManager, $orchestrator, $buildToolDetector, $formatAutoDetector, $params, $this->tempDir);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Found fonts', $output);
    }
}
