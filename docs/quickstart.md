## Quick Start

### 1. Add fonts to your template
```twig
{# templates/base.html.twig #}
<head>
  {# Use default provider (Bunny Fonts recommended for privacy) #}
  {{ font_manager('Ubuntu', '300 400 700', 'normal italic') }}
  
  {# Monospace font for code #}
  {{ font_manager('JetBrains Mono', '400 500', 'normal', 'swap', true) }}
</head>
```

### 2. Lock fonts for production
```bash
php bin/console fonts:lock
```

This downloads fonts to `assets/fonts/` and automatically exports them in configured formats.

The bundle automatically switches to locked fonts in production.

### 3. Configure export formats (optional)
```yaml
# config/packages/font_manager.yaml
font_manager:
  build:
    tool: 'auto'  # auto-detect: assetmapper, webpack, or vite
  
  export:
    formats:
      - css_variables      # CSS custom properties
      - scss_bootstrap     # Bootstrap SCSS variables
      - tailwind_config    # Tailwind CSS configuration
      - typescript_definitions  # TypeScript type definitions
```

Available formats:
- **CSS**: `css_variables`, `css_modules`, `css_layer`
- **SCSS**: `scss_variables`, `scss_bootstrap`, `scss_mixins`
- **JavaScript**: `esm_javascript`, `tailwind_config`, `typescript_definitions`
- **Design System**: `json`, `design_tokens`, `figma_tokens`, `style_dictionary`

### 4. Optional: Search and export
```bash
# Search available fonts (requires API key for Google provider)
php bin/console fonts:search roboto --provider=google

# Export fonts in specific formats
php bin/console fonts:export --format=scss_bootstrap --format=tailwind_config

# List all available export formats
php bin/console fonts:formats

# Show usage instructions for a format
php bin/console fonts:format:info scss_bootstrap

# Validate local fonts
php bin/console fonts:validate
```

## Configuration

```yaml
# config/packages/font_manager.yaml
font_manager:
    default_provider: 'bunny'  # Recommended: privacy-friendly
    
    providers:
        bunny:
            enabled: true  # GDPR-compliant, zero tracking
```

For detailed configuration options, see [Configuration Guide](docs/configuration.md).

## Migration from google-fonts
Migrating from `neuralglitch/google-fonts`? Use the automatic migration command:

```bash
php bin/console fonts:migrate-from-google-fonts --dry-run  # Preview
php bin/console fonts:migrate-from-google-fonts            # Apply
```

See [Migration Guide](docs/migration.md) for details.

## Multi-Format Export
Font Manager can export fonts in 12+ formats for seamless framework integration:

### Bootstrap Integration
```yaml
# config/packages/font_manager.yaml
font_manager:
  export:
    formats:
      - scss_bootstrap
```
```scss
// app.scss
@import './assets/styles/fonts-bootstrap';  // Font Manager variables
@import 'bootstrap/scss/bootstrap';         // Bootstrap uses your fonts
```

### Tailwind Integration
```yaml
font_manager:
  export:
    formats:
      - tailwind_config
```
```javascript
// tailwind.config.js
const fontConfig = require('./assets/fonts-tailwind.config.js');

module.exports = {
  theme: {
    extend: {
      fontFamily: fontConfig.fontFamily,
    },
  },
};
```

### TypeScript Integration
```yaml
font_manager:
  export:
    formats:
      - typescript_definitions
```
```typescript
// app.ts
import { fonts, type FontFamily } from './assets/fonts';

function applyFont(element: HTMLElement, family: FontFamily) {
  element.style.fontFamily = fonts[family].family; // Type-safe!
}
```
