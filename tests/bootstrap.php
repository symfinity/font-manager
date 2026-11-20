<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — monorepo Composer autoload, plus this package's own PSR-4.
 *
 * Path packages do not publish autoload-dev into the root vendor dump, and a
 * package that is not in the root require is still executed by qa:test.
 *
 * Optional package hook: tests/bootstrap.local.php (KERNEL_CLASS, Dotenv, …).
 */

$packageRoot = dirname(__DIR__);
$monorepoRoot = dirname($packageRoot, 2);
$monorepoAutoload = $monorepoRoot.'/vendor/autoload.php';
$autoload = null;

if (is_file($monorepoRoot.'/mono.json') && is_file($monorepoAutoload)) {
    $autoload = $monorepoAutoload;
} elseif (is_file($packageRoot.'/vendor/autoload.php')) {
    $autoload = $packageRoot.'/vendor/autoload.php';
}

if ($autoload === null) {
    fwrite(STDERR, "Composer autoload not found — run composer install in the package or monorepo.\n");
    exit(1);
}

$loader = require $autoload;
if ($loader instanceof Composer\Autoload\ClassLoader && is_file($packageRoot.'/composer.json')) {
    $composer = json_decode((string) file_get_contents($packageRoot.'/composer.json'), true);
    if (is_array($composer)) {
        foreach (['autoload', 'autoload-dev'] as $section) {
            $sectionConfig = $composer[$section] ?? null;
            if (!is_array($sectionConfig)) {
                continue;
            }
            $psr4 = $sectionConfig['psr-4'] ?? null;
            if (!is_array($psr4)) {
                continue;
            }
            foreach ($psr4 as $prefix => $relativePaths) {
                $paths = is_array($relativePaths) ? $relativePaths : [$relativePaths];
                foreach ($paths as $relativePath) {
                    if (!is_string($prefix) || !is_string($relativePath)) {
                        continue;
                    }
                    $loader->addPsr4($prefix, $packageRoot.'/'.trim($relativePath, '/').'/');
                }
            }
        }
    }
}

$localBootstrap = __DIR__.'/bootstrap.local.php';
if (is_file($localBootstrap)) {
    require $localBootstrap;
}
