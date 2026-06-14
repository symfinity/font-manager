<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Unit\Import;

use Symfinity\FontManager\Exception\InvalidFonttrioRegistryException;
use Symfinity\FontManager\Import\FonttrioPairingAdapter;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use PHPUnit\Framework\TestCase;

final class FonttrioPairingAdapterTest extends TestCase
{
    private string $fixtureDir;
    private FonttrioPairingAdapter $adapter;

    protected function setUp(): void
    {
        $this->fixtureDir = dirname(__DIR__, 2) . '/Fixtures/Fonttrio';
        $client = new FonttrioRegistryClient(null, $this->fixtureDir);
        $this->adapter = new FonttrioPairingAdapter($client);
    }

    public function testImportEditorialFixtureResolvesThreeFontsAndRoles(): void
    {
        $result = $this->adapter->import($this->fixtureDir . '/editorial.json');

        self::assertSame('editorial', $result->getId());
        self::assertCount(3, $result->getFonts());
        self::assertArrayHasKey('playfair-display', $result->getFonts());
        self::assertArrayHasKey('source-serif-4', $result->getFonts());
        self::assertArrayHasKey('jetbrains-mono', $result->getFonts());

        self::assertSame([
            'body' => 'source-serif-4',
            'heading' => 'playfair-display',
            'mono' => 'jetbrains-mono',
        ], $result->getRoles());

        self::assertSame('Playfair Display Variable', $result->getFonts()['playfair-display']['family']);
        self::assertSame('--font-playfair-display', $result->getFonts()['playfair-display']['css_variable']);
    }

    public function testImportRejectsInvalidType(): void
    {
        $path = sys_get_temp_dir() . '/fonttrio-invalid-type-' . uniqid() . '.json';
        file_put_contents($path, json_encode([
            'name' => 'bad',
            'type' => 'registry:font',
            'registryDependencies' => [],
            'cssVars' => ['theme' => ['--font-body' => 'var(--font-x)']],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->adapter->import($path);
            self::fail('Expected InvalidFonttrioRegistryException');
        } catch (InvalidFonttrioRegistryException $exception) {
            self::assertStringContainsString('registry:style', $exception->getMessage());
        } finally {
            @unlink($path);
        }
    }

    public function testImportRejectsNonGoogleProvider(): void
    {
        $stylePath = sys_get_temp_dir() . '/fonttrio-style-' . uniqid() . '.json';
        $fontPath = sys_get_temp_dir() . '/fonttrio-font-' . uniqid() . '.json';

        file_put_contents($fontPath, json_encode([
            'name' => 'custom-font',
            'type' => 'registry:font',
            'font' => [
                'family' => 'Custom',
                'provider' => 'bunny',
                'import' => 'Custom',
                'variable' => '--font-custom',
                'weight' => [400],
            ],
        ], JSON_THROW_ON_ERROR));

        file_put_contents($stylePath, json_encode([
            'name' => 'bad-provider',
            'type' => 'registry:style',
            'registryDependencies' => [$fontPath],
            'cssVars' => ['theme' => ['--font-body' => 'var(--font-custom)']],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->adapter->import($stylePath);
            self::fail('Expected InvalidFonttrioRegistryException');
        } catch (InvalidFonttrioRegistryException $exception) {
            self::assertStringContainsString('unsupported provider', strtolower($exception->getMessage()));
        } finally {
            @unlink($stylePath);
            @unlink($fontPath);
        }
    }

    public function testImportReportsBrokenDependencyUrl(): void
    {
        $stylePath = sys_get_temp_dir() . '/fonttrio-broken-' . uniqid() . '.json';
        file_put_contents($stylePath, json_encode([
            'name' => 'broken-deps',
            'type' => 'registry:style',
            'registryDependencies' => ['/path/does/not/exist/font.json'],
            'cssVars' => ['theme' => ['--font-body' => 'var(--font-x)']],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->adapter->import($stylePath);
            self::fail('Expected InvalidFonttrioRegistryException');
        } catch (InvalidFonttrioRegistryException $exception) {
            self::assertStringContainsString('Broken dependency URL', $exception->getMessage());
        } finally {
            @unlink($stylePath);
        }
    }
}
