# Font manager ↔ UI Kernel pairing

Cross-package typography alignment for **symfinity/font-manager** + **symfinity/ui-kernel** consumers.

## Problem

- **ui-kernel** always emits `--ui-font-family-sans` / `--ui-font-family-mono` (system stacks in built-in themes).
- **font-manager** loads webfonts and sets per-family vars such as `--font-family-ubuntu`.
- **UX Blocks** and kernel tokens read `--ui-font-family-*` — not hardcoded family names.

Without a bridge, body, components, and page CSS can each use a different stack.

## Recommended setup (v0.2.6+)

### 1. Enable the bridge in config

```yaml
# config/packages/symfinity_font_manager.yaml
symfinity_font_manager:
    ui_kernel_bridge: true
```

### 2. Template order (critical)

```twig
{# 1. Load webfonts (monospace arg BEFORE display — see usage.md) #}
{{ font_manager('Ubuntu', '300 400 700', 'normal italic', false, 'swap') }}
{{ font_manager('Ubuntu Mono', '400 500', 'normal', true, 'swap') }}

{# 2. Kernel theme tokens (system stacks — bridge overrides next) #}
{{ ui_kernel_css('default-dark')|raw }}

{# 3. Bridge font-manager vars → --ui-font-family-* (after kernel CSS) #}
{{ font_manager_ui_kernel_bridge()|raw }}
```

Use **`var(--ui-font-family-sans)` / `var(--ui-font-family-mono)`** in app CSS — never hardcode loaded family names.

### 3. Locked fonts / pairings

When `ui_kernel_bridge: true`, `fonts:lock` also adds kernel aliases to `fonts-variables.css`:

```css
--ui-font-family-sans: var(--font-family-sans);
--ui-font-family-mono: var(--font-family-mono);
```

Load that file **after** `ui_kernel_css()` (same as the Twig bridge).

## Manual bridge (legacy / custom selectors)

If you cannot use `ui_kernel_bridge` yet, map semantic aliases in consumer CSS:

| Font-manager export | Kernel token | Bridge value |
|---------------------|--------------|--------------|
| `--font-family-sans` | `--ui-font-family-sans` | `var(--font-family-sans)` |
| `--font-family-mono` | `--ui-font-family-mono` | `var(--font-family-mono)` |

```css
@layer kernel.profile {
  [data-ui-root] {
    --ui-font-family-sans: var(--font-family-sans);
    --ui-font-family-mono: var(--font-family-mono);
  }
}
```

Per-family Twig calls without pairings use `--font-family-{slug}` — prefer `ui_kernel_bridge` or assign semantic roles via `fonts:import-pairing`.

## ui-designer convention

Theme YAML **may** record a pairing id for human workflow — **not** validated or auto-imported by ui-designer:

```yaml
metadata:
  display_name: Editorial Brand
  font_pairing: editorial
```

Run `fonts:import-pairing @fonttrio/editorial` separately after choosing a colour theme.

## Import workflow

```bash
php bin/console fonts:import-pairing @fonttrio/editorial
php bin/console fonts:lock
```

See [commands.md](./commands.md#fontsimport-pairing) and [exports.md](./exports.md).

## Non-goals

- ui-kernel does **not** require font-manager at runtime.
- No automatic Packagist coupling — enable `ui_kernel_bridge` when both packages are installed.
