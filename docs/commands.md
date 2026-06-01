# CLI Commands Reference

## fonts:migrate-from-google-fonts

**Automatically migrate from symfinity/font-manager**

```bash
# Preview changes (recommended)
php bin/console fonts:migrate-from-google-fonts --dry-run

# Apply migration
php bin/console fonts:migrate-from-google-fonts
```

**What it does:**
- Converts `google_fonts.yaml` → `font_manager.yaml`
- Updates all templates: `google_fonts()` → `font_manager()`
- Migrates manifest file
- Creates backups automatically

**Options:**
- `--dry-run` - Preview without changes
- `--skip-templates` - Skip template updates
- `--skip-config` - Skip config updates

See [Migration Guide](migration.md) for full details.

---

## fonts:search

Search for fonts by name using a provider.

```bash
# Search using default provider
php bin/console fonts:search roboto

# Search with custom provider
php bin/console fonts:search --provider=google ubuntu

# Limit results
php bin/console fonts:search --limit=10 sans
```

**Options:**
- `--provider=NAME` - Provider to use (google, bunny, local)
- `--limit=N` - Maximum results (default: 20)

**Note:** Only Google provider supports search API. Bunny and Local providers will show an error.

---

## fonts:lock

Scan templates and lock all used fonts for production.

```bash
# Scan default template directories (with auto-export)
php bin/console fonts:lock

# Scan specific directories
php bin/console fonts:lock templates/ views/

# Skip automatic export
php bin/console fonts:lock --no-export
```

**What it does:**
1. Scans Twig templates for `font_manager()` calls
2. Downloads all referenced fonts
3. Creates manifest file
4. Saves fonts to `assets/fonts/`
5. **Automatically exports configured formats**

**Output:**
- Font files: `assets/fonts/{font-name}-{weight}-{style}.woff2`
- CSS file: `assets/fonts/{font-name}.css`
- Manifest: `var/font-manager.lock.json`
- **Export files:** Based on configured formats (see [Export Guide](exports.md))

**Options:**
- `--no-export` - Skip automatic export after locking

---

## fonts:status

Show status of locked fonts.

```bash
php bin/console fonts:status
```

**Shows:**
- Manifest information
- Locked fonts list
- Weights and styles per font
- Number of files
- Provider used

---

## fonts:validate

Validate local font files exist.

```bash
php bin/console fonts:validate
```

**What it does:**
1. Checks local fonts configuration
2. Verifies all referenced files exist
3. Reports missing files with paths

**Use case:** Validate custom brand fonts before deployment.

---

## fonts:prune

Remove unused locked fonts.

```bash
# Preview what would be deleted
php bin/console fonts:prune --dry-run

# Actually delete unused fonts
php bin/console fonts:prune
```

**What it does:**
1. Compares manifest to actual files
2. Identifies unused files
3. Optionally removes them

---

## fonts:export

Export fonts in configured or specific formats.

```bash
# Export all configured formats
php bin/console fonts:export

# Export specific formats
php bin/console fonts:export --format=scss_bootstrap --format=tailwind_config

# Dry run (preview without writing)
php bin/console fonts:export --dry-run

# Specify build tool
php bin/console fonts:export --build-tool=webpack
```

**What it does:**
1. Loads locked fonts from manifest
2. Exports in requested formats
3. Resolves dependencies automatically
4. Writes files to appropriate directories

**Options:**
- `--format=NAME` - Export specific format(s) (repeatable)
- `--dry-run` - Preview without writing files
- `--build-tool=NAME` - Override build tool detection (auto, assetmapper, webpack, vite)

See [Export Formats Guide](exports.md) for all available formats.

---

## fonts:formats

List all available export formats.

```bash
php bin/console fonts:formats
```

**Output:**
Shows all available export formats grouped by category (CSS, SCSS, JavaScript, Design System).

**Example output:**
```
CSS
  css_variables      CSS Custom Properties      .css
  css_modules        CSS Modules Export         .module.css
  css_layer          CSS @layer Integration     .css

SCSS
  scss_variables     SCSS Variables             .scss
  scss_bootstrap     SCSS Bootstrap Variables   .scss
  scss_mixins        SCSS Mixins & Functions    .scss

JavaScript
  esm_javascript     ES Modules                 .js
  tailwind_config    Tailwind Configuration     .js
  typescript_definitions TypeScript Definitions  .d.ts

Design System
  json               Generic JSON               .json
  design_tokens      W3C Design Tokens          .tokens.json
  figma_tokens       Figma Tokens Studio        .figma.json
  style_dictionary   Style Dictionary Format    .js
```

---

## fonts:format:info

Show detailed usage instructions for an export format.

```bash
php bin/console fonts:format:info scss_bootstrap
```

**What it shows:**
- Format name and label
- File extension
- Default filename
- Dependencies
- Detailed usage instructions
- Export examples

**Use case:** Learn how to integrate a specific export format in your project.

---

## Typical Workflows

### Development Workflow

```bash
# 1. Search for fonts
php bin/console fonts:search inter

# 2. Add to template
# {{ font_manager('Inter', '400 700') }}

# 3. Test in browser
# (fonts load from CDN)
```

### Production Deployment

```bash
# 1. Lock fonts before deployment (auto-exports configured formats)
php bin/console fonts:lock

# 2. Check status
php bin/console fonts:status

# 3. Verify exports
php bin/console fonts:formats

# 4. Compile assets
php bin/console asset-map:compile

# 5. Deploy
```

### Maintenance

```bash
# Remove old/unused fonts
php bin/console fonts:prune

# Validate local fonts
php bin/console fonts:validate
```

---

For more information:
- [Export Formats](exports.md)
- [Usage Guide](usage.md)
- [Providers](providers.md)
- [Configuration](configuration.md)

