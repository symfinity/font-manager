<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

use Symfinity\FontManager\Exception\InvalidFonttrioRegistryException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class FonttrioRegistryClient
{
    public const DEFAULT_BASE_URL = 'https://www.fonttrio.xyz/r/';

    /** @var list<string> */
    public const DEFAULT_ALLOWED_HOSTS = ['www.fonttrio.xyz', 'fonttrio.xyz'];

    /** @var list<string> */
    private array $allowedHosts;

    /**
     * @param list<string> $allowedHosts hosts permitted for remote registry fetches (SSRF guard)
     */
    public function __construct(
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly ?string $fixtureDirectory = null,
        array $allowedHosts = self::DEFAULT_ALLOWED_HOSTS,
    ) {
        $this->allowedHosts = array_values(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            $allowedHosts,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $source): array
    {
        $url = $this->resolveUrl($source);

        if ($this->isLocalSource($url)) {
            return $this->loadLocal($url);
        }

        $fixturePath = $this->resolveFixturePath($url);
        if (null !== $fixturePath) {
            return $this->loadLocal($fixturePath);
        }

        $this->assertRemoteHostAllowed($url);

        $client = $this->httpClient ?? HttpClient::create();
        $response = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'symfinity/font-manager',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Failed to fetch Fonttrio registry item from %s (HTTP %d).',
                $url,
                $statusCode
            ));
        }

        $content = $response->getContent(false);
        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Invalid JSON from Fonttrio registry URL: %s',
                $url
            ));
        }

        return $data;
    }

    public function resolveUrl(string $source): string
    {
        $source = trim($source);

        if (str_starts_with($source, '@fonttrio/')) {
            $slug = substr($source, strlen('@fonttrio/'));

            return self::DEFAULT_BASE_URL . $slug . '.json';
        }

        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            return $source;
        }

        if ($this->filesystemPathExists($source)) {
            return $source;
        }

        throw new InvalidFonttrioRegistryException(sprintf(
            'Unsupported Fonttrio source "%s". Use @fonttrio/{slug}, an HTTPS registry URL, or a local fixture path.',
            $source
        ));
    }

    private function assertRemoteHostAllowed(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? strtolower($host) : '';

        if ('' === $host || !in_array($host, $this->allowedHosts, true)) {
            throw new InvalidFonttrioRegistryException(sprintf(
                'Refusing to fetch Fonttrio registry from disallowed host "%s". Allowed hosts: %s. Use @fonttrio/{slug} or a local fixture path for other sources.',
                '' !== $host ? $host : '(none)',
                implode(', ', $this->allowedHosts)
            ));
        }
    }

    private function isLocalSource(string $source): bool
    {
        return str_starts_with($source, '/') || preg_match('#^[A-Za-z]:\\\\#', $source) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLocal(string $path): array
    {
        if (!$this->filesystemPathExists($path)) {
            throw new InvalidFonttrioRegistryException(sprintf('Fonttrio fixture not found: %s', $path));
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new InvalidFonttrioRegistryException(sprintf('Failed to read Fonttrio fixture: %s', $path));
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new InvalidFonttrioRegistryException(sprintf('Invalid JSON in Fonttrio fixture: %s', $path));
        }

        return $data;
    }

    private function resolveFixturePath(string $url): ?string
    {
        if (null === $this->fixtureDirectory) {
            return null;
        }

        if (!str_contains($url, 'fonttrio.xyz/r/')) {
            return null;
        }

        $basename = basename(parse_url($url, PHP_URL_PATH) ?? '');
        if ('' === $basename) {
            return null;
        }

        $candidate = rtrim($this->fixtureDirectory, '/') . '/' . $basename;

        return $this->filesystemPathExists($candidate) ? $candidate : null;
    }

    private function filesystemPathExists(string $path): bool
    {
        return is_file($path);
    }
}
