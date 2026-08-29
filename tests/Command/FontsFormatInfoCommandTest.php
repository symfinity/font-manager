<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsFormatInfoCommand;
use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FontsFormatInfoCommandTest extends TestCase
{
    public function testExecuteShowsFormatInfo(): void
    {
        $registry = new ExporterRegistry();
        $registry->register(new CssVariablesExporter());

        $command = new FontsFormatInfoCommand($registry);
        $tester = new CommandTester($command);
        $tester->execute(['format' => 'css_variables']);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('CSS Custom Properties', $output);
        $this->assertStringContainsString('Usage Instructions', $output);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithUnknownFormat(): void
    {
        $registry = new ExporterRegistry();

        $command = new FontsFormatInfoCommand($registry);
        $tester = new CommandTester($command);
        $tester->execute(['format' => 'unknown']);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('not found', $output);
        $this->assertSame(1, $tester->getStatusCode());
    }

    public function testExecuteWithInvalidArgument(): void
    {
        $registry = new ExporterRegistry();

        $command = new FontsFormatInfoCommand($registry);
        $tester = new CommandTester($command);
        $tester->execute(['format' => null]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('Invalid format name', $output);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
