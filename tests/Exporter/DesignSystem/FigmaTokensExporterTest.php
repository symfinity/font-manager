<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\DesignSystem\FigmaTokensExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class FigmaTokensExporterTest extends TestCase
{
    private FigmaTokensExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new FigmaTokensExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('figma_tokens', $this->exporter->getName());
    }

    public function testExportFigmaFormat(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('global', $data);
        $this->assertIsArray($data['global']);
        $this->assertArrayHasKey('fontFamilies', $data['global']);
        $this->assertArrayHasKey('fontWeights', $data['global']);
    }

    public function testExportFigmaWeightNames(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal']);
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['global']);
        $this->assertIsArray($data['global']['fontWeights']);

        $this->assertArrayHasKey('normal', $data['global']['fontWeights']);
        $this->assertArrayHasKey('bold', $data['global']['fontWeights']);
        $this->assertIsArray($data['global']['fontWeights']['normal']);
        $this->assertIsArray($data['global']['fontWeights']['bold']);
        $this->assertSame('Regular', $data['global']['fontWeights']['normal']['value']);
        $this->assertSame('Bold', $data['global']['fontWeights']['bold']['value']);
    }
}
