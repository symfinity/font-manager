# Export Formats Guide

Font Manager can export fonts in 12+ formats for seamless integration with any CSS framework or build tool.

## Quick Start

### 1. Configure Export Formats

**Option A: Auto-Detection (Recommended)**

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    export:
        auto_detect: true  # Scans project for Bootstrap, Tailwind, TypeScript
```

**Option B: Manual Configuration**

```yaml
symfinity_font_manager:
    export:
        formats:
            - css_variables      # CSS custom properties
            - scss_bootstrap     # Bootstrap SCSS variables
            - tailwind_config    # Tailwind CSS configuration
            - typescript_definitions  # TypeScript type definitions
```

### 2. Lock Fonts (Auto-Export)

```bash
php bin/console fonts:lock
```

This automatically exports fonts in all configured formats.

### 3. Manual Export

```bash
# Export specific formats
php bin/console fonts:export --format=scss_bootstrap --format=tailwind_config

# List all available formats
php bin/console fonts:formats

# Show usage instructions for a format
php bin/console fonts:format:info scss_bootstrap
```

---

## Available Export Formats

### CSS Formats

#### 1. CSS Variables (`css_variables`)

**Output:** `assets/styles/fonts-variables.css`

Generates CSS custom properties for maximum flexibility.

```css
:root {
  /* Font Families */
  --font-family-ubuntu: 'Ubuntu', sans-serif;
  --font-family-jetbrains-mono: 'JetBrains Mono', monospace;
  
  /* Semantic Aliases */
  --font-family-sans: var(--font-family-ubuntu);
  --font-family-mono: var(--font-family-jetbrains-mono);
  
  /* Font Weights */
  --font-weight-light: 300;
  --font-weight-normal: 400;
  --font-weight-bold: 700;
}
```

**Usage:**
```css
@import './fonts-variables.css';

body {
  font-family: var(--font-family-sans);
  font-weight: var(--font-weight-normal);
}
```

**Best for:** Vanilla CSS, custom design systems

---

#### 2. CSS Modules (`css_modules`)

**Output:** `assets/fonts.module.css`  
**Dependencies:** `css_variables`

Export for JavaScript consumption via CSS Modules.

```css
@import './fonts-variables.css';

:export {
  fontSans: var(--font-family-ubuntu);
  fontMono: var(--font-family-jetbrains-mono);
  fontWeightNormal: var(--font-weight-normal);
  fontWeightBold: var(--font-weight-bold);
}
```

**Usage:**
```javascript
import fonts from './fonts.module.css';

element.style.fontFamily = fonts.fontSans;
element.style.fontWeight = fonts.fontWeightBold;
```

**Best for:** React, Vue with CSS Modules

---

#### 3. CSS @layer (`css_layer`)

**Output:** `assets/styles/fonts-layer.css`

Uses CSS Cascade Layers for better style control.

```css
@layer design-tokens {
  :root {
    --font-family-sans: 'Ubuntu', sans-serif;
    --font-family-mono: 'JetBrains Mono', monospace;
  }
}

@layer base {
  body {
    font-family: var(--font-family-sans);
  }
  
  h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-family-sans);
  }
}
```

**Usage:**
```css
@import './fonts-layer.css';

/* Override in your own layer */
@layer overrides {
  body { font-family: custom; }
}
```

**Best for:** Modern CSS with cascade layer support

---

### SCSS Formats

#### 4. SCSS Variables (`scss_variables`)

**Output:** `assets/styles/fonts-variables.scss`

Standard SCSS variables for custom projects.

```scss
// Font Families
$font-family-ubuntu: 'Ubuntu', sans-serif !default;
$font-family-jetbrains-mono: 'JetBrains Mono', monospace !default;

// Semantic Aliases
$font-family-sans: $font-family-ubuntu !default;
$font-family-mono: $font-family-jetbrains-mono !default;

// Font Weights
$font-weight-light: 300 !default;
$font-weight-normal: 400 !default;
$font-weight-bold: 700 !default;
```

**Usage:**
```scss
@import './fonts-variables';

body {
  font-family: $font-family-sans;
  font-weight: $font-weight-normal;
}
```

**Best for:** Custom SCSS projects

---

#### 5. SCSS Bootstrap (`scss_bootstrap`)

**Output:** `assets/styles/fonts-bootstrap.scss`

Bootstrap-specific SCSS variables that integrate seamlessly with Bootstrap.

```scss
// Font Families
$font-family-ubuntu: 'Ubuntu', sans-serif !default;

// Bootstrap Integration
$font-family-base: $font-family-ubuntu !default;
$font-family-monospace: $font-family-jetbrains-mono !default;

// Typography Settings
$headings-font-weight: 700 !default;
$headings-font-family: $font-family-ubuntu !default;

// Line Heights
$line-height-base: 1.5 !default;
$line-height-sm: 1.25 !default;
$line-height-lg: 2 !default;
```

**Usage:**
```scss
// app.scss - Import BEFORE Bootstrap!
@import './fonts-bootstrap';
@import 'bootstrap/scss/bootstrap';
```

Bootstrap automatically uses your custom fonts for:
- Body text (`$font-family-base`)
- Headings (`$headings-font-family`)
- Code elements (`$font-family-monospace`)

**Best for:** Bootstrap 5 projects

---

#### 6. SCSS Mixins (`scss_mixins`)

**Output:** `assets/styles/fonts-mixins.scss`  
**Dependencies:** `scss_variables`

Advanced SCSS with maps, functions, and mixins.

```scss
@import './fonts-variables';

// Font Maps
$fonts: (
  'sans': $font-family-ubuntu,
  'mono': $font-family-jetbrains-mono,
);

$font-weights: (
  'light': 300,
  'normal': 400,
  'bold': 700,
);

// Functions
@function font-family($name) {
  @return map-get($fonts, $name);
}

@function font-weight($name) {
  @return map-get($font-weights, $name);
}

// Mixins
@mixin apply-font($family, $weight: normal) {
  font-family: font-family($family);
  font-weight: font-weight($weight);
}

@mixin font($family, $size, $weight: normal, $line-height: 1.5) {
  @include apply-font($family, $weight);
  font-size: $size;
  line-height: $line-height;
}
```

**Usage:**
```scss
@import './fonts-mixins';

.my-heading {
  @include font('sans', 2rem, 'bold', 1.2);
}

.my-class {
  font-family: font-family('sans');
  font-weight: font-weight('bold');
}
```

**Best for:** Advanced SCSS projects, design systems

---

### JavaScript Formats

#### 7. ES Modules (`esm_javascript`)

**Output:** `assets/fonts.js`

Modern JavaScript ES Modules export.

```javascript
export const fontFamilies = {
  sans: "'Ubuntu', sans-serif",
  mono: "'JetBrains Mono', monospace",
};

export const fontWeights = {
  light: 300,
  normal: 400,
  bold: 700,
};

export const fonts = {
  sans: {
    name: 'Ubuntu',
    family: "'Ubuntu', sans-serif",
    weights: [300, 400, 700],
    styles: ['normal', 'italic'],
    monospace: false,
    semantic: 'sans',
  },
  // ...
};

export function getFont(family) {
  return fonts[family] || null;
}

export function getFontFamily(family) {
  const font = getFont(family);
  return font ? font.family : null;
}
```

**Usage:**
```javascript
import { fonts, fontFamilies, fontWeights } from './fonts.js';

element.style.fontFamily = fontFamilies.sans;
element.style.fontWeight = fontWeights.bold;

// Get font details
const font = fonts.sans;
console.log(font.weights); // [300, 400, 700]
```

**Best for:** Vanilla JavaScript, modern frameworks

---

#### 8. Tailwind Config (`tailwind_config`)

**Output:** `assets/fonts-tailwind.config.js`

Tailwind CSS configuration module.

```javascript
module.exports = {
  fontFamily: {
    'ubuntu': ['Ubuntu', 'sans-serif'],
    'jetbrains-mono': ['JetBrains Mono', 'monospace'],
    
    // Semantic aliases (overrides Tailwind defaults)
    'sans': ['Ubuntu', 'sans-serif'],
    'mono': ['JetBrains Mono', 'monospace'],
  },
  fontWeight: {
    light: '300',
    normal: '400',
    bold: '700',
  },
};
```

**Usage:**
```javascript
// tailwind.config.js
const fontConfig = require('./assets/fonts-tailwind.config.js');

module.exports = {
  theme: {
    extend: {
      fontFamily: fontConfig.fontFamily,
      fontWeight: fontConfig.fontWeight,
    },
  },
};
```

**HTML Usage:**
```html
<p class="font-sans font-normal">Text with custom font</p>
<code class="font-mono">Monospace code</code>
```

**Best for:** Tailwind CSS projects

---

#### 9. TypeScript Definitions (`typescript_definitions`)

**Output:** `assets/fonts.d.ts`  
**Dependencies:** `esm_javascript`

Type-safe TypeScript definitions.

```typescript
export interface Font {
  name: string;
  family: string;
  weights: number[];
  styles: readonly ('normal' | 'italic')[];
  monospace: boolean;
  semantic?: 'sans' | 'serif' | 'mono';
}

export const fontFamilies: {
  sans: string;
  mono: string;
};

export const fontWeights: {
  light: number;
  normal: number;
  bold: number;
};

export const fonts: {
  sans: {
    name: 'Ubuntu';
    family: "'Ubuntu', sans-serif";
    weights: [300, 400, 700];
    styles: readonly ['normal', 'italic'];
    monospace: false;
    semantic: 'sans';
  };
  // ...
};

export type FontFamily = 'sans' | 'mono';
export type FontWeight = 300 | 400 | 700;

export function getFont(family: FontFamily): Font | null;
export function getFontFamily(family: FontFamily): string | null;
export function getFontWeights(family: FontFamily): number[];
```

**Usage:**
```typescript
import { fonts, type FontFamily, type FontWeight } from './fonts';

// Type-safe font selection
function applyFont(element: HTMLElement, family: FontFamily) {
  element.style.fontFamily = fonts[family].family; // ✓ Type-safe
}

const myFont: FontFamily = 'sans'; // ✓ Valid
const invalid: FontFamily = 'invalid'; // ✗ TypeScript error
```

**Best for:** TypeScript projects

---

### Design System Formats

#### 10. Generic JSON (`json`)

**Output:** `assets/fonts.json`

Generic JSON for custom integrations.

```json
{
  "fonts": {
    "sans": {
      "name": "Ubuntu",
      "family": "'Ubuntu', sans-serif",
      "weights": [300, 400, 700],
      "styles": ["normal", "italic"],
      "monospace": false,
      "semantic": "sans",
      "files": {
        "300-normal": "/fonts/ubuntu-300.woff2",
        "400-normal": "/fonts/ubuntu-400.woff2"
      }
    }
  },
  "variables": {
    "css": {
      "--font-family-sans": "'Ubuntu', sans-serif"
    },
    "scss": {
      "$font-family-base": "'Ubuntu', sans-serif"
    }
  },
  "metadata": {
    "generated_at": "2025-11-06T20:30:00Z",
    "generator": "font-manager",
    "count": 2
  }
}
```

**Usage:**
```javascript
// Node.js
const fonts = require('./fonts.json');
console.log(fonts.fonts.sans.family);

// PHP
$fonts = json_decode(file_get_contents('fonts.json'), true);
echo $fonts['fonts']['sans']['family'];
```

**Best for:** Custom integrations, build scripts

---

#### 11. W3C Design Tokens (`design_tokens`)

**Output:** `assets/fonts.tokens.json`

W3C Design Tokens specification format.

```json
{
  "font": {
    "family": {
      "sans": {
        "$value": "'Ubuntu', sans-serif",
        "$type": "fontFamily",
        "$description": "Ubuntu font family"
      }
    },
    "weight": {
      "normal": {
        "$value": 400,
        "$type": "fontWeight"
      }
    }
  }
}
```

**Usage with Style Dictionary:**
```bash
npx style-dictionary build
```

**Compatible with:**
- Style Dictionary (https://amzn.github.io/style-dictionary/)
- Figma Tokens Studio
- Design system tools

**Best for:** Design systems, token-based workflows

---

#### 12. Figma Tokens (`figma_tokens`)

**Output:** `assets/fonts.figma.json`

Figma Tokens Studio format for design-to-code sync.

```json
{
  "global": {
    "fontFamilies": {
      "sans": {
        "value": "Ubuntu",
        "type": "fontFamilies"
      }
    },
    "fontWeights": {
      "normal": {
        "value": "Regular",
        "type": "fontWeights"
      },
      "bold": {
        "value": "Bold",
        "type": "fontWeights"
      }
    }
  }
}
```

**Usage in Figma:**
1. Install Figma Tokens plugin
2. Settings → Import tokens
3. Select `fonts.figma.json`

**Best for:** Figma-to-code workflows

---

#### 13. Style Dictionary (`style_dictionary`)

**Output:** `assets/fonts.style-dict.js`

Style Dictionary configuration format.

```javascript
module.exports = {
  font: {
    family: {
      sans: {
        value: "'Ubuntu', sans-serif",
        comment: 'Ubuntu font family',
      },
    },
    weight: {
      normal: {
        value: 400,
        comment: 'Font weight 400',
      },
    },
  },
};
```

**Usage:**

Create `config/config.json`:
```json
{
  "source": ["fonts.style-dict.js"],
  "platforms": {
    "css": {
      "transformGroup": "css",
      "buildPath": "build/css/",
      "files": [{
        "destination": "fonts.css",
        "format": "css/variables"
      }]
    }
  }
}
```

Build:
```bash
npx style-dictionary build
```

**Best for:** Multi-platform design systems

---

## Framework Integration Workflows

### Bootstrap Integration

**1. Configure Export:**
```yaml
symfinity_font_manager:
    export:
        formats:
            - scss_bootstrap
```

**2. Lock Fonts:**
```bash
php bin/console fonts:lock
```

**3. Import in SCSS:**
```scss
// app.scss - Import BEFORE Bootstrap!
@import './assets/styles/fonts-bootstrap';
@import 'bootstrap/scss/bootstrap';
```

**4. Result:**
Bootstrap automatically uses your fonts for body text, headings, and code.

---

### Tailwind Integration

**1. Configure Export:**
```yaml
symfinity_font_manager:
    export:
        formats:
            - tailwind_config
```

**2. Lock Fonts:**
```bash
php bin/console fonts:lock
```

**3. Import in Tailwind Config:**
```javascript
// tailwind.config.js
const fontConfig = require('./assets/fonts-tailwind.config.js');

module.exports = {
  theme: {
    extend: {
      fontFamily: fontConfig.fontFamily,
      fontWeight: fontConfig.fontWeight,
    },
  },
};
```

**4. Use in HTML:**
```html
<p class="font-sans font-normal">Custom font text</p>
<code class="font-mono">Code with custom monospace</code>
```

---

### TypeScript Integration

**1. Configure Export:**
```yaml
symfinity_font_manager:
    export:
        formats:
            - esm_javascript
            - typescript_definitions
```

**2. Lock Fonts:**
```bash
php bin/console fonts:lock
```

**3. Import in TypeScript:**
```typescript
import { fonts, type FontFamily, type FontWeight } from './assets/fonts';

function applyFont(element: HTMLElement, family: FontFamily, weight: FontWeight) {
  element.style.fontFamily = fonts[family].family; // ✓ Type-safe
  element.style.fontWeight = weight.toString();
}

// Type errors caught at compile time:
applyFont(el, 'sans', 400);     // ✓ Valid
applyFont(el, 'invalid', 999);  // ✗ TypeScript error
```

**Best for:** Type-safe font handling

---

## Build Tool Support

### AssetMapper

**Default paths:**
- Fonts: `assets/fonts/`
- Styles: `assets/styles/`
- Config: `assets/`

**AssetMapper serves files directly - no build step required.**

**Generated files:**
```
assets/
  fonts/
    ubuntu-400.woff2
    ubuntu-700.woff2
  styles/
    fonts-variables.css
    fonts-bootstrap.scss
```

---

### Webpack/Encore

**Default paths:**
- Fonts: `assets/fonts/`
- Styles: `assets/`
- Config: `assets/`

**Webpack Configuration:**
```javascript
// webpack.config.js
Encore
  .addEntry('app', './assets/app.js')
  .copyFiles({
    from: './assets/fonts',
    to: 'fonts/[path][name].[hash:8].[ext]',
  });
```

**Import in app.js:**
```javascript
import './fonts.js';
```

**Import SCSS:**
```scss
@import './fonts-bootstrap';
@import 'bootstrap/scss/bootstrap';
```

---

### Vite

**Default paths:**
- Fonts: `assets/fonts/`
- Styles: `assets/`
- Config: `assets/`

**Vite Configuration:**
```javascript
// vite.config.js
import { defineConfig } from 'vite';

export default defineConfig({
  publicDir: 'assets/fonts',
  build: {
    outDir: 'public/build',
  },
});
```

**Import in main.js:**
```javascript
import './assets/fonts.js';
```

---

## Output Path Customization

### Auto Paths (Recommended)

```yaml
symfinity_font_manager:
    build:
        tool: 'auto'  # Detects: assetmapper, webpack, or vite
    export:
        output:
            fonts_dir: 'auto'   # Automatically configured
            styles_dir: 'auto'
            config_dir: 'auto'
```

### Custom Paths

```yaml
symfinity_font_manager:
    export:
        output:
            base_dir: '%kernel.project_dir%'
            fonts_dir: 'public/fonts'       # Direct to public/
            styles_dir: 'src/styles'        # Custom location
            config_dir: 'config/design'     # Design tokens
```

---

## Commands

### fonts:export

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

### fonts:formats

List all available export formats.

```bash
php bin/console fonts:formats
```

**Output:**
```
CSS
  css_variables      CSS Custom Properties      .css       -
  css_modules        CSS Modules Export         .module.css css_variables
  css_layer          CSS @layer Integration     .css       -

SCSS
  scss_variables     SCSS Variables             .scss      -
  scss_bootstrap     SCSS Bootstrap Variables   .scss      -
  scss_mixins        SCSS Mixins & Functions    .scss      scss_variables

JavaScript
  esm_javascript     ES Modules                 .js        -
  tailwind_config    Tailwind CSS Configuration .js        -
  typescript_definitions TypeScript Definitions  .d.ts      esm_javascript

Design System
  json               Generic JSON               .json      -
  design_tokens      W3C Design Tokens          .tokens.json -
  figma_tokens       Figma Tokens Studio        .figma.json -
  style_dictionary   Style Dictionary Format    .js        -
```

### fonts:format:info

Show detailed usage instructions for a specific format.

```bash
php bin/console fonts:format:info scss_bootstrap
```

**Output:**
```
Format: SCSS Bootstrap Variables

Name: scss_bootstrap
Extension: .scss
Default Filename: fonts-bootstrap.scss
Dependencies: None

Usage Instructions:
  Import in your SCSS BEFORE Bootstrap:
    @import './fonts-bootstrap';
    @import 'bootstrap/scss/bootstrap';
  
  This will automatically apply fonts to:
    - Body text ($font-family-base)
    - Headings ($headings-font-family)
    - Code elements ($font-family-monospace)

Export Examples:
  # Export only scss_bootstrap
  php bin/console fonts:export --format=scss_bootstrap
  
  # Export with dry-run
  php bin/console fonts:export --format=scss_bootstrap --dry-run
```

---

## Migration & Compatibility

### Backward Compatibility

**Default behavior (no export config):**
- Only `css_variables` is generated
- Works exactly like before
- No breaking changes

**Opt-in for new features:**
```yaml
symfinity_font_manager:
    export:
        formats:
            - scss_bootstrap  # Activates new export system
```

### Upgrading from v1.x

**Before (v1.x):**
```bash
php bin/console fonts:lock  # Only CSS variables
```

**After (v2.x):**
```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    export:
        formats:
            - css_variables       # Same as before
            - scss_bootstrap      # NEW: Bootstrap integration
            - tailwind_config     # NEW: Tailwind integration
```

```bash
php bin/console fonts:lock  # Exports all configured formats
```

---

## Best Practices

### Minimal Setup (Vanilla CSS)

```yaml
symfinity_font_manager:
    export:
        formats:
            - css_variables
```

### Bootstrap Project

```yaml
symfinity_font_manager:
    export:
        formats:
            - scss_bootstrap
```

### Tailwind Project

```yaml
symfinity_font_manager:
    export:
        formats:
            - tailwind_config
```

### TypeScript + Tailwind

```yaml
symfinity_font_manager:
    export:
        formats:
            - tailwind_config
            - typescript_definitions
```

### Design System (Multi-Platform)

```yaml
symfinity_font_manager:
    export:
        formats:
            - css_variables
            - scss_variables
            - esm_javascript
            - typescript_definitions
            - design_tokens
            - figma_tokens
```

---

## Troubleshooting

### Files Not Generated

Check if fonts are locked:
```bash
php bin/console fonts:status
```

Re-lock with export:
```bash
php bin/console fonts:lock
```

### Wrong Output Paths

Check detected build tool:
```bash
php bin/console fonts:export --dry-run
```

Override in config:
```yaml
symfinity_font_manager:
    build:
        tool: 'webpack'  # Force specific tool
```

### Missing Dependencies

Some formats depend on others (e.g., `css_modules` needs `css_variables`).

Check dependencies:
```bash
php bin/console fonts:format:info css_modules
```

The system automatically resolves dependencies!

---

## See Also

- [Configuration Reference](configuration.md) - Full configuration options
- [Commands Reference](commands.md) - All CLI commands
- [Usage Guide](usage.md) - Twig function usage
