# Contract: Fonttrio pairing adapter

**Feature**: symfinity **056**  
**Package**: `packages/font-manager/`  
**Port**: `FontPairingImportPort` → `FonttrioPairingAdapter`  
**Status**: Normative for implement

## Purpose

Parse Fonttrio registry JSON (`registry:style` pairings and `registry:font` dependencies) into font-manager config entries and semantic role mapping — without Node tooling or ui-kernel coupling.

**Registry base URL**: `https://www.fonttrio.xyz/r/{name}.json`

**Reference pairing (live)**: [editorial.json](https://www.fonttrio.xyz/r/editorial.json)

---

## Input shapes

### `registry:style` (pairing)

| Field | Required | Type | Notes |
|-------|----------|------|-------|
| `name` | yes | string | Pairing id (e.g. `editorial`) |
| `type` | yes | string | **MUST** be `registry:style` |
| `registryDependencies` | yes | string[] | HTTPS URLs to `registry:font` items |
| `cssVars.theme` | yes | map | Keys include `--font-body`, `--font-heading`, `--font-mono` (subset allowed with warn) |
| `title` | no | string | Display label for catalog |
| `description` | no | string | |
| `categories` | no | string[] | |
| `meta` | no | object | mood, useCase, appearance — preserved in provenance only |

**Example** (truncated):

```json
{
  "name": "editorial",
  "type": "registry:style",
  "registryDependencies": [
    "https://www.fonttrio.xyz/r/playfair-display.json",
    "https://www.fonttrio.xyz/r/source-serif-4.json",
    "https://www.fonttrio.xyz/r/jetbrains-mono.json"
  ],
  "cssVars": {
    "theme": {
      "--font-heading": "var(--font-playfair-display)",
      "--font-body": "var(--font-source-serif-4)",
      "--font-mono": "var(--font-jetbrains-mono)"
    }
  }
}
```

### `registry:font` (dependency)

| Field | Required | Type | Notes |
|-------|----------|------|-------|
| `name` | yes | string | Registry slug |
| `type` | yes | string | **MUST** be `registry:font` |
| `font.family` | yes | string | Human family name |
| `font.provider` | yes | string | v1: **`google`** only; others → explicit error |
| `font.import` | yes | string | Provider import id (e.g. `Playfair_Display`) |
| `font.variable` | yes | string | CSS var without value (e.g. `--font-playfair-display`) |
| `font.weight` | yes | int[] | Subset to lock |
| `font.subsets` | no | string[] | Default `latin` when absent |

**Example** (truncated):

```json
{
  "name": "playfair-display",
  "type": "registry:font",
  "font": {
    "family": "Playfair Display Variable",
    "provider": "google",
    "import": "Playfair_Display",
    "variable": "--font-playfair-display",
    "weight": [400, 500, 600, 700, 800, 900],
    "subsets": ["latin", "latin-ext"]
  }
}
```

---

## Port interface

```php
interface FontPairingImportPort
{
    /**
     * @return PairingImportResult pairing id, font config fragments, semantic roles
     */
    public function import(string $source): PairingImportResult;
}
```

| Method | Input | Behavior |
|--------|-------|----------|
| `import` | `@fonttrio/{slug}` | Resolve to `{baseUrl}/{slug}.json`, fetch, parse |
| `import` | `https://…/r/{slug}.json` | Fetch URL directly |
| `import` | local filesystem path | **SHOULD** support for tests/fixtures only |

---

## Resolution algorithm

```text
1. Normalize source → fetch style JSON
2. Assert type === registry:style
3. For each URL in registryDependencies:
     a. Fetch font JSON (or load fixture)
     b. Assert type === registry:font
     c. Build font_manager font entry:
          - slug: kebab from registry name
          - provider: google
          - family / weights / subsets from font.*
          - css_variable: font.variable (stored for export)
4. Parse cssVars.theme:
     - Resolve --font-body / --font-heading / --font-mono → font.variable targets
     - Build semantic role map (Option A)
5. Return PairingImportResult { id, fonts[], roles, provenance }
```

| MUST | MUST NOT |
|------|----------|
| Detect duplicate dependency URLs | Follow `extends` on style items in v1 (Fonttrio `extends: none` only) |
| Fail on unknown `registry:style` type | Import Fonttrio `css` @layer blocks into bundle assets |
| Pin User-Agent or Accept headers minimally | Require Fonttrio API keys |
| Record `source_url` + `fetched_at` in provenance | Mutate lock manifest during parse-only import |

---

## Config merge output

Each resolved font **MUST** merge into existing `font_manager.fonts` (or bundle-equivalent key) without duplicating same `import` + provider:

```yaml
# Illustrative — exact keys match bundle Configuration.php at implement
symfinity_font_manager:
    fonts:
        playfair-display:
            provider: google
            family: 'Playfair Display Variable'
            import: Playfair_Display
            weights: [400, 500, 600, 700, 800, 900]
            subsets: [latin, latin-ext]
            css_variable: '--font-playfair-display'
    pairings:
        active: editorial
        catalog:
            editorial:
                source: '@fonttrio/editorial'
                label: 'Editorial — Playfair Display + Source Serif 4 + JetBrains Mono'
```

Semantic roles stored under `pairings.active_roles` or equivalent (see [pairing-preset-catalog](./pairing-preset-catalog.md)).

---

## Export interaction (Option A)

After lock, `css_variables` exporter **MUST** emit:

```css
:root {
  /* Per-family (from font.variable) */
  --font-playfair-display: 'Playfair Display Variable', serif;
  --font-source-serif-4: 'Source Serif 4', serif;
  --font-jetbrains-mono: 'JetBrains Mono', monospace;

  /* Semantic aliases — Option A */
  --font-family-sans: var(--font-source-serif-4);   /* body role */
  --font-family-mono: var(--font-jetbrains-mono);
  --font-heading: var(--font-playfair-display);     /* font-manager alias; not kernel token */
}
```

Kernel consumer **MAY** add project CSS:

```css
[data-theme] {
  --ui-font-family-sans: var(--font-family-sans);
  --ui-font-family-mono: var(--font-family-mono);
}
```

---

## CLI

```bash
# Slug form
php bin/console fonts:import-pairing @fonttrio/editorial

# URL form
php bin/console fonts:import-pairing https://www.fonttrio.xyz/r/editorial.json

# Dry-run (no config write)
php bin/console fonts:import-pairing @fonttrio/editorial --dry-run

# Then existing lock path
php bin/console fonts:lock
```

| Flag | Behavior |
|------|----------|
| `--dry-run` | Parse + print merge diff; exit 0 |
| `--no-lock` | Skip auto-lock prompt after import (default: prompt or manual lock) |

---

## Validation

| Check | Expected |
|-------|----------|
| Unit test with `editorial.json` + font fixtures | 3 fonts; roles body/heading/mono resolved |
| Unknown type | `InvalidFonttrioRegistryException` |
| Non-google provider | Clear error; exit non-zero |
| Missing dependency URL | Error lists broken URL |
| Offline CI | No network in default PHPUnit group |

---

## Non-goals

- Download Fonttrio component CSS or `@layer base` rules
- ui-themer theme YAML writer
- Automatic ui-kernel CssGenerator patch
