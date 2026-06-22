<?php

declare(strict_types=1);

namespace Symfinity\FontManager\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class FontManagerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // General parameters
        $container->setParameter('font_manager.default_provider', $config['default_provider'] ?? 'google');
        $container->setParameter('font_manager.cache_ttl', $config['cache_ttl'] ?? 3600);
        $container->setParameter('font_manager.use_locked_fonts', $config['use_locked_fonts'] ?? false);
        $container->setParameter('font_manager.fonts_dir', $config['fonts_dir'] ?? '%kernel.project_dir%/assets/fonts');
        $container->setParameter(
            'font_manager.manifest_file',
            $config['manifest_file'] ?? '%kernel.project_dir%/var/font-manager.lock.json'
        );
        $container->setParameter('font_manager.unicode_subsets', $config['unicode_subsets'] ?? ['latin', 'latin-ext']);

        // Provider configurations
        $container->setParameter('font_manager.providers', $config['providers'] ?? []);
        $container->setParameter('font_manager.providers.google', $config['providers']['google'] ?? ['enabled' => true]);
        $container->setParameter('font_manager.providers.bunny', $config['providers']['bunny'] ?? ['enabled' => true]);
        $container->setParameter('font_manager.providers.fontsource', $config['providers']['fontsource'] ?? ['enabled' => true]);
        $container->setParameter('font_manager.providers.local', $config['providers']['local'] ?? ['enabled' => false]);

        // Build tool configuration
        $container->setParameter('font_manager.build.tool', $config['build']['tool'] ?? 'auto');

        // Export configuration
        $container->setParameter('font_manager.export.auto_detect', $config['export']['auto_detect'] ?? false);
        $container->setParameter('font_manager.export.formats', $config['export']['formats'] ?? ['css_variables']);
        $container->setParameter('font_manager.export.output', $config['export']['output'] ?? [
            'base_dir' => '%kernel.project_dir%',
            'fonts_dir' => 'auto',
            'styles_dir' => 'auto',
            'config_dir' => 'auto',
        ]);

        // Performance configuration
        $container->setParameter('font_manager.performance.resource_hints', $config['performance']['resource_hints'] ?? true);
        $container->setParameter('font_manager.performance.preload_critical_fonts', $config['performance']['preload_critical_fonts'] ?? false);
        $container->setParameter('font_manager.performance.font_loading_api', $config['performance']['font_loading_api'] ?? false);
        $container->setParameter('font_manager.performance.prefer_variable_fonts', $config['performance']['prefer_variable_fonts'] ?? true);
        $container->setParameter('font_manager.performance.intelligent_fallbacks', $config['performance']['intelligent_fallbacks'] ?? true);

        $fonts = $config['fonts'] ?? [];
        $container->setParameter('font_manager.fonts', is_array($fonts) ? $fonts : []);

        $pairings = $config['pairings'] ?? [];
        $container->setParameter('font_manager.pairings', is_array($pairings) ? $pairings : []);
    }

    public function getAlias(): string
    {
        return 'symfinity_font_manager';
    }
}
