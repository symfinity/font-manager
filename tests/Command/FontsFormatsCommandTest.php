<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsFormatsCommand;
use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Exporter\Scss\ScssVariablesExporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FontsFormatsCommandTest extends TestCase
{
    public function testExecuteListsFormats(): void
    {
        $registry = new ExporterRegistry();
        $registry->register(new CssVariablesExporter());
        $registry->register(new ScssVariablesExporter());

        $command = new FontsFormatsCommand($registry);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('css_variables', $output);
        $this->assertStringContainsString('scss_variables', $output);
        $this->assertStringContainsString('CSS Custom Properties', $output);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteShowsCategories(): void
    {
        $registry = new ExporterRegistry();
        $registry->register(new CssVariablesExporter());

        $command = new FontsFormatsCommand($registry);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('CSS', $output);
    }
}
