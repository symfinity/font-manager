<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\MigrateFromGoogleFontsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class MigrateFromGoogleFontsCommandTest extends TestCase
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

    public function testExecuteWithNoGoogleFontsInstallation(): void
    {
        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No google-fonts installation found', $output);
    }

    public function testExecuteDryRun(): void
    {
        $configDir = $this->tempDir . '/config/packages';
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile(
            $configDir . '/google_fonts.yaml',
            "google_fonts:\n    lock_fonts: true\n"
        );

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--dry-run' => true]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('DRY RUN', $output);
        self::assertStringContainsString('Would create', $output);

        self::assertFalse($this->filesystem->exists($configDir . '/symfinity_font_manager.yaml'));
    }

    public function testExecuteMigratesConfiguration(): void
    {
        $configDir = $this->tempDir . '/config/packages';
        $this->filesystem->mkdir($configDir);

        $oldConfig = <<<'YAML'
google_fonts:
    lock_fonts: true
    fonts_dir: '%kernel.project_dir%/assets/fonts'
    use_locked_fonts: false
YAML;

        $this->filesystem->dumpFile($configDir . '/google_fonts.yaml', $oldConfig);

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());

        self::assertTrue($this->filesystem->exists($configDir . '/symfinity_font_manager.yaml'));
        self::assertTrue($this->filesystem->exists($configDir . '/google_fonts.yaml.backup'));

        $newContent = file_get_contents($configDir . '/symfinity_font_manager.yaml');
        self::assertIsString($newContent);
        self::assertStringContainsString('symfinity_font_manager:', $newContent);
        self::assertStringContainsString("default_provider: 'google'", $newContent);
    }

    public function testExecuteMigratesTemplates(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        $this->filesystem->mkdir($templatesDir);

        $oldTemplate = <<<'TWIG'
<!DOCTYPE html>
<html>
<head>
    {{ google_fonts('Roboto', '400 700', 'normal') }}
    {{ google_fonts('Inter', '400', 'normal italic') }}
</head>
TWIG;

        $this->filesystem->dumpFile($templatesDir . '/base.html.twig', $oldTemplate);

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $newContent = file_get_contents($templatesDir . '/base.html.twig');
        self::assertIsString($newContent);
        self::assertStringContainsString('font_manager(', $newContent);
        self::assertStringNotContainsString('google_fonts(', $newContent);
    }

    public function testExecuteMigratesManifest(): void
    {
        $varDir = $this->tempDir . '/var';
        $this->filesystem->mkdir($varDir);

        $manifest = [
            'locked' => true,
            'fonts' => [
                'Roboto' => ['weights' => [400, 700]],
            ],
        ];

        $this->filesystem->dumpFile(
            $varDir . '/google-fonts.lock.json',
            (string) json_encode($manifest)
        );

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertTrue($this->filesystem->exists($varDir . '/font-manager.lock.json'));

        $manifestContent = file_get_contents($varDir . '/font-manager.lock.json');
        self::assertIsString($manifestContent);
        $newManifest = json_decode($manifestContent, true);
        self::assertIsArray($newManifest);
        self::assertTrue($newManifest['locked']);
        self::assertIsArray($newManifest['fonts']);
        self::assertArrayHasKey('Roboto', $newManifest['fonts']);
    }

    public function testExecuteSkipsTemplatesWhenRequested(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        $this->filesystem->mkdir($templatesDir);
        $this->filesystem->dumpFile(
            $templatesDir . '/base.html.twig',
            "{{ google_fonts('Roboto', '400') }}"
        );

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--skip-templates' => true]);

        $content = file_get_contents($templatesDir . '/base.html.twig');
        self::assertIsString($content);
        self::assertStringContainsString('google_fonts(', $content);
        self::assertStringNotContainsString('font_manager(', $content);
    }

    public function testExecuteSkipsConfigWhenRequested(): void
    {
        $configDir = $this->tempDir . '/config/packages';
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile(
            $configDir . '/google_fonts.yaml',
            "google_fonts:\n    lock_fonts: true\n"
        );

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['--skip-config' => true]);

        self::assertFalse($this->filesystem->exists($configDir . '/symfinity_font_manager.yaml'));
    }

    public function testExecuteHandlesExistingFontManagerConfig(): void
    {
        $configDir = $this->tempDir . '/config/packages';
        $this->filesystem->mkdir($configDir);
        $this->filesystem->dumpFile($configDir . '/google_fonts.yaml', "google_fonts:\n");
        $this->filesystem->dumpFile($configDir . '/symfinity_font_manager.yaml', "symfinity_font_manager:\n");

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        self::assertStringContainsString('already exists', $output);
    }

    public function testExecuteHandlesNestedTemplates(): void
    {
        $templatesDir = $this->tempDir . '/templates';
        $adminDir = $templatesDir . '/admin';
        $this->filesystem->mkdir($adminDir);

        $this->filesystem->dumpFile(
            $templatesDir . '/base.html.twig',
            "{{ google_fonts('Roboto', '400') }}"
        );

        $this->filesystem->dumpFile(
            $adminDir . '/dashboard.html.twig',
            "{{ google_fonts('Inter', '400') }}"
        );

        $command = new MigrateFromGoogleFontsCommand($this->tempDir, $this->filesystem);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $baseContent = file_get_contents($templatesDir . '/base.html.twig');
        $dashContent = file_get_contents($adminDir . '/dashboard.html.twig');

        self::assertIsString($baseContent);
        self::assertIsString($dashContent);
        self::assertStringContainsString('font_manager(', $baseContent);
        self::assertStringContainsString('font_manager(', $dashContent);
    }
}
