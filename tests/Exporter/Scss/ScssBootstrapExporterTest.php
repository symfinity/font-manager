<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\Scss;

use Symfinity\FontManager\Exporter\Scss\ScssBootstrapExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class ScssBootstrapExporterTest extends TestCase
{
    private ScssBootstrapExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new ScssBootstrapExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('scss_bootstrap', $this->exporter->getName());
    }

    public function testExportBootstrapVariables(): void
    {
        $fonts = [
            new Font('Ubuntu', [300, 400, 700], ['normal'], false, 'sans'),
            new Font('JetBrains Mono', [400], ['normal'], true, 'mono'),
        ];

        $collection = new FontCollection($fonts);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('$font-family-base:', $output);
        $this->assertStringContainsString('$font-family-monospace:', $output);
        $this->assertStringContainsString('$headings-font-weight:', $output);
        $this->assertStringContainsString('$line-height-base:', $output);
    }

    public function testExportWithoutMonospace(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('$font-family-base:', $output);
        $this->assertStringContainsString('Bootstrap Integration', $output);
    }
}
