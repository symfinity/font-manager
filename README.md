<div align="center">

# Font Manager

### Universal font manager for Symfony supporting multiple providers

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-7.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

---

## Documentation

| Topic | Page |
|-------|------|
| Commands | [docs/commands.md](docs/commands.md) |
| Configuration | [docs/configuration.md](docs/configuration.md) |
| Exporter Policy | [docs/exporter-policy.md](docs/exporter-policy.md) |
| Exports | [docs/exports.md](docs/exports.md) |
| Index | [docs/index.md](docs/index.md) |
| Installation | [docs/installation.md](docs/installation.md) |
| Local Fonts | [docs/local-fonts.md](docs/local-fonts.md) |
| M4 Public Release Checklist | [docs/m4-public-release-checklist.md](docs/m4-public-release-checklist.md) |
| Migration From Google Fonts | [docs/migration-from-google-fonts.md](docs/migration-from-google-fonts.md) |
| Migration | [docs/migration.md](docs/migration.md) |
| Performance | [docs/performance.md](docs/performance.md) |
| Providers | [docs/providers.md](docs/providers.md) |
| Quickstart | [docs/quickstart.md](docs/quickstart.md) |
| Reference | [docs/reference.md](docs/reference.md) |
| Troubleshooting | [docs/troubleshooting.md](docs/troubleshooting.md) |
| Upgrade | [docs/upgrade.md](docs/upgrade.md) |
| Usage | [docs/usage.md](docs/usage.md) |

## Requirements

- PHP 8.2+
- Symfony 6.4+ (Flex recipe when available)

## Install

```bash
composer require symfinity/font-manager
```

## Features

- **Multiple Providers** — Google Fonts, Bunny Fonts, Fontsource, and Local Fonts
- **Privacy-Friendly** — GDPR-compliant options (Bunny Fonts, Fontsource)
- **Development Mode** — CDN with inline styles
- **Production Mode** — Lock fonts locally for performance and privacy
- **Multi-Format Export** — 13 optional export formats (CSS variables default in Flex recipe)
- **CLI Tools** — Search, lock, validate, prune, migrate-from-google-fonts, export
- **Twig** — `font_manager()` helper
- **Type-Safe** — PHP 8.2+ enums and strict types
