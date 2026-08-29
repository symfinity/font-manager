# Exporter policy (Symfinity port)

## Container vs configuration

All **13** export format services are registered in the bundle `config/services.yaml` and listed in `ExporterRegistry`. That keeps CLI discovery (`fonts:formats`, `fonts:format:info`, `fonts:export`) working without extra wiring.

**Which formats run** is controlled only by application config:

```yaml
symfinity_font_manager:
    export:
        formats:
            - css_variables   # Symfinity Flex recipe default (only this line)
```

Omitting a format from `export.formats` means it is **not** generated on `fonts:lock` or auto-export — even though the exporter service exists in the container.

## Symfinity defaults (V0)

| Layer | Policy |
|-------|--------|
| Flex recipe `0.1` | `css_variables` only |
| Shipped package `config/packages/symfinity_font_manager.yaml` | Same as recipe |
| Full multi-format setup | Opt-in in app config — see [exports.md](./exports.md) |

**Deferred for default recipe:** `tailwind_config`, `scss_bootstrap`, Figma/style-dictionary exports, `export.auto_detect: true`.

## ui-kernel pairing

When `symfinity/ui-kernel` is installed, set `ui_kernel_bridge: true` in `symfinity_font_manager` config. Font-manager then:

- emits `font_manager_ui_kernel_bridge()` Twig output mapping `--ui-font-family-*` after kernel CSS
- adds `--ui-font-family-sans/mono` aliases in `fonts-variables.css` on `fonts:lock`

See [font-manager-kernel-pairing.md](./font-manager-kernel-pairing.md) and ui-kernel `docs/font-manager-pairing.md`.

## Related

- [configuration.md](./configuration.md) — full config tree
- [exports.md](./exports.md) — format reference
- [migration-from-google-fonts.md](./migration-from-google-fonts.md) — legacy google-fonts successor
