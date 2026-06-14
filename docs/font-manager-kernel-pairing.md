# Font manager ↔ UI Kernel pairing

Cross-package typography alignment for Symfinity Chameleon / ui-kernel consumers.

## Option A (056 — locked)

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

Normative kernel contract: [font-manager-pairing](../../../../specs/symfinity/symfinity/2-ui-kernel/contracts/font-manager-pairing.md).

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
