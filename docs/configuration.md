# Configuration Reference

Symfinity Flex recipe and shipped package defaults use **`default_provider: google`** and **`export.formats: [css_variables]`** only. The full example below shows optional providers and formats — enable explicitly in your app.

See [Exporter policy](./exporter-policy.md) for why all exporters exist in the container but only configured formats run on lock.

## Full Configuration Example

```yaml
# config/packages/font_manager.yaml
font_manager:
    # Default provider: google (Symfinity recipe), bunny, fontsource, or local
    default_provider: google

    # Cache TTL for API responses (seconds)
    cache_ttl: 3600

    # Use locked fonts instead of CDN (auto-enabled in prod)
    use_locked_fonts: false

    # Directory for locked fonts (served by AssetMapper)
    fonts_dir: '%kernel.project_dir%/assets/fonts'

    # Font lock manifest file
    manifest_file: '%kernel.project_dir%/var/font-manager.lock.json'

    # Unicode subsets to include (reduces file count)
    unicode_subsets: ['latin', 'latin-ext']  # Default: European languages

    # Build tool configuration
    build:
        tool: 'auto'  # auto | assetmapper | webpack | vite

    # Export formats configuration
    export:
        auto_detect: false  # Auto-detect required formats
        formats:
            - css_variables
            - scss_bootstrap
            - tailwind_config
            - typescript_definitions
        output:
            base_dir: '%kernel.project_dir%'
            fonts_dir: 'auto'
            styles_dir: 'auto'
            config_dir: 'auto'

    # Provider configurations
    providers:
        google:
            enabled: true
            api_key: '%env(GOOGLE_FONTS_API_KEY)%'  # Optional, for search

        bunny:
            enabled: true

        local:
            enabled: true
            directory: '%kernel.project_dir%/assets/fonts/custom'
            fonts:
                CorporateSerif:
                    display_name: 'Corporate Serif'
                    category: 'serif'
                    weights: [400, 700]
                    styles: ['normal', 'italic']
                    files:
                        400-normal: 'corporate-serif-regular.woff2'
                        400-italic: 'corporate-serif-italic.woff2'
                        700-normal: 'corporate-serif-bold.woff2'
                        700-italic: 'corporate-serif-bold-italic.woff2'
                    unicode_range: 'U+0000-00FF, U+0131'
```

## Configuration Options

### General Settings

#### `default_provider`

**Type:** `string`  
**Default:** `google`  
**Values:** `google`, `bunny`, `fontsource`, `local`

Which provider to use by default when no provider is specified.

```yaml
font_manager:
    default_provider: bunny  # Privacy-friendly
```

**Note:** When using `bunny` as default, the `fonts:search` command automatically falls back to Google Fonts API (Bunny uses the same catalog but doesn't provide a search API).

#### `cache_ttl`

**Type:** `integer`  
**Default:** `3600`

Cache TTL for provider API responses in seconds.

```yaml
font_manager:
    cache_ttl: 7200  # 2 hours
```

#### `use_locked_fonts`

**Type:** `boolean`  
**Default:** `false`

Whether to use locked/local fonts instead of CDN.

```yaml
font_manager:
    use_locked_fonts: true  # Use self-hosted fonts
```

**Note:** Automatically enabled in `prod` environment via `when@prod` configuration.

#### `fonts_dir`

**Type:** `string`  
**Default:** `%kernel.project_dir%/assets/fonts`

Directory where locked fonts are stored.

```yaml
font_manager:
    fonts_dir: '%kernel.project_dir%/public/fonts'
```

#### `manifest_file`

**Type:** `string`  
**Default:** `%kernel.project_dir%/var/font-manager.lock.json`

Path to the font lock manifest file.

```yaml
font_manager:
    manifest_file: '%kernel.project_dir%/var/fonts.lock.json'
```

#### `unicode_subsets`

**Type:** `array<string>`  
**Default:** `['latin', 'latin-ext']`

Unicode character subsets to include when downloading fonts. This significantly reduces file count and improves performance.

```yaml
font_manager:
    # Default: European languages only
    unicode_subsets: ['latin', 'latin-ext']  # ~83% fewer files
```

**Available subsets:**
- `latin` - Basic Latin (A-Z, common punctuation)
- `latin-ext` - Extended Latin (accented characters)
- `cyrillic` - Russian, Ukrainian, etc.
- `cyrillic-ext` - Extended Cyrillic
- `greek` - Greek alphabet
- `greek-ext` - Extended Greek

**Examples:**

```yaml
# Russian/Ukrainian support
unicode_subsets: ['latin', 'latin-ext', 'cyrillic']  # 12 files per font

# Greek support
unicode_subsets: ['latin', 'latin-ext', 'greek']  # 12 files per font

# All languages (no filtering)
unicode_subsets: []  # 48 files per font
```

**File count comparison:**
- Default `['latin', 'latin-ext']`: **8 files** per font (Ubuntu 4 weights = 8 total)
- With cyrillic: **12 files** per font
- All subsets `[]`: **48 files** per font

**Note:** Only affects Google Fonts, Bunny Fonts, and Fontsource. Local fonts are not filtered.

---

## Build & Export Settings

Font Manager supports multi-format export for seamless framework integration.

### `build.tool`

**Type:** `string`  
**Default:** `auto`  
**Values:** `auto`, `assetmapper`, `webpack`, `vite`

```yaml
font_manager:
    build:
        tool: 'auto'  # Auto-detect build tool
```

### `export.formats`

**Type:** `array<string>`  
**Default:** `['css_variables']`

Export formats to generate when locking fonts.

```yaml
font_manager:
    export:
        formats:
            - css_variables
            - scss_bootstrap
            - tailwind_config
```

**For detailed format documentation, see [Export Formats Guide](exports.md).**

---

### Provider: Google Fonts

```yaml
font_manager:
    providers:
        google:
            enabled: true
            api_key: '%env(GOOGLE_FONTS_API_KEY)%'
```

#### `enabled`

**Type:** `boolean`  
**Default:** `true`

Enable Google Fonts provider.

#### `api_key`

**Type:** `string|null`  
**Default:** `null`

Google Fonts API key (optional, only required for `fonts:search` command).

Get your free API key at [Google Cloud Console](https://console.cloud.google.com/apis/credentials).

---

### Provider: Bunny Fonts

```yaml
font_manager:
    providers:
        bunny:
            enabled: true
```

#### `enabled`

**Type:** `boolean`  
**Default:** `true`

Enable Bunny Fonts provider.

**No additional configuration needed** - Bunny Fonts is ready to use.

---

### Provider: Local Fonts

```yaml
font_manager:
    providers:
        local:
            enabled: true
            directory: '%kernel.project_dir%/assets/fonts/custom'
            fonts:
                FontName:
                    display_name: 'Font Display Name'
                    category: 'serif'  # serif, sans-serif, monospace, etc.
                    weights: [400, 700]
                    styles: ['normal', 'italic']
                    files:
                        400-normal: 'filename-regular.woff2'
                        400-italic: 'filename-italic.woff2'
                        700-normal: 'filename-bold.woff2'
                        700-italic: 'filename-bold-italic.woff2'
                    unicode_range: 'U+0000-00FF'  # Optional
```

#### `enabled`

**Type:** `boolean`  
**Default:** `false`

Enable local fonts provider.

#### `directory`

**Type:** `string`  
**Default:** `%kernel.project_dir%/assets/fonts/custom`

Directory where custom font files are stored.

#### `fonts`

**Type:** `array`

Map of font configurations. Each font requires:

- `display_name` (string, optional) - Human-readable name
- `category` (string, optional) - Font category
- `weights` (array of integers) - Available weights
- `styles` (array of strings) - Available styles (`normal`, `italic`)
- `files` (array) - Map of variant keys to filenames
  - Key format: `{weight}-{style}` (e.g., `400-normal`, `700-italic`)
  - Value: filename in the fonts directory
- `unicode_range` (string, optional) - Unicode range for subsetting

---

## Environment-Specific Configuration

### Development

```yaml
# config/packages/dev/font_manager.yaml
font_manager:
    default_provider: google  # Use Google for search API
    use_locked_fonts: false   # Use CDN for faster development
```

### Production

```yaml
# config/packages/prod/font_manager.yaml
font_manager:
    default_provider: bunny   # Privacy-friendly
    use_locked_fonts: true    # Self-hosted fonts
```

---

## Environment Variables

```bash
# .env
GOOGLE_FONTS_API_KEY=your_api_key_here  # Optional, for search command
```

```bash
# .env.local (gitignored)
GOOGLE_FONTS_API_KEY=actual_key_value
```

---

## Migration from google-fonts Bundle

### Before (google-fonts)

```yaml
# config/packages/google_fonts.yaml
google_fonts:
    api_key: '%env(GOOGLE_FONTS_API_KEY)%'
    fonts_dir: '%kernel.project_dir%/assets/fonts'
```

### After (font-manager)

```yaml
# config/packages/font_manager.yaml
font_manager:
    default_provider: google
    providers:
        google:
            enabled: true
            api_key: '%env(GOOGLE_FONTS_API_KEY)%'
```

---

For more details:
- [Usage Guide](usage.md)
- [Providers Guide](providers.md)
- [Commands Reference](commands.md)

