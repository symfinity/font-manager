<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\DependencyInjection;

use Symfinity\FontManager\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class PairingsConfigurationTest extends TestCase
{
    public function testPairingsCatalogRequiresSource(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [[
            'pairings' => [
                'catalog' => [
                    'editorial' => [
                        'label' => 'Editorial',
                    ],
                ],
            ],
        ]]);
    }

    public function testValidPairingsCatalogAccepted(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'pairings' => [
                'active' => 'editorial',
                'catalog' => [
                    'editorial' => [
                        'source' => '@fonttrio/editorial',
                        'label' => 'Editorial',
                    ],
                ],
                'active_roles' => [
                    'body' => 'source-serif-4',
                    'heading' => 'playfair-display',
                    'mono' => 'jetbrains-mono',
                ],
            ],
        ]]);

        self::assertSame('editorial', $config['pairings']['active']);
        self::assertSame('@fonttrio/editorial', $config['pairings']['catalog']['editorial']['source']);
    }
}
