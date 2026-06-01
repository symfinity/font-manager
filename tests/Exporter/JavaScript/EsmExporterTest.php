<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\JavaScript\EsmExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class EsmExporterTest extends TestCase
{
    private EsmExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new EsmExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('esm_javascript', $this->exporter->getName());
    }

    public function testExportEsmFormat(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal', 'italic'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('export const fontFamilies', $output);
        $this->assertStringContainsString('export const fontWeights', $output);
        $this->assertStringContainsString('export const fonts', $output);
        $this->assertStringContainsString('export function getFont', $output);
        $this->assertStringContainsString('export default', $output);
    }

    public function testExportIncludesHelperFunctions(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('getFontFamily', $output);
        $this->assertStringContainsString('getFontWeights', $output);
    }
}
