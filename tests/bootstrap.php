<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — single Composer autoload only.
 *
 * Monorepo: project-root vendor (mono qa:test). Split mirror: package vendor only.
 *
 * Optional package hook: tests/bootstrap.local.php (KERNEL_CLASS, Dotenv, …).
 */

$packageRoot = dirname(__DIR__);
$monorepoRoot = dirname($packageRoot, 2);
$monorepoAutoload = $monorepoRoot.'/vendor/autoload.php';

if (is_file($monorepoRoot.'/mono.json') && is_file($monorepoAutoload)) {
    if (!class_exists(\Composer\Autoload\ClassLoader::class, false)) {
        require $monorepoAutoload;
    }
} elseif (is_file($packageRoot.'/vendor/autoload.php')) {
    require $packageRoot.'/vendor/autoload.php';
} else {
    fwrite(STDERR, "Composer autoload not found — run composer install in the package or monorepo.\n");
    exit(1);
}

$localBootstrap = __DIR__.'/bootstrap.local.php';
if (is_file($localBootstrap)) {
    require $localBootstrap;
}
