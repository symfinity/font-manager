<div align="center">

# Font Manager

### Universal font manager for Symfony supporting multiple providers

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-7.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

## Features

- **Multiple Providers** — Google Fonts, Bunny Fonts, Fontsource, and Local Fonts
- **Privacy-Friendly** — GDPR-compliant options (Bunny Fonts, Fontsource)
- **Development Mode** — CDN with inline styles
- **Production Mode** — Lock fonts locally for performance and privacy
- **Multi-Format Export** — 13 optional export formats (CSS variables default in Flex recipe)
- **CLI Tools** — Search, lock, validate, prune, migrate-from-google-fonts, export
- **Twig** — `font_manager()` helper
- **Type-Safe** — PHP 8.2+ enums and strict types

## Supported Providers

| Provider | Privacy | API Key | Notes |
|----------|---------|---------|-------|
| **Google Fonts** | Tracks users | Optional (search) | Default in Symfinity Flex recipe |
| **Bunny Fonts** | GDPR-friendly | No | Enable in config when needed |
| **Fontsource** | Self-hosted CDN | No | Enable in config when needed |
| **Local Fonts** | Full control | No | Custom `@font-face` |

## Installation

```bash
composer require symfinity/font-manager
```

Flex recipe sources live in the [symfinity product monorepo](https://github.com/symfinity/symfinity) under `recipes/symfinity/font-manager/` (published via `symfinity/recipes` at first public release — see [M4 checklist](docs/m4-public-release-checklist.md)).

## Quick Start

### 1. Add fonts to your template

```twig
{# templates/base.html.twig #}
<head>
  {{ font_manager('Roboto', '400 700', 'normal') }}
</head>
```

### 2. Lock fonts for production

```bash
php bin/console fonts:lock
```

Downloads fonts to `assets/fonts/` and exports enabled formats (default: `css_variables` only — see [Exporter policy](docs/exporter-policy.md)).

Production uses locked fonts when `use_locked_fonts: true` (Flex recipe sets this under `when@prod`).

### 3. Optional: extra export formats

```yaml
# config/packages/font_manager.yaml
font_manager:
  export:
    formats:
      - css_variables
      - scss_bootstrap
      - tailwind_config
```

See [Export formats](docs/exports.md) for the full list. Symfinity recipe ships **`css_variables` only**.

## Configuration

Default Symfinity install (Flex recipe):

```yaml
font_manager:
    default_provider: google
    export:
        formats:
            - css_variables
```

For all options see [Configuration Guide](docs/configuration.md) and [Exporter policy](docs/exporter-policy.md).

## Migration

| From | Guide |
|------|--------|
| `neuralglitch/font-manager` | [docs/migration.md](docs/migration.md) |
| `neuralglitch/google-fonts` | [docs/migration-from-google-fonts.md](docs/migration-from-google-fonts.md) (+ `fonts:migrate-from-google-fonts`) |

Do not abandon legacy Packagist packages until `symfinity/font-manager` is installable (wave-1 gate G5).

## Documentation

- **[Exporter policy](docs/exporter-policy.md)** — DI registration vs enabled formats (Symfinity default)
- **[Export formats](docs/exports.md)** — Multi-format export guide
- **[Usage](docs/usage.md)** — Twig function parameters
- **[Providers](docs/providers.md)** — Provider comparison
- **[Commands](docs/commands.md)** — CLI reference
- **[Configuration](docs/configuration.md)** — Full config tree
- **[Local fonts](docs/local-fonts.md)** — Self-hosted fonts
- **[Performance](docs/performance.md)** — Resource hints and fallbacks

## Requirements

- PHP 8.2+
- Symfony 7.4+
- Twig 3.0+

## License

[MIT](LICENSE)
