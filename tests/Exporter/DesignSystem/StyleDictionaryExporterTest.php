<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\DesignSystem\StyleDictionaryExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class StyleDictionaryExporterTest extends TestCase
{
    private StyleDictionaryExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new StyleDictionaryExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('style_dictionary', $this->exporter->getName());
    }

    public function testExportStyleDictionaryFormat(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('module.exports', $output);
        $this->assertStringContainsString('font:', $output);
        $this->assertStringContainsString('family:', $output);
        $this->assertStringContainsString('weight:', $output);
    }

    public function testExportIncludesComments(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('comment:', $output);
    }
}
