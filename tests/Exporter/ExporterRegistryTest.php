<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Exporter;

use Symfinity\FontManager\Exception\ExporterException;
use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use PHPUnit\Framework\TestCase;

final class ExporterRegistryTest extends TestCase
{
    private ExporterRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ExporterRegistry();
    }

    public function testRegister(): void
    {
        $exporter = new CssVariablesExporter();
        $this->registry->register($exporter);

        $this->assertTrue($this->registry->has('css_variables'));
    }

    public function testGet(): void
    {
        $exporter = new CssVariablesExporter();
        $this->registry->register($exporter);

        $result = $this->registry->get('css_variables');

        $this->assertSame($exporter, $result);
    }

    public function testGetThrowsExceptionForUnknown(): void
    {
        $this->expectException(ExporterException::class);
        $this->expectExceptionMessage('Exporter "unknown" not found');

        $this->registry->get('unknown');
    }

    public function testHas(): void
    {
        $exporter = new CssVariablesExporter();
        $this->registry->register($exporter);

        $this->assertTrue($this->registry->has('css_variables'));
        $this->assertFalse($this->registry->has('unknown'));
    }

    public function testAll(): void
    {
        $exporter1 = new CssVariablesExporter();
        $this->registry->register($exporter1);

        $all = $this->registry->all();

        $this->assertCount(1, $all);
        $this->assertArrayHasKey('css_variables', $all);
    }

    public function testGetNames(): void
    {
        $exporter = new CssVariablesExporter();
        $this->registry->register($exporter);

        $names = $this->registry->getNames();

        $this->assertContains('css_variables', $names);
    }
}
