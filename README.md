<div align="center">

# Font Manager

### Universal font manager for Symfony supporting multiple providers

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white)](composer.json)
[![Symfony](https://img.shields.io/badge/Symfony-6.4+-343434?style=flat&logo=symfony&logoColor=white)](composer.json)
<br/>
[![CI](https://github.com/symfinity/font-manager/actions/workflows/ci.yml/badge.svg)](https://github.com/symfinity/font-manager/actions/workflows/ci.yml)
<br/>
[![Release](https://img.shields.io/packagist/v/symfinity/font-manager.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/symfinity/font-manager)
[![Downloads](https://img.shields.io/packagist/dt/symfinity/font-manager.svg?style=flat&logo=packagist&logoColor=white)](https://packagist.org/packages/symfinity/font-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat)](LICENSE)

</div>

> **Read-only mirror.** Source of truth: [symfinity/symfinity](https://github.com/symfinity/symfinity) (`packages/font-manager/`). See [CONTRIBUTING.md](CONTRIBUTING.md) for how to propose changes.

## Features
- **Multiple Providers** - Google Fonts, Bunny Fonts, Fontsource, and Local Fonts
- **Privacy-Friendly** - GDPR-compliant options (Bunny Fonts, Fontsource)
- **Development Mode** - CDN with inline styles
- **Production Mode** - Lock fonts locally for better performance and privacy
- **Multi-Format Export** - Export fonts in 12+ formats (CSS, SCSS, Tailwind, TypeScript, Design Tokens, and more)
- **Build Tool Support** - AssetMapper, Webpack, and Vite auto-detection
- **Framework Integration** - Bootstrap SCSS variables, Tailwind config, CSS custom properties
- **Design System Ready** - W3C Design Tokens, Figma Tokens, Style Dictionary
- **Smart CSS** - Automatic font styling for body, headings, and bold text
- **CLI Tools** - Search, lock, validate, prune, and export commands
- **Custom Fonts** - Support for self-hosted brand fonts
- **Type-Safe** - PHP 8.1 enums and TypeScript definitions

## Supported Providers
| Provider | Fonts | Privacy | API Key | CDN |
|----------|-------|---------|---------|-----|
| **Google Fonts** | 1,500+ | Tracks | Optional | Yes |
| **Bunny Fonts** | 1,500+ | GDPR | No | Yes |
| **Fontsource** | 1,500+ | Good | No | Yes |
| **Local Fonts** | Custom | Perfect | No | No |

**Recommended for privacy:** Use **Bunny Fonts** (GDPR-compliant, zero tracking)

## Prerequisites

Add the [symfinity/recipes](https://github.com/symfinity/recipes) Flex endpoint to your project's `composer.json` (see [recipes README](https://github.com/symfinity/recipes/blob/main/README.md)) — recipes are not in Symfony's official recipe repository yet.

## Installation
```bash
composer require symfinity/font-manager
```

The Flex recipe registers the bundle for **dev** and **test** only. Enable **prod** manually before locking fonts — see [Installation](docs/installation.md).

## Documentation
- **[Quickstart](docs/quickstart.md)** - Get started in 5 minutes
- **[Installation](docs/installation.md)** - Flex, manual setup, production
- **[Export Formats](docs/exports.md)** - Multi-format export guide (CSS, SCSS, Tailwind, TypeScript, Design Tokens)
- **[Usage Guide](docs/usage.md)** - Function parameters and examples
- **[Providers](docs/providers.md)** - Provider comparison and setup
- **[Commands](docs/commands.md)** - CLI command reference
- **[Configuration](docs/configuration.md)** - All configuration options
- **[Local Fonts](docs/local-fonts.md)** - Custom font setup
- **[Migration Guide](docs/migration.md)** - Migrating from google-fonts

## Requirements
- PHP 8.1 or higher
- Symfony 6.4, 7.x, or 8.x
- Twig 3.0 or higher

## Support
- [GitHub Issues](https://github.com/symfinity/font-manager/issues)
- [Security](.github/SECURITY.md)
- [Contributing](CONTRIBUTING.md)

## License
[MIT](LICENSE)
