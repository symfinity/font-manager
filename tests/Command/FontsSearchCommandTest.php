<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsSearchCommand;
use Symfinity\FontManager\Provider\GoogleFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FontsSearchCommandTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear provider cache before each test
        GoogleFontsProvider::clearCache();
    }

    public function testExecuteSearchesForFonts(): void
    {
        $jsonData = json_encode([
            'items' => [
                ['family' => 'Roboto', 'variants' => ['regular', '700'], 'category' => 'sans-serif'],
                ['family' => 'Robot Condensed', 'variants' => ['regular'], 'category' => 'sans-serif'],
            ],
        ]);
        $mockResponse = new MockResponse(false !== $jsonData ? $jsonData : '{}');

        $httpClient = new MockHttpClient($mockResponse);
        $googleProvider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $command = new FontsSearchCommand($registry);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'Robot']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Roboto', $output);
        self::assertStringContainsString('sans-serif', $output);
    }

    public function testExecuteRespectsLimitOption(): void
    {
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = ['family' => "Font {$i}", 'variants' => ['regular'], 'category' => 'sans-serif'];
        }

        $mockResponse = new MockResponse((string) json_encode(['items' => $items]));
        $httpClient = new MockHttpClient($mockResponse);
        $googleProvider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $command = new FontsSearchCommand($registry);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'Font', '--limit' => '5']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Found 5 fonts', $output);
    }

    public function testExecuteHandlesNoResults(): void
    {
        $mockResponse = new MockResponse((string) json_encode(['items' => []]));
        $httpClient = new MockHttpClient($mockResponse);
        $googleProvider = new GoogleFontsProvider($httpClient, ['api_key' => 'test-key']);

        $registry = new ProviderRegistry('google');
        $registry->registerProvider($googleProvider);

        $command = new FontsSearchCommand($registry);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['query' => 'NonExistent']);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('No fonts found', $output);
    }
}
