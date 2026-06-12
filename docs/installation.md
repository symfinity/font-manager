# Installation

## Prerequisites

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint to your project's `composer.json` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md)).

## Composer

```bash
composer require symfinity/font-manager
```

## Symfony Flex

The recipe (`symfinity/recipes`, version folder `0.2` for `^0.2`) applies:

- `config/packages/font_manager.yaml` from the package default
- Bundle registration for **`dev`** and **`test`** environments only

## Production

Font locking and local assets require the bundle in **production**:

1. Register the bundle for `prod` in `config/bundles.php`:

```php
return [
    // ...
    Symfinity\FontManager\FontManagerBundle::class => ['all' => true],
];
```

2. Run `php bin/console fonts:lock` before deploy (or in your build pipeline).
3. Serve locked fonts from your asset pipeline — see [Local Fonts](local-fonts.md) and [Commands](commands.md).

## Manual installation

When Flex is unavailable:

1. `composer require symfinity/font-manager`
2. Register `Symfinity\FontManager\FontManagerBundle` in `config/bundles.php`
3. Copy `config/packages/font_manager.yaml` from the package into your project

## Verify installation

```bash
php bin/console fonts:status
```

## Next steps

[Quick start](quickstart.md).
