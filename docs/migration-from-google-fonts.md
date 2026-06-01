# Migration from neuralglitch/google-fonts

There is **no** `symfinity/google-fonts` package. **`symfinity/font-manager`** is the sole Symfinity successor.

## Automated migration

If the app still uses `neuralglitch/google-fonts`, install font-manager and run:

```bash
composer remove neuralglitch/google-fonts
composer require symfinity/font-manager
php bin/console fonts:migrate-from-google-fonts
```

The command rewrites `config/packages/google_fonts.yaml` → `font_manager:` semantics and updates Twig usage where applicable. Use `--dry-run` first.

## Manual mapping

| Legacy (`google-fonts`) | Symfinity (`font-manager`) |
|-------------------------|----------------------------|
| `google_fonts:` config root | `font_manager:` |
| Google CDN wiring | `GoogleFontsProvider` (default) or privacy providers (Bunny, Fontsource) |
| Twig helpers | `font_manager()` |

## Package identity

| Item | Legacy | Symfinity |
|------|--------|-----------|
| Composer name | `neuralglitch/google-fonts` | `symfinity/font-manager` |
| G5 `replacement=` | — | `symfinity/font-manager` |

## Abandon timeline

Abandon `neuralglitch/google-fonts` only after `symfinity/font-manager` passes wave-1 gate **G2** (Packagist + Flex recipe).
