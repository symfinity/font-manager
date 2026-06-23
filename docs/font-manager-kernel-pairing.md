# Font manager ↔ UI Kernel pairing

Cross-package typography alignment for Symfinity ui-kernel consumers.

## Option A (css_variables bridge)

Font-manager exports semantic aliases; kernel tokens are mapped in **consumer CSS** — no ui-kernel schema change in v1.

| Font-manager export | Kernel doc token | Consumer bridge |
|---------------------|------------------|-----------------|
| `--font-family-sans` | `--ui-font-family-sans` | `var(--font-family-sans)` |
| `--font-family-mono` | `--ui-font-family-mono` | `var(--font-family-mono)` |
| `--font-heading` | *(none v1)* | use directly in theme CSS |

Example project bridge:

```css
@import './assets/styles/fonts-variables.css';

[data-theme] {
  --ui-font-family-sans: var(--font-family-sans);
  --ui-font-family-mono: var(--font-family-mono);
}
```

Kernel `--ui-font-family-*` tokens expect a consumer bridge as shown above; see the **ui-kernel** handbook for theme token vocabulary.

## ui-themer convention

Theme YAML **may** record a pairing id for human workflow — **not** validated or auto-imported by ui-themer:

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
