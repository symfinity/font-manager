<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\JavaScript\TailwindConfigExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class TailwindConfigExporterTest extends TestCase
{
    private TailwindConfigExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new TailwindConfigExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('tailwind_config', $this->exporter->getName());
    }

    public function testExportTailwindFormat(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('module.exports', $output);
        $this->assertStringContainsString('fontFamily:', $output);
        $this->assertStringContainsString('fontWeight:', $output);
        $this->assertStringContainsString("'sans':", $output);
        $this->assertStringContainsString("'Ubuntu'", $output);
    }

    public function testExportWithMonospace(): void
    {
        $fonts = [
            new Font('Ubuntu', [400], ['normal'], false, 'sans'),
            new Font('JetBrains Mono', [400], ['normal'], true, 'mono'),
        ];

        $collection = new FontCollection($fonts);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString("'mono':", $output);
        $this->assertStringContainsString('monospace', $output);
    }

    public function testUsageInstructions(): void
    {
        $instructions = $this->exporter->getUsageInstructions();

        $this->assertStringContainsString('tailwind.config.js', $instructions);
        $this->assertStringContainsString('require', $instructions);
    }
}
