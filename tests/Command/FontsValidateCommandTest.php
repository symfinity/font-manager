<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Command;

use Symfinity\FontManager\Command\FontsValidateCommand;
use Symfinity\FontManager\Provider\LocalFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;

final class FontsValidateCommandTest extends TestCase
{
    public function testExecuteWithNoErrors(): void
    {
        $config = [
            'directory' => '/tmp',
            'fonts' => [], // No fonts configured
        ];

        $httpClient = new MockHttpClient();
        $localProvider = new LocalFontsProvider($httpClient, $config);

        $registry = new ProviderRegistry();
        $registry->registerProvider($localProvider);

        $command = new FontsValidateCommand($registry);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('All local font files found', $output);
    }

    public function testExecuteWithMissingFiles(): void
    {
        $config = [
            'directory' => '/nonexistent',
            'fonts' => [
                'TestFont' => [
                    'weights' => [400],
                    'styles' => ['normal'],
                    'files' => [
                        '400-normal' => 'missing.woff2',
                    ],
                ],
            ],
        ];

        $httpClient = new MockHttpClient();
        $localProvider = new LocalFontsProvider($httpClient, $config);

        $registry = new ProviderRegistry();
        $registry->registerProvider($localProvider);

        $command = new FontsValidateCommand($registry);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('missing font files', $output);
    }
}
