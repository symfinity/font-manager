<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Integration;

use Symfinity\FontManager\Exporter\Css\CssVariablesExporter;
use Symfinity\FontManager\Import\FonttrioPairingAdapter;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use Symfinity\FontManager\Import\PairingFontCollectionFactory;
use Symfinity\FontManager\Tests\FonttrioTestFixtures;
use PHPUnit\Framework\TestCase;

final class ImportPairingLockSmokeTest extends TestCase
{
    public function testImportThenExportEmitsOptionASemanticAliases(): void
    {
        $fixtureDir = FonttrioTestFixtures::directory();
        $client = new FonttrioRegistryClient(null, $fixtureDir);
        $adapter = new FonttrioPairingAdapter($client);
        $result = $adapter->import(FonttrioTestFixtures::EDITORIAL_SOURCE);

        $collection = (new PairingFontCollectionFactory())->fromImportResult($result);
        $css = (new CssVariablesExporter())->export($collection);

        self::assertStringContainsString('--font-family-sans: var(--font-source-serif-4);', $css);
        self::assertStringContainsString('--font-family-mono: var(--font-jetbrains-mono);', $css);
        self::assertStringContainsString('--font-heading: var(--font-playfair-display);', $css);
        self::assertStringContainsString("--font-playfair-display: 'Playfair Display Variable', serif;", $css);
    }

    public function testExportWithoutPairingRolesKeepsLegacySansAliasShape(): void
    {
        $exporter = new CssVariablesExporter();
        $collection = new \Symfinity\FontManager\Model\FontCollection([
            new \Symfinity\FontManager\Model\Font('Ubuntu', [400], ['normal'], false, 'sans'),
        ]);

        $css = $exporter->export($collection);

        self::assertStringContainsString('--font-family-sans: var(--font-family-ubuntu);', $css);
        self::assertStringNotContainsString('--font-heading:', $css);
    }
}
