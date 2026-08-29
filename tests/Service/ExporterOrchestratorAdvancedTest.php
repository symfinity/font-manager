<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Service;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Exporter\Css\CssModulesExporter;
use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use Symfinity\FontManager\Service\ExporterOrchestrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ExporterOrchestratorAdvancedTest extends TestCase
{
    private ExporterRegistry $registry;
    private ExporterOrchestrator $orchestrator;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->registry = new ExporterRegistry();
        $this->filesystem = new Filesystem();
        $this->orchestrator = new ExporterOrchestrator($this->registry, $this->filesystem);
    }

    public function testResolveDependenciesInCorrectOrder(): void
    {
        // CssModules depends on CssVariables
        $this->registry->register(new CssVariablesExporter());
        $this->registry->register(new CssModulesExporter());

        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);

        $results = $this->orchestrator->export(
            $collection,
            ['css_modules', 'css_variables'],  // Wrong order in input
            sys_get_temp_dir(),
            BuildToolType::ASSET_MAPPER,
            false
        );

        // Should be reordered: css_variables first, then css_modules
        $this->assertCount(2, $results);
        $this->assertSame('css_variables', $results[0]['exporter']);
        $this->assertSame('css_modules', $results[1]['exporter']);
    }

    public function testValidateDependenciesWithValid(): void
    {
        $this->registry->register(new CssVariablesExporter());
        $this->registry->register(new CssModulesExporter());

        $result = $this->orchestrator->validateDependencies(['css_variables', 'css_modules']);

        $this->assertArrayHasKey('valid', $result);
        $this->assertContains('css_variables', $result['valid']);
        $this->assertContains('css_modules', $result['valid']);
    }

    public function testValidateDependenciesWithMissing(): void
    {
        $this->registry->register(new CssModulesExporter());  // Has dependency on css_variables

        $result = $this->orchestrator->validateDependencies(['css_modules']);

        $this->assertArrayHasKey('invalid', $result);
        $this->assertArrayHasKey('css_modules', $result['invalid']);
        $this->assertContains('css_variables', $result['invalid']['css_modules']);
    }

    public function testExportWritesFiles(): void
    {
        $this->registry->register(new CssVariablesExporter());

        $tempDir = sys_get_temp_dir() . '/font-manager-test-' . uniqid();
        $this->filesystem->mkdir($tempDir);

        $font = new Font('Ubuntu', [400], ['normal'], false, 'sans');
        $collection = new FontCollection([$font]);

        $results = $this->orchestrator->export(
            $collection,
            ['css_variables'],
            $tempDir,
            BuildToolType::ASSET_MAPPER,
            true  // Write files
        );

        $this->assertTrue($results[0]['written']);
        $this->assertFileExists($results[0]['path']);

        // Cleanup
        $this->filesystem->remove($tempDir);
    }
}
