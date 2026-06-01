<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\DependencyInjection;

use Symfinity\FontManager\DependencyInjection\FontManagerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FontManagerExtensionTest extends TestCase
{
    public function testLoadSetsDefaultParameters(): void
    {
        $container = new ContainerBuilder();
        $extension = new FontManagerExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasParameter('font_manager.default_provider'));
        self::assertTrue($container->hasParameter('font_manager.cache_ttl'));
        self::assertTrue($container->hasParameter('font_manager.use_locked_fonts'));
        self::assertTrue($container->hasParameter('font_manager.fonts_dir'));
        self::assertTrue($container->hasParameter('font_manager.manifest_file'));
    }

    public function testLoadSetsCustomParameters(): void
    {
        $container = new ContainerBuilder();
        $extension = new FontManagerExtension();

        $config = [
            'font_manager' => [
                'default_provider' => 'bunny',
                'cache_ttl' => 7200,
                'use_locked_fonts' => true,
            ],
        ];

        $extension->load($config, $container);

        self::assertSame('bunny', $container->getParameter('font_manager.default_provider'));
        self::assertSame(7200, $container->getParameter('font_manager.cache_ttl'));
        self::assertTrue($container->getParameter('font_manager.use_locked_fonts'));
    }

    public function testLoadSetsProviderParameters(): void
    {
        $container = new ContainerBuilder();
        $extension = new FontManagerExtension();

        $config = [
            'font_manager' => [
                'providers' => [
                    'google' => ['enabled' => true, 'api_key' => 'test-key'],
                    'bunny' => ['enabled' => true],
                    'fontsource' => ['enabled' => true],
                    'local' => ['enabled' => false],
                ],
            ],
        ];

        $extension->load($config, $container);

        $googleConfig = $container->getParameter('font_manager.providers.google');
        self::assertIsArray($googleConfig);
        self::assertTrue($googleConfig['enabled']);
        self::assertSame('test-key', $googleConfig['api_key']);

        $bunnyConfig = $container->getParameter('font_manager.providers.bunny');
        self::assertIsArray($bunnyConfig);
        self::assertTrue($bunnyConfig['enabled']);

        $fontsourceConfig = $container->getParameter('font_manager.providers.fontsource');
        self::assertIsArray($fontsourceConfig);
        self::assertTrue($fontsourceConfig['enabled']);
    }

    public function testGetAlias(): void
    {
        $extension = new FontManagerExtension();

        self::assertSame('font_manager', $extension->getAlias());
    }
}
