<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsStatusCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class FontsStatusCommandTest extends TestCase
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

        $command = new FontsStatusCommand($manifestFile, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No fonts locked yet', $output);
    }

    public function testExecuteShowsLockedFonts(): void
    {
        $manifestFile = $this->tempDir . '/manifest.json';

        $manifest = [
            'locked' => true,
            'generated_at' => '2025-11-03T12:00:00+00:00',
            'fonts' => [
                'Roboto' => [
                    'weights' => [400, 700],
                    'styles' => ['normal'],
                    'files' => ['roboto-400.woff2', 'roboto-700.woff2'],
                    'provider' => 'google',
                ],
            ],
        ];

        $jsonContent = json_encode($manifest);
        if (false === $jsonContent) {
            throw new \RuntimeException('Failed to encode manifest');
        }
        $this->filesystem->dumpFile($manifestFile, $jsonContent);

        $command = new FontsStatusCommand($manifestFile, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Roboto', $output);
        self::assertStringContainsString('400, 700', $output);
        self::assertStringContainsString('google', $output);
    }
}
