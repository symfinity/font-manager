# Font Providers Guide

## Available Providers

### Google Fonts

**Best for:** Development, font discovery  
**Privacy:** ⚠️ Tracks users  
**API Key:** Optional (required for search)

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    default_provider: 'google'
    providers:
        google:
            enabled: true
            api_key: '%env(GOOGLE_FONTS_API_KEY)%'  # Optional
```

**Features:**

✅ 1,500+ fonts  
✅ Search API  
✅ Font metadata  
✅ Variable fonts  
⚠️ Tracks user IPs  
⚠️ GDPR concerns

**Usage:**
```twig
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'google') }}
```

---

### Bunny Fonts (Recommended for Production)

**Best for:** Production, privacy-focused apps  
**Privacy:** ✅ Zero tracking, GDPR compliant  
**API Key:** Not required

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    default_provider: 'bunny'
    providers:
        bunny:
            enabled: true
```

**Features:**

✅ Same 1,500+ fonts as Google  
✅ Zero user tracking  
✅ GDPR compliant by default  
✅ EU-based CDN  
✅ Fast global delivery  
❌ No search API  
❌ No metadata API

**Usage:**
```twig
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'bunny') }}
```

**Workflow:**
1. Use Google provider to search for fonts during development
2. Switch to Bunny provider for production
3. Or use `fonts:lock` to self-host fonts

---

### Fontsource

**Best for:** Version-controlled fonts, self-hosted via CDN  
**Privacy:** ✅ Good (self-hosted)  
**API Key:** Not required

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    default_provider: fontsource
    providers:
        fontsource:
            enabled: true
```

**Features:**

✅ Same 1,500+ fonts as Google  
✅ Version-controlled (via npm versions)  
✅ Self-hosted via jsdelivr CDN  
✅ No npm required (CDN-based)  
✅ Search API (via npm registry)  
✅ Privacy-friendly

**Usage:**
```twig
{{ font_manager('Roboto', '400 700', 'normal', 'swap', false, 'fontsource') }}
```

**How it works:**
- Fonts are served from jsdelivr CDN
- Uses @fontsource npm packages
- No need to install npm
- Version-controlled (can pin versions)
- Self-hosted (fonts on jsdelivr, not Google)

---

### Local Fonts

**Best for:** Custom brand fonts, corporate fonts  
**Privacy:** ✅ Perfect (self-hosted)  
**API Key:** Not required

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    default_provider: 'local'
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
                        400-normal: 'corporate-serif-regular.woff2'
                        400-italic: 'corporate-serif-italic.woff2'
                        700-normal: 'corporate-serif-bold.woff2'
                        700-italic: 'corporate-serif-bold-italic.woff2'
                    unicode_range: 'U+0000-00FF, U+0131'
```

**Directory Structure:**
```
assets/fonts/custom/
├── corporate-serif-regular.woff2
├── corporate-serif-italic.woff2
├── corporate-serif-bold.woff2
└── corporate-serif-bold-italic.woff2
```

**Features:**

✅ Full control over fonts  
✅ Perfect privacy  
✅ Works offline  
✅ Custom/licensed fonts  
✅ Version control friendly  
⚠️ Manual font management  
⚠️ Must manage licenses

**Usage:**
```twig
{{ font_manager('CorporateSerif', '400 700', 'normal italic', 'swap', false, 'local') }}
```

**Validate Files:**
```bash
php bin/console fonts:validate
```

---

## Provider Comparison

| Feature | Google | Bunny | Fontsource | Local |
|---------|--------|-------|------------|-------|
| **Fonts** | 1,500+ | 1,500+ | 1,500+ | Custom |
| **Privacy** | ⚠️ Tracks | ✅ GDPR | ✅ Good | ✅ Perfect |
| **API Key** | Optional | No | No | No |
| **Search** | ✅ Yes | ❌ No | ✅ Yes | ✅ Config |
| **CDN** | Global | Global | jsdelivr | Self-hosted |
| **Setup** | Easy | Easy | Easy | Manual |
| **Version Control** | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| **Best For** | Development | Production | Version-pinned | Brands |

## Switching Providers

### Per Environment

```yaml
# config/packages/dev/font_manager.yaml
symfinity_font_manager:
    default_provider: 'google'

# config/packages/prod/font_manager.yaml
symfinity_font_manager:
    default_provider: 'bunny'
```

### Per Font

```twig
{# Headings: Google Fonts (lots of choices) #}
{{ font_manager('Poppins', '600 700', 'normal', 'swap', false, 'google') }}

{# Body: Bunny Fonts (privacy) #}
{{ font_manager('Inter', '400 600', 'normal italic', 'swap', false, 'bunny') }}

{# UI: Fontsource (version-controlled) #}
{{ font_manager('Poppins', '400 600', 'normal', 'swap', false, 'fontsource') }}

{# Branding: Local (custom font) #}
{{ font_manager('BrandFont', '400 700', 'normal', 'swap', false, 'local') }}
```

## Best Practices

### Development Workflow

1. **Use Google provider** for searching and testing fonts
2. **Search fonts**: `php bin/console fonts:search roboto`
3. **Test in templates** with Google provider
4. **Switch to Bunny** for production deployment
5. **Lock fonts**: `php bin/console fonts:lock`

### Production Deployment

1. **Lock fonts before deployment**: `php bin/console fonts:lock`
2. **Use locked fonts in production** (auto with `when@prod`)
3. **Or use Bunny Fonts CDN** (privacy-friendly alternative)
4. **Commit manifest file** to version control

### Privacy Compliance

**For GDPR compliance:**
```yaml
symfinity_font_manager:
    default_provider: 'bunny'  # Or lock fonts for self-hosting
```

**For perfect privacy:**
```yaml
symfinity_font_manager:
    use_locked_fonts: true  # Self-host fonts (no external requests)
```

---

For more information:
- [Export Formats](exports.md)
- [Usage Guide](usage.md)
- [Commands Reference](commands.md)
- [Configuration](configuration.md)
