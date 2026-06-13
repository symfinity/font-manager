# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.2] - 2026-06-14

### Changed

- Split mirror CI expanded to PHP 8.2–8.5 × Symfony 6.4, 7.4, 8.0, and 8.1 (PHPUnit + PHPStan on every cell)
- Flex recipe version folder `0.2` for `^0.2` installs (recipe `0.1` unchanged for existing `^0.1` constraints)
- Handbook: consumer upgrade guide, index cleanup, quickstart cross-links and support footer
- Packagist archives slimmed via `.gitattributes` `export-ignore` rules

### Notes

- No functional or API changes — patch release following the 0.2.1 relocation note
- Symfony 8.0 remains in the CI matrix for compatibility; prefer 8.1+ for new projects

## [0.2.1] - 2026-06-12

### Changed

- Relocated to `symfinity/font-manager` under the Symfinity organization; namespace `NeuralGlitch\FontManager\` → `Symfinity\FontManager\` (no functional changes in this release).

### Notes

- CI on the split mirror: PHP 8.1 × Symfony 6.4.* only (relocation). Broader matrix including Symfony 7.4 and 8.1 (PHP 8.4+) planned for the next patch; Symfony 8.0 not targeted (EOL July 2026).

## [0.2.0] - 2025-11-22

### Added

- **Multi-Format Export System** - Export fonts in 12+ formats for seamless framework integration
  - CSS Formats: `css_variables`, `css_modules`, `css_layer`
  - SCSS Formats: `scss_variables`, `scss_bootstrap`, `scss_mixins`
  - JavaScript Formats: `esm_javascript`, `tailwind_config`, `typescript_definitions`
  - Design System Formats: `json`, `design_tokens`, `figma_tokens`, `style_dictionary`
- **Build Tool Auto-Detection** - Automatically detects AssetMapper, Webpack, or Vite
- **Framework Integration**
  - Bootstrap SCSS variables (`$font-family-base`) auto-generated
  - Tailwind CSS configuration module export
  - TypeScript type definitions for type-safe font handling
- **Design System Support**
  - W3C Design Tokens format
  - Figma Tokens Studio format
  - Style Dictionary configuration
- **New Console Commands**
  - `fonts:export` - Export fonts in specific formats with dry-run support
  - `fonts:formats` - List all available export formats with details
  - `fonts:format:info` - Show detailed usage instructions for a format
- **Enhanced `fonts:lock` Command** - Automatically exports configured formats after locking
- **Configuration Options**
  - `build.tool` - Build tool selection (auto, assetmapper, webpack, vite)
  - `export.auto_detect` - Auto-detect required formats based on project
  - `export.formats` - List of export formats to generate
  - `export.output` - Customizable output paths per format
- **Comprehensive Documentation**
  - New `docs/exports.md` - Complete export formats guide
  - Updated all existing docs with cross-references
  - Framework integration examples (Bootstrap, Tailwind, TypeScript)
  - Build tool integration guides

### Changed

- Configuration structure extended with `build` and `export` sections
- `fonts:lock` command now auto-exports configured formats (disable with `--no-export`)
- Output paths now adjust based on detected build tool

## [0.1.0] - 2025-11-06

### Added

- Initial release of Font Manager Bundle for Symfony
- Multi-provider architecture supporting Google Fonts, Bunny Fonts, Fontsource, and Local Fonts
- Twig function `font_manager()` for easy font integration in templates
- Development mode with provider CDN and inline styles
- Production mode with local font locking and dedicated stylesheets
- Automatic CSS variable generation for font families (`--font-{name}`)
- Intelligent CSS rules for body, headings, bold text, and italic styles
- Separate CSS rules for monospace fonts (only `code`, `pre`, `kbd`, `samp` tags)
- PHP 8.1 Enums for type-safe configuration:
  - `FontDisplay` (auto, block, swap, fallback, optional)
  - `ProviderFeature` (search, metadata, variable_fonts, cdn)
  - `FontStyle` (normal, italic)
- Unicode subset filtering with configurable subsets via YAML:
  - Default: `['latin', 'latin-ext']` (reduces file count by ~83%)
  - Configurable: Add `cyrillic`, `greek`, etc. for international projects
  - Automatic detection and filtering for Google Fonts, Bunny Fonts, and Fontsource
- Console commands:
  - `fonts:search` - Search fonts from any provider
  - `fonts:lock` - Scan templates and lock all used fonts locally
  - `fonts:validate` - Validate local font configuration
  - `fonts:status` - Display configuration and locked fonts status
  - `fonts:prune` - Remove unused fonts from locked fonts directory
  - `fonts:migrate-from-google-fonts` - Automated migration from google-fonts bundle
- Font manifest file for production font management
- Support for multiple weights (100-900) and styles (normal, italic)
- Monospace font support with dedicated CSS rules
- Provider-specific features:
  - Google Fonts: API search, metadata, variable fonts, CDN
  - Bunny Fonts: GDPR-compliant CDN (no API key required)
  - Fontsource: Privacy-friendly CDN with jsdelivr, automatic lowercase-kebab-case conversion, relative URL resolution
  - Local Fonts: Self-hosted custom fonts with YAML configuration
- Environment-aware font loading (CDN in dev, locked in prod)
- Automatic font subsetting and optimization during locking
- Automatic provider detection and tracking in manifest
- Symfony 6.4, 7.x, and 8.x compatibility
- AssetMapper integration with proper font path handling
- Symfony Flex recipe support
- Documentation:
  - Usage guide with examples
  - Provider comparison and setup instructions
  - CLI command reference
  - Complete configuration reference
  - Local fonts setup guide
  - Migration guide from google-fonts bundle
