<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter\JavaScript;

use Symfinity\FontManager\Exporter\JavaScript\TypeScriptExporter;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use PHPUnit\Framework\TestCase;

final class TypeScriptExporterTest extends TestCase
{
    private TypeScriptExporter $exporter;

    protected function setUp(): void
    {
        $this->exporter = new TypeScriptExporter();
    }

    public function testGetName(): void
    {
        $this->assertSame('typescript_definitions', $this->exporter->getName());
    }

    public function testGetFileExtension(): void
    {
        $this->assertSame('.d.ts', $this->exporter->getFileExtension());
    }

    public function testDependencies(): void
    {
        $deps = $this->exporter->getDependencies();

        $this->assertContains('esm_javascript', $deps);
    }

    public function testExportIncludesTypeDefinitions(): void
    {
        $font = new Font('Ubuntu', [400, 700], ['normal', 'italic'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('export interface Font', $output);
        $this->assertStringContainsString('export const fontFamilies', $output);
        $this->assertStringContainsString('export const fontWeights', $output);
        $this->assertStringContainsString('export const fonts', $output);
        $this->assertStringContainsString('export type FontFamily', $output);
        $this->assertStringContainsString('export type FontWeight', $output);
    }

    public function testExportIncludesFunctionSignatures(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);
        $output = $this->exporter->export($collection);

        $this->assertStringContainsString('export function getFont', $output);
        $this->assertStringContainsString('export function getFontFamily', $output);
        $this->assertStringContainsString('export function getFontWeights', $output);
    }
}
