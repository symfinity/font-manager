# Performance Optimization Guide

Font Manager 0.3.0 introduces comprehensive performance optimizations for font loading, including resource hints, preloading, Font Loading API, variable fonts, and intelligent fallback chains.

## Quick Start

Enable all performance features:

```yaml
# config/packages/font_manager.yaml
font_manager:
    performance:
        resource_hints: true          # Generate preconnect/dns-prefetch hints
        preload_critical_fonts: false # Preload critical fonts (manual configuration)
        font_loading_api: false       # Use Font Loading API (experimental)
        prefer_variable_fonts: true   # Prefer variable fonts when available
        intelligent_fallbacks: true   # Generate intelligent fallback chains
```

## Resource Hints

Font Manager automatically generates resource hints (`preconnect` and `dns-prefetch`) for font provider CDNs to establish early connections.

**Enabled by default** - No configuration needed. Font Manager detects which providers you use and generates appropriate hints.

### Example Output

```html
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link rel="dns-prefetch" href="https://fonts.bunny.net">
```

## Variable Fonts

Variable fonts can significantly reduce the number of font files by using a single file with adjustable weight and style ranges.

### Automatic Detection

Font Manager automatically detects and prefers variable fonts when:
- Provider supports variable fonts (Google Fonts, Bunny Fonts, Fontsource)
- Variable font variant is available for the requested font

### Weight Range Support

Variable fonts support weight ranges (e.g., `100..900`) instead of discrete weights:

```twig
{# Variable font with weight range #}
{{ font_manager('Roboto Flex', '100..900', 'normal') }}
```

### Fallback to Static Fonts

If variable fonts are unavailable, Font Manager automatically falls back to static fonts.

## Intelligent Fallback Chains

Font Manager generates intelligent fallback chains based on font characteristics (monospace vs. sans-serif) and system font availability.

### Sans-Serif Fallbacks

```css
font-family: 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
```

### Monospace Fallbacks

```css
font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
```

### Disable Intelligent Fallbacks

```yaml
font_manager:
    performance:
        intelligent_fallbacks: false  # Use simple fallbacks: 'font-name', sans-serif
```

## Preload Critical Fonts

Preload critical fonts for above-the-fold content to improve Largest Contentful Paint (LCP).

```yaml
font_manager:
    performance:
        preload_critical_fonts: true
```

**Note:** Currently requires manual configuration of which fonts are critical. Automatic detection planned for future release.

## Font Loading API

The Font Loading API provides programmatic control over font loading for better performance.

```yaml
font_manager:
    performance:
        font_loading_api: true  # Experimental
```

**Note:** Font Loading API integration is experimental and requires browser support.

## Performance Best Practices

1. **Enable all optimizations** - Turn on all performance features in production
2. **Use variable fonts** - Prefer variable fonts when available (fewer files)
3. **Limit font weights** - Only request weights you actually use
4. **Use locked fonts** - Lock fonts locally for production (automatic in `prod` environment)
5. **Enable intelligent fallbacks** - Improves perceived performance during font loading

## Measuring Performance

Use browser DevTools to measure font loading performance:

- **Network tab** - Check font file sizes and load times
- **Performance tab** - Analyze layout shifts during font loading
- **Lighthouse** - Get performance scores and recommendations

## Troubleshooting

### Fonts Not Loading

- Check resource hints are generated (View Page Source)
- Verify provider CDN is accessible
- Check browser console for errors

### Performance Issues

- Enable `prefer_variable_fonts` to reduce file count
- Use `preload_critical_fonts` for above-the-fold content
- Consider reducing number of font weights/styles

## Related Documentation

- [Usage Guide](usage.md) - Basic font usage
- [Configuration](configuration.md) - All configuration options
- [Export Formats](exports.md) - Multi-format export system

