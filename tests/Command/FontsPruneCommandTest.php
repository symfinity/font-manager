<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsPruneCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class FontsPruneCommandTest extends TestCase
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

    public function testExecuteWithNoManifest(): void
    {
        $manifestFile = $this->tempDir . '/manifest.json';
        $fontsDir = $this->tempDir . '/fonts';

        $command = new FontsPruneCommand($manifestFile, $fontsDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No manifest file', $output);
    }

    public function testExecuteWithNoUnusedFonts(): void
    {
        $manifestFile = $this->tempDir . '/manifest.json';
        $fontsDir = $this->tempDir . '/fonts';

        $manifest = [
            'fonts' => [
                'Roboto' => [
                    'files' => ['roboto-400.woff2'],
                    'css' => 'roboto.css',
                ],
            ],
        ];

        $this->filesystem->mkdir($fontsDir);
        $this->filesystem->dumpFile($manifestFile, (string) json_encode($manifest));
        $this->filesystem->dumpFile($fontsDir . '/roboto-400.woff2', 'font');
        $this->filesystem->dumpFile($fontsDir . '/roboto.css', 'css');

        $command = new FontsPruneCommand($manifestFile, $fontsDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No unused fonts found', $output);
    }

    public function testExecuteDryRun(): void
    {
        $manifestFile = $this->tempDir . '/manifest.json';
        $fontsDir = $this->tempDir . '/fonts';

        $manifest = [
            'fonts' => [
                'Roboto' => [
                    'files' => ['roboto-400.woff2'],
                ],
            ],
        ];

        $this->filesystem->mkdir($fontsDir);
        $this->filesystem->dumpFile($manifestFile, (string) json_encode($manifest));
        $this->filesystem->dumpFile($fontsDir . '/unused-font.woff2', 'font');

        $command = new FontsPruneCommand($manifestFile, $fontsDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--dry-run' => true]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Dry run', $output);
        self::assertTrue($this->filesystem->exists($fontsDir . '/unused-font.woff2'));
    }
}
