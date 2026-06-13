# Upgrade and migration

## 0.2.2

No breaking changes. Patch release: expanded split-mirror CI matrix and handbook cleanup only.

```bash
composer update symfinity/font-manager
```

## 0.2.1 (Symfinity relocation)

**Package rename:** `neuralglitch/font-manager` → `symfinity/font-manager`

**Namespace:** `NeuralGlitch\FontManager\` → `Symfinity\FontManager\`

### Migration steps

1. Remove the old package and require the successor:

```bash
composer remove neuralglitch/font-manager
composer require symfinity/font-manager:^0.2
```

2. Update Flex endpoint if needed — see [Installation](installation.md).
3. Replace namespace imports in PHP and service config:

```php
// Before
use NeuralGlitch\FontManager\FontManagerBundle;

// After
use Symfinity\FontManager\FontManagerBundle;
```

4. Twig function `font_manager()` and YAML config keys are unchanged.
5. Run `php bin/console fonts:status` to verify.

The bundle still **replaces** `neuralglitch/font-manager` and `neuralglitch/google-fonts` in Composer — see [Migration from google-fonts](migration-from-google-fonts.md) for the google-fonts successor path.

## 0.2.0

Adds multi-format export (`fonts:export`, `fonts:formats`), build-tool auto-detection, and extended `build` / `export` configuration. See [Export Formats](exports.md).

`fonts:lock` now auto-exports configured formats unless you pass `--no-export`.

## 0.1.x → 0.2.x

If you still run `0.1.x` under `neuralglitch/font-manager`, upgrade through **0.2.1** above first, then `composer update` to the latest `^0.2`.

## See also

- [Configuration](configuration.md)
- [Migration](migration.md)
- [CHANGELOG](../CHANGELOG.md)
