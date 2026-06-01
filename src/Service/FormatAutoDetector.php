<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Service;

use Symfony\Component\Filesystem\Filesystem;

final class FormatAutoDetector
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem()
    ) {
    }

    /**
     * Auto-detect required export formats based on project setup.
     *
     * @return string[]
     */
    public function detect(string $projectDir): array
    {
        $formats = ['css_variables'];  // Always include CSS variables as baseline

        // Check for Bootstrap
        if ($this->hasBootstrap($projectDir)) {
            $formats[] = 'scss_bootstrap';
        }

        // Check for Tailwind
        if ($this->hasTailwind($projectDir)) {
            $formats[] = 'tailwind_config';
        }

        // Check for TypeScript
        if ($this->hasTypeScript($projectDir)) {
            $formats[] = 'typescript_definitions';
            $formats[] = 'esm_javascript';  // TypeScript needs ESM
        }

        // Check for SCSS (but not Bootstrap)
        if ($this->hasScss($projectDir) && !in_array('scss_bootstrap', $formats, true)) {
            $formats[] = 'scss_variables';
        }

        return array_unique($formats);
    }

    private function hasBootstrap(string $projectDir): bool
    {
        // Check composer.json for Bootstrap
        $composerPath = $projectDir . '/composer.json';
        if ($this->filesystem->exists($composerPath)) {
            $content = file_get_contents($composerPath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['require'])) {
                    if (is_array($decoded['require'])) {
                        // Check for Bootstrap (via Composer or frontend-wizard)
                        if (isset($decoded['require']['twbs/bootstrap'])) {
                            return true;
                        }
                    }
                }
            }
        }

        // Check package.json for Bootstrap
        $packagePath = $projectDir . '/package.json';
        if ($this->filesystem->exists($packagePath)) {
            $content = file_get_contents($packagePath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $deps = array_merge(
                        $decoded['dependencies'] ?? [],
                        $decoded['devDependencies'] ?? []
                    );
                    if (isset($deps['bootstrap'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function hasTailwind(string $projectDir): bool
    {
        // Check for tailwind.config.js
        if ($this->filesystem->exists($projectDir . '/tailwind.config.js')
            || $this->filesystem->exists($projectDir . '/tailwind.config.ts')
        ) {
            return true;
        }

        // Check package.json
        $packagePath = $projectDir . '/package.json';
        if ($this->filesystem->exists($packagePath)) {
            $content = file_get_contents($packagePath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $deps = array_merge(
                        $decoded['dependencies'] ?? [],
                        $decoded['devDependencies'] ?? []
                    );
                    if (isset($deps['tailwindcss'])) {
                        return true;
                    }
                }
            }
        }

        // Check composer.json for Tailwind (via symfonycasts/tailwind-bundle or frontend-wizard)
        $composerPath = $projectDir . '/composer.json';
        if ($this->filesystem->exists($composerPath)) {
            $content = file_get_contents($composerPath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['require']) && is_array($decoded['require'])) {
                    if (isset($decoded['require']['symfonycasts/tailwind-bundle'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function hasTypeScript(string $projectDir): bool
    {
        // Check for tsconfig.json
        if ($this->filesystem->exists($projectDir . '/tsconfig.json')) {
            return true;
        }

        // Check package.json for TypeScript
        $packagePath = $projectDir . '/package.json';
        if ($this->filesystem->exists($packagePath)) {
            $content = file_get_contents($packagePath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $deps = array_merge(
                        $decoded['dependencies'] ?? [],
                        $decoded['devDependencies'] ?? []
                    );
                    if (isset($deps['typescript'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function hasScss(string $projectDir): bool
    {
        // Check for SCSS files in assets
        if ($this->filesystem->exists($projectDir . '/assets/styles')) {
            try {
                // Check for .scss files
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $projectDir . '/assets/styles',
                        \RecursiveDirectoryIterator::SKIP_DOTS
                    )
                );

                foreach ($iterator as $file) {
                    if ($file instanceof \SplFileInfo && $file->isFile() && 'scss' === $file->getExtension()) {
                        return true;
                    }
                }
            } catch (\UnexpectedValueException) {
                // Directory not accessible, skip
            }
        }

        // Check composer.json for Sass bundle
        $composerPath = $projectDir . '/composer.json';
        if ($this->filesystem->exists($composerPath)) {
            $content = file_get_contents($composerPath);
            if (false !== $content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['require']) && is_array($decoded['require'])) {
                    if (isset($decoded['require']['symfonycasts/sass-bundle'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
