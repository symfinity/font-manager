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

## See also

- [Quickstart](quickstart.md)
- [CHANGELOG](../CHANGELOG.md)
