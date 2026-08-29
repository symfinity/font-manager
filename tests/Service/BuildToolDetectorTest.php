<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Service\BuildToolDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class BuildToolDetectorTest extends TestCase
{
    private BuildToolDetector $detector;
    private Filesystem $filesystem;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->detector = new BuildToolDetector();
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/font-manager-test-' . uniqid();
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testDetectVite(): void
    {
        $this->filesystem->touch($this->tempDir . '/vite.config.js');

        $result = $this->detector->detect($this->tempDir);

        $this->assertSame(BuildToolType::VITE, $result);
    }

    public function testDetectWebpack(): void
    {
        $this->filesystem->touch($this->tempDir . '/webpack.config.js');

        $result = $this->detector->detect($this->tempDir);

        $this->assertSame(BuildToolType::WEBPACK, $result);
    }

    public function testDetectAssetMapper(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/config/packages');
        $this->filesystem->touch($this->tempDir . '/config/packages/asset_mapper.yaml');

        $result = $this->detector->detect($this->tempDir);

        $this->assertSame(BuildToolType::ASSET_MAPPER, $result);
    }

    public function testDetectUnknown(): void
    {
        $result = $this->detector->detect($this->tempDir);

        $this->assertSame(BuildToolType::UNKNOWN, $result);
    }

    public function testGetDefaultPaths(): void
    {
        $paths = $this->detector->getDefaultPaths(BuildToolType::ASSET_MAPPER);

        $this->assertArrayHasKey('fonts', $paths);
        $this->assertArrayHasKey('styles', $paths);
        $this->assertArrayHasKey('config', $paths);
        $this->assertSame('assets/fonts', $paths['fonts']);
    }

    public function testGetName(): void
    {
        $this->assertSame('AssetMapper', $this->detector->getName(BuildToolType::ASSET_MAPPER));
        $this->assertSame('Webpack/Encore', $this->detector->getName(BuildToolType::WEBPACK));
        $this->assertSame('Vite', $this->detector->getName(BuildToolType::VITE));
        $this->assertSame('Unknown', $this->detector->getName(BuildToolType::UNKNOWN));
    }
}
