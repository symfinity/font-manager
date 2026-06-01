<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\Scss;

use Symfinity\FontManager\Exporter\Scss\ScssVariablesExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class ScssVariablesExporterTest extends TestCase
{
    private ScssVariablesExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new ScssVariablesExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('scss_variables', $this->exporter->getName());
    }

    public function testGetLabel(): void
    {
        $this->assertSame('SCSS Variables', $this->exporter->getLabel());
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('.scss', $this->exporter->getFileExtension());
    }

    public function testExportWithSemanticAliases(): void
    {
        $fonts = [
            new Font('Ubuntu', [400, 700], ['normal'], false, 'sans'),
            new Font('JetBrains Mono', [400], ['normal'], true, 'mono'),
        ];

        $collection = new FontCollection($fonts);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('$font-family-ubuntu:', $output);
        $this->assertStringContainsString('$font-family-sans:', $output);
        $this->assertStringContainsString('$font-family-mono:', $output);
        $this->assertStringContainsString('!default', $output);
    }

    public function testExportWithFontWeights(): void
    {
        $font = new Font('Ubuntu', [300, 400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('$font-weight-light: 300', $output);
        $this->assertStringContainsString('$font-weight-normal: 400', $output);
        $this->assertStringContainsString('$font-weight-bold: 700', $output);
    }

    public function testUsageInstructions(): void
    {
        $instructions = $this->exporter->getUsageInstructions();

        $this->assertStringContainsString('@import', $instructions);
        $this->assertStringContainsString('$font-family-sans', $instructions);
    }
}
