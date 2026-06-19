# Font Manager - Usage Guide

## Basic Usage

### In Templates

```twig
{# Use default provider (configured in font_manager.yaml) #}
{{ font_manager('Roboto', '400 700') }}

{# Specify weights and styles #}
{{ font_manager('Ubuntu', '300 400 500 700', 'normal italic') }}

{# Monospace font for code elements #}
{{ font_manager('JetBrains Mono', '400 500', 'normal', 'swap', true) }}

{# Specify provider explicitly #}
{{ font_manager('Open Sans', '400 700', 'normal', 'swap', false, 'bunny') }}
```

### Parameters

```twig
font_manager(
    name,         # Font family name (required)
    weights,      # '400 700' or [400, 700] (optional, default: '400')
    styles,       # 'normal italic' or ['normal', 'italic'] (optional, default: 'normal')
    display,      # 'swap' | 'block' | 'fallback' | 'optional' (optional, default: 'swap')
    monospace,    # true | false (optional, default: false)
    provider      # 'google' | 'bunny' | 'local' (optional, default: configured default)
)
```

## Provider-Specific Usage

### Google Fonts

```twig
{# Standard usage #}
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'google') }}
```

**Features:**
- 1,500+ fonts
- Search API available
- Font metadata available
- Variable fonts supported

### Bunny Fonts (Privacy-Friendly)

```twig
{# Privacy-friendly, GDPR compliant #}
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'bunny') }}
```

**Features:**
- Same 1,500+ fonts as Google
- Zero user tracking
- GDPR compliant
- EU-based CDN
- No search API (use Google to search, then switch to Bunny)

### Fontsource (Version-Controlled)

```twig
{# Version-controlled via npm packages #}
{{ font_manager('Poppins', '400 700', 'normal', 'swap', false, 'fontsource') }}
```

**Features:**
- Same 1,500+ fonts as Google
- Version-controlled (npm versions)
- Self-hosted via jsdelivr CDN
- Search API via npm registry
- No npm installation required
- Privacy-friendly

### Local Fonts (Self-Hosted)

```twig
{# Custom brand fonts #}
{{ font_manager('CorporateSerif', '400 700', 'normal italic', 'swap', false, 'local') }}
```

**Configuration Required:**
```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    providers:
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
                        400-normal: 'corporate-regular.woff2'
                        400-italic: 'corporate-italic.woff2'
                        700-normal: 'corporate-bold.woff2'
                        700-italic: 'corporate-bold-italic.woff2'
```

## Examples

### Body Font
```twig
{# templates/base.html.twig #}
<html>
<head>
    {# Main body font #}
    {{ font_manager('Inter', '300 400 500 700', 'normal italic') }}
</head>
<body>
    <h1>Welcome</h1>
    <p>This text uses Inter font.</p>
</body>
</html>
```

### Monospace for Code
```twig
{# Code/mono font #}
{{ font_manager('Fira Code', '400 500 700', 'normal', 'swap', true) }}

<pre><code>
function hello() {
    console.log('Hello'); // Uses Fira Code
}
</code></pre>
```

### Multiple Fonts
```twig
{# Headings #}
{{ font_manager('Montserrat', '600 700 800', 'normal') }}

{# Body text #}
{{ font_manager('Open Sans', '300 400 600', 'normal italic') }}

{# Code blocks #}
{{ font_manager('JetBrains Mono', '400 500', 'normal', 'swap', true) }}
```

## Development vs Production

### Development (CDN)

**Configuration:**
```yaml
# config/packages/dev/font_manager.yaml
symfinity_font_manager:
    default_provider: 'bunny'  # Fast CDN, privacy-friendly
    use_locked_fonts: false
```

**Result:**
- Fonts loaded from CDN (fast development)
- No build step required
- Changes reflect immediately

### Production (Self-Hosted)

**Configuration:**
```yaml
# config/packages/prod/font_manager.yaml
symfinity_font_manager:
    use_locked_fonts: true  # Use locally locked fonts
```

**Build Process:**
```bash
# Lock fonts before deployment
php bin/console fonts:lock

# Check what was locked
php bin/console fonts:status

# Compile assets
php bin/console asset-map:compile
```

**Result:**
- Fonts served from your domain
- Better privacy (no external requests)
- Better performance (no CDN latency)
- Works offline

## Advanced Usage

### Environment-Specific Providers

```yaml
# config/packages/dev/font_manager.yaml
symfinity_font_manager:
    default_provider: 'google'  # Use Google for development (search API)

# config/packages/prod/font_manager.yaml
symfinity_font_manager:
    default_provider: 'bunny'  # Use Bunny for production (privacy)
```

### Custom Font Fallbacks

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    providers:
        local:
            fonts:
                BrandFont:
                    weights: [400, 700]
                    styles: ['normal']
                    files:
                        400-normal: 'brand-regular.woff2'
                        700-normal: 'brand-bold.woff2'
                    unicode_range: 'U+0000-00FF'  # Latin only
```

### Weight Optimization

```twig
{# Only load weights you need #}
{{ font_manager('Roboto', '400 700') }}  {# Good - minimal #}

{# Don't load all weights if not needed #}
{{ font_manager('Roboto', '100 200 300 400 500 600 700 800 900') }}  {# Bad - bloated #}
```

## Troubleshooting

### Fonts not loading in production

```bash
# Check fonts are locked
php bin/console fonts:status

# Re-lock fonts
php bin/console fonts:lock

# Compile assets
php bin/console asset-map:compile
```

### Search not working with Bunny Fonts

```yaml
# Temporarily switch to Google for search
symfinity_font_manager:
    default_provider: 'google'
```

```bash
# Search fonts
php bin/console fonts:search roboto

# Then switch back to Bunny for production
symfinity_font_manager:
    default_provider: 'bunny'
```

### Local font files not found

```bash
# Validate font files exist
php bin/console fonts:validate

# Check configuration
cat config/packages/symfinity_font_manager.yaml
```

## Best Practices

1. **Use Bunny Fonts for production** (privacy-friendly)
2. **Lock fonts before deployment** (better performance)
3. **Only load weights you need** (faster page load)
4. **Use font-display: swap** (prevent FOIT)
5. **Preload critical fonts** (optional, for above-the-fold content)
6. **Use variable fonts** when available (fewer requests)

---

For more details, see:
- [Export Formats](exports.md)
- [Providers Guide](providers.md)
- [Commands Reference](commands.md)
- [Configuration Options](configuration.md)
