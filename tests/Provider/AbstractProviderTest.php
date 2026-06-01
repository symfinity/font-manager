<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Provider;

use Symfinity\FontManager\Enum\FontDisplay;
use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Provider\AbstractProvider;
use Symfinity\FontManager\Provider\FontProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class AbstractProviderTest extends TestCase
{
    public function testCacheWorks(): void
    {
        $provider = $this->createConcreteProvider();

        // Clear cache first
        AbstractProvider::clearCache();

        // Test cache is empty initially
        $reflection = new \ReflectionClass(AbstractProvider::class);
        $method = $reflection->getMethod('getFromCache');
        $method->setAccessible(true);

        $result = $method->invoke($provider, 'test-key');
        self::assertNull($result);

        // Put something in cache
        $putMethod = $reflection->getMethod('putInCache');
        $putMethod->setAccessible(true);
        $putMethod->invoke($provider, 'test-key', ['data' => 'value']);

        // Should retrieve from cache
        $cached = $method->invoke($provider, 'test-key');
        self::assertIsArray($cached);
        self::assertSame('value', $cached['data']);

        // Clear cache
        AbstractProvider::clearCache();
        $result = $method->invoke($provider, 'test-key');
        self::assertNull($result);
    }

    public function testSupports(): void
    {
        $provider = $this->createConcreteProvider();

        self::assertFalse($provider->supports(ProviderFeature::SEARCH));
        self::assertFalse($provider->supports(ProviderFeature::METADATA));
        self::assertTrue($provider->supports(ProviderFeature::CDN));
    }

    public function testRequiresAuth(): void
    {
        $provider = $this->createConcreteProvider();

        self::assertFalse($provider->requiresAuth());
    }

    public function testIsReady(): void
    {
        $provider = $this->createConcreteProvider();

        self::assertTrue($provider->isReady());
    }

    private function createConcreteProvider(): FontProviderInterface
    {
        return new class(new MockHttpClient()) extends AbstractProvider {
            public function getName(): string
            {
                return 'test';
            }

            public function searchFonts(string $query, int $maxResults = 20): array
            {
                return [];
            }

            public function downloadFontCss(string $fontName, array $weights, array $styles, FontDisplay $display = FontDisplay::SWAP): string
            {
                return '';
            }

            public function getFontMetadata(string $fontName): ?array
            {
                return null;
            }

            public function getFontVariants(string $fontName): array
            {
                return ['weights' => [400], 'styles' => ['normal']];
            }

            public function renderCdnLinks(string $fontName, array $weights, array $styles, FontDisplay $display = FontDisplay::SWAP): string
            {
                return '';
            }
        };
    }
}
