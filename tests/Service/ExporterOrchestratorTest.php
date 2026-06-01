<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use Symfinity\FontManager\Service\ExporterOrchestrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ExporterOrchestratorTest extends TestCase
{
    private ExporterRegistry $registry;
    private ExporterOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->registry = new ExporterRegistry();
        $this->registry->register(new CssVariablesExporter());

        $this->orchestrator = new ExporterOrchestrator($this->registry, new Filesystem());
    }

    public function testValidateDependencies(): void
    {
        $result = $this->orchestrator->validateDependencies(['css_variables']);

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('invalid', $result);
        $this->assertContains('css_variables', $result['valid']);
        $this->assertEmpty($result['invalid']);
    }

    public function testValidateDependenciesDetectsMissing(): void
    {
        $result = $this->orchestrator->validateDependencies(['nonexistent']);

        $this->assertArrayHasKey('invalid', $result);
        $this->assertArrayHasKey('nonexistent', $result['invalid']);
    }

    public function testExportDryRun(): void
    {
        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);

        $results = $this->orchestrator->export(
            $collection,
            ['css_variables'],
            sys_get_temp_dir(),
            BuildToolType::ASSET_MAPPER,
            false // dry run
        );

        $this->assertCount(1, $results);
        $this->assertSame('css_variables', $results[0]['exporter']);
        $this->assertFalse($results[0]['written']);
        $this->assertGreaterThan(0, $results[0]['size']);
    }
}
