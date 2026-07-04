# Verification

**Integration profile:** P1 — head hook (`font_manager()` in layout).

## Local commands

```bash
composer test
composer phpstan
php bin/console debug:config symfinity_font_manager
```

## Clean-app smoke

```bash
composer require symfinity/font-manager
```

Call `{{ font_manager() }}` in `<head>` — expect `@font-face` or provider link tags without manual asset paths.

Org maintainer dogfood:

```bash
make dogfood-new SLUG=ui-lab VERSION='7.4.*' FORCE=1
make dogfood-serve SLUG=ui-lab
# GET /fonts
```

## See also

- [Quickstart](quickstart.md)
- [CHANGELOG](../CHANGELOG.md)
