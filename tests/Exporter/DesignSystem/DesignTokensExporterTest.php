<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\DesignSystem;

use Symfinity\FontManager\Exporter\DesignSystem\DesignTokensExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class DesignTokensExporterTest extends TestCase
{
    private DesignTokensExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new DesignTokensExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('design_tokens', $this->exporter->getName());
    }

    public function testExportW3CFormat(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertJson($output);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('font', $data);
        $this->assertIsArray($data['font']);
        $this->assertArrayHasKey('family', $data['font']);
        $this->assertArrayHasKey('weight', $data['font']);
    }

    public function testExportW3CTokenStructure(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertIsArray($data['font']);
        $this->assertIsArray($data['font']['family']);
        $sansToken = $data['font']['family']['sans'];
        $this->assertIsArray($sansToken);

        $this->assertArrayHasKey('$value', $sansToken);
        $this->assertArrayHasKey('$type', $sansToken);
        $this->assertArrayHasKey('$description', $sansToken);
        $this->assertSame('fontFamily', $sansToken['$type']);
    }
}
