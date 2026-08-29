<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\DesignSystem\JsonExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class JsonExporterTest extends TestCase
{
    private JsonExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new JsonExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('json', $this->exporter->getName());
    }

    public function testExportValidJson(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('fonts', $data);
        $this->assertArrayHasKey('variables', $data);
        $this->assertArrayHasKey('metadata', $data);
    }

    public function testExportIncludesCssAndScssVariables(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['variables']);
        $this->assertArrayHasKey('css', $data['variables']);
        $this->assertArrayHasKey('scss', $data['variables']);
    }

    public function testExportWithSemanticFonts(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['variables']);
        $this->assertIsArray($data['variables']['scss']);
        $this->assertArrayHasKey('$font-family-base', $data['variables']['scss']);
    }
}
