# Local Fonts Guide

Complete guide for setting up custom/brand fonts using the Local Fonts provider.

## Overview

The Local Fonts provider allows you to:

- Use custom brand fonts
- Self-host commercial fonts
- Have complete control over font files
- Version-control your fonts
- Validate font file integrity

## Quick Setup

### 1. Prepare Font Files

Place your font files in a directory:

```
assets/fonts/custom/
├── brand-regular.woff2
├── brand-bold.woff2
├── brand-italic.woff2
└── brand-bold-italic.woff2
```

**Recommended formats (in order):**
1. **WOFF2** - Best compression, modern browsers
2. **WOFF** - Legacy browser support
3. **TTF** - Fallback

### 2. Configure Fonts

```yaml
# config/packages/font_manager.yaml
font_manager:
    default_provider: 'local'
    
    providers:
        local:
            enabled: true
            directory: '%kernel.project_dir%/assets/fonts/custom'
            fonts:
                BrandFont:
                    display_name: 'Brand Font'
                    category: 'sans-serif'
                    weights: [400, 700]
                    styles: ['normal', 'italic']
                    files:
                        400-normal: 'brand-regular.woff2'
                        400-italic: 'brand-italic.woff2'
                        700-normal: 'brand-bold.woff2'
                        700-italic: 'brand-bold-italic.woff2'
                    unicode_range: 'U+0000-00FF, U+0131'  # Optional
```

### 3. Use in Templates

```twig
{# templates/base.html.twig #}
{{ font_manager('BrandFont', '400 700', 'normal italic', 'swap', false, 'local') }}
```

## Configuration Options

### Font Definition

```yaml
fonts:
    FontFamilyName:
        display_name: 'Display Name'      # Human-readable name
        category: 'sans-serif'            # serif, sans-serif, display, handwriting, monospace
        weights: [300, 400, 700]          # Available weights
        styles: ['normal', 'italic']      # Available styles
        files:                            # Font file mapping
            300-normal: 'font-light.woff2'
            400-normal: 'font-regular.woff2'
            400-italic: 'font-italic.woff2'
            700-normal: 'font-bold.woff2'
        unicode_range: 'U+0000-00FF'      # Optional: Limit character range
```

### File Mapping Pattern

**Format:** `{weight}-{style}: filename`

**Examples:**
```yaml
files:
    400-normal: 'font-regular.woff2'
    400-italic: 'font-italic.woff2'
    700-normal: 'font-bold.woff2'
    700-italic: 'font-bold-italic.woff2'
    900-normal: 'font-black.woff2'
```

### Font Formats

Supported formats (auto-detected from extension):

| Extension | Format | Browser Support |
|-----------|--------|-----------------|
| `.woff2` | woff2 | Modern (recommended) |
| `.woff` | woff | Legacy support |
| `.ttf` | truetype | Fallback |
| `.otf` | opentype | Fallback |
| `.eot` | embedded-opentype | IE9-11 |

## Complete Example

### Multi-Weight, Multi-Style Font

```yaml
font_manager:
    providers:
        local:
            enabled: true
            directory: '%kernel.project_dir%/assets/fonts/custom'
            fonts:
                CorporateSerif:
                    display_name: 'Corporate Serif'
                    category: 'serif'
                    weights: [300, 400, 600, 700]
                    styles: ['normal', 'italic']
                    files:
                        300-normal: 'corporate-light.woff2'
                        300-italic: 'corporate-light-italic.woff2'
                        400-normal: 'corporate-regular.woff2'
                        400-italic: 'corporate-italic.woff2'
                        600-normal: 'corporate-semibold.woff2'
                        600-italic: 'corporate-semibold-italic.woff2'
                        700-normal: 'corporate-bold.woff2'
                        700-italic: 'corporate-bold-italic.woff2'
                    unicode_range: 'U+0000-00FF, U+0131, U+0152-0153'
```

**Usage:**
```twig
{{ font_manager('CorporateSerif', '400 700', 'normal italic', 'swap', false, 'local') }}
```

## Validation

### Check Font Files

Validate that all configured font files exist:

```bash
php bin/console fonts:validate
```

**Output:**
```
Validating local fonts...
✅ All local font files found
```

Or if missing:
```
⚠️ Found 2 missing font files:

Font: BrandFont, Variant: 400-normal, File: brand-regular.woff2
Path: /path/to/assets/fonts/custom/brand-regular.woff2

Font: BrandFont, Variant: 700-normal, File: brand-bold.woff2
Path: /path/to/assets/fonts/custom/brand-bold.woff2
```

## Best Practices

### 1. Use WOFF2 Format

**Best compression and modern browser support:**
```yaml
files:
    400-normal: 'font.woff2'  # ✅ 30-50% smaller than WOFF
```

### 2. Subset Fonts

Use `unicode_range` to limit character sets:

```yaml
unicode_range: 'U+0000-00FF'  # Latin only
unicode_range: 'U+0100-024F'  # Latin Extended
```

**Benefits:**
- Smaller file sizes
- Faster loading
- Reduced bandwidth

### 3. Provide Only Needed Weights

```yaml
weights: [400, 700]  # Regular and Bold only
```

Don't provide 9 weights if you only use 2.

### 4. Organize Font Files

```
assets/fonts/custom/
├── brand/
│   ├── brand-regular.woff2
│   ├── brand-bold.woff2
│   └── brand-italic.woff2
└── corporate/
    ├── corporate-regular.woff2
    └── corporate-bold.woff2
```

**Update paths in config:**
```yaml
files:
    400-normal: 'brand/brand-regular.woff2'
```

## Common Use Cases

### Single Weight Font

```yaml
fonts:
    Logo:
        weights: [700]
        styles: ['normal']
        files:
            700-normal: 'logo-bold.woff2'
```

### Icon Font

```yaml
fonts:
    Icons:
        category: 'display'
        weights: [400]
        styles: ['normal']
        files:
            400-normal: 'icons.woff2'
        unicode_range: 'U+E000-F8FF'  # Private Use Area
```

### Variable Font

```yaml
fonts:
    VariableFont:
        weights: [400, 500, 600, 700]  # Supported weights
        styles: ['normal']
        files:
            400-normal: 'variable-font.woff2'  # Single file, all weights
```

## Combining Providers

Mix local fonts with CDN fonts:

```twig
{# Brand font (local) #}
{{ font_manager('BrandFont', '400 700', 'normal', 'swap', false, 'local') }}

{# Body font (CDN) #}
{{ font_manager('Inter', '400 600', 'normal', 'swap', false, 'bunny') }}
```

## Troubleshooting

### Font files not found

**Run validation:**
```bash
php bin/console fonts:validate
```

**Check paths:**
- Verify `directory` is correct
- Check file names match exactly (case-sensitive)
- Ensure files exist in the configured directory

### Font not rendering

**Check browser console** for 404 errors on font files.

**Verify public path:**
```yaml
# Font files must be in AssetMapper-accessible location
directory: '%kernel.project_dir%/assets/fonts/custom'
```

### Wrong font weight/style

**Check variant mapping:**
```yaml
# Incorrect:
files:
    400-bold: 'font-bold.woff2'  # ❌ Style should be 'normal'

# Correct:
files:
    700-normal: 'font-bold.woff2'  # ✅ Weight 700 = bold
```

## Performance Tips

### 1. Use font-display: swap

```twig
{{ font_manager('BrandFont', '400', 'normal', 'swap') }}
```

Prevents FOIT (Flash of Invisible Text).

### 2. Preload Critical Fonts

```twig
<link rel="preload" href="{{ asset('fonts/custom/brand-regular.woff2') }}" as="font" type="font/woff2" crossorigin>
```

### 3. Subset Fonts

Use tools like [glyphhanger](https://github.com/filamentgroup/glyphhanger) to create subsets.

## See Also

- [Export Formats](exports.md)
- [Configuration Guide](configuration.md)
- [Provider Comparison](providers.md)
- [Commands Reference](commands.md)
- [Usage Examples](usage.md)
