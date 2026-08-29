<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Tests\Unit\Import;

use PHPUnit\Framework\TestCase;
use Symfinity\FontManager\Exception\InvalidFonttrioRegistryException;
use Symfinity\FontManager\Import\FonttrioRegistryClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FonttrioRegistryClientTest extends TestCase
{
    public function testFetchRejectsDisallowedRemoteHost(): void
    {
        $client = new FonttrioRegistryClient();

        $this->expectException(InvalidFonttrioRegistryException::class);
        $this->expectExceptionMessage('disallowed host');

        // No fixture directory + non-allowlisted host: must be rejected before any HTTP request.
        $client->fetch('https://internal.example.com/secret.json');
    }

    public function testResolveUrlMapsFonttrioSlugToAllowedHost(): void
    {
        $client = new FonttrioRegistryClient();

        self::assertSame(
            'https://www.fonttrio.xyz/r/editorial.json',
            $client->resolveUrl('@fonttrio/editorial'),
        );
    }

    public function testCustomAllowlistPermitsConfiguredHost(): void
    {
        $mock = new MockHttpClient(new MockResponse((string) json_encode(['type' => 'registry:font'], JSON_THROW_ON_ERROR)));
        $client = new FonttrioRegistryClient($mock, null, ['fonts.internal.test']);

        $data = $client->fetch('https://fonts.internal.test/x.json');

        self::assertSame('registry:font', $data['type'] ?? null);
    }
}
