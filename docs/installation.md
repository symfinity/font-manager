# Installation

## Prerequisites

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint to your project's `composer.json` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md)).

## Composer

```bash
composer require symfinity/font-manager
```

## Symfony Flex

The recipe (`symfinity/recipes`, version folder `0.2` for `^0.2`, `0.1` for `^0.1`) applies:

- `config/packages/symfinity_font_manager.yaml` from the package default
- Bundle registration for **all** environments (`dev`, `test`, and `prod`)

Default config sets `use_locked_fonts: true` under `when@prod:` — run **`fonts:lock`** before deploy so production serves local assets instead of CDN.

## Production

1. Run `php bin/console fonts:lock` before deploy (or in your build pipeline).
2. Commit or ship the lock manifest and downloaded font files with your release.
3. Serve locked fonts from your asset pipeline — see [Local Fonts](local-fonts.md) and [Commands](commands.md).

If you installed before this recipe change and still have `FontManagerBundle` registered for `dev`/`test` only, update `config/bundles.php` to `['all' => true]`.

## Manual installation

When Flex is unavailable:

1. `composer require symfinity/font-manager`
2. Register `Symfinity\FontManager\FontManagerBundle` in `config/bundles.php`
3. Copy `config/packages/symfinity_font_manager.yaml` from the package into your project

## Verify installation

```bash
php bin/console fonts:status
```

## Next steps

[Quick start](quickstart.md).
