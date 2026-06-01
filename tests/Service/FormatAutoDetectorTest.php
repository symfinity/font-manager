<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Service\FormatAutoDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class FormatAutoDetectorTest extends TestCase
{
    private FormatAutoDetector $detector;
    private Filesystem $filesystem;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->detector = new FormatAutoDetector();
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/font-manager-test-' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testDetectReturnsDefaultCssVariables(): void
    {
        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('css_variables', $result);
    }

    public function testDetectBootstrapFromComposer(): void
    {
        $composerJson = [
            'require' => [
                'twbs/bootstrap' => '^5.3',
            ],
        ];

        $json = json_encode($composerJson);
        if (false === $json) {
            $this->fail('Failed to encode JSON');
        }

        $this->filesystem->dumpFile($this->tempDir . '/composer.json', $json);

        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('scss_bootstrap', $result);
    }

    public function testDetectTailwindFromPackageJson(): void
    {
        $packageJson = [
            'devDependencies' => [
                'tailwindcss' => '^3.0',
            ],
        ];

        $json = json_encode($packageJson);
        if (false === $json) {
            $this->fail('Failed to encode JSON');
        }

        $this->filesystem->dumpFile($this->tempDir . '/package.json', $json);

        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('tailwind_config', $result);
    }

    public function testDetectTailwindFromConfigFile(): void
    {
        $this->filesystem->touch($this->tempDir . '/tailwind.config.js');

        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('tailwind_config', $result);
    }

    public function testDetectTypeScriptFromTsConfig(): void
    {
        $this->filesystem->touch($this->tempDir . '/tsconfig.json');

        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('typescript_definitions', $result);
        $this->assertContains('esm_javascript', $result);
    }

    public function testDetectMultipleFormats(): void
    {
        // Bootstrap
        $composerJson = ['require' => ['twbs/bootstrap' => '^5.3']];
        $json = json_encode($composerJson);
        if (false === $json) {
            $this->fail('Failed to encode JSON');
        }
        $this->filesystem->dumpFile($this->tempDir . '/composer.json', $json);

        // TypeScript
        $this->filesystem->touch($this->tempDir . '/tsconfig.json');

        $result = $this->detector->detect($this->tempDir);

        $this->assertContains('css_variables', $result);
        $this->assertContains('scss_bootstrap', $result);
        $this->assertContains('typescript_definitions', $result);
        $this->assertContains('esm_javascript', $result);
    }

    public function testDetectReturnsUniqueFormats(): void
    {
        $result = $this->detector->detect($this->tempDir);

        $this->assertSame($result, array_unique($result));
    }
}
