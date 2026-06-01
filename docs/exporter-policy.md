# Exporter policy (Symfinity port)

## Container vs configuration

All **13** export format services are registered in the bundle `config/services.yaml` and listed in `ExporterRegistry`. That keeps CLI discovery (`fonts:formats`, `fonts:format:info`, `fonts:export`) working without extra wiring.

**Which formats run** is controlled only by application config:

```yaml
font_manager:
    export:
        formats:
            - css_variables   # Symfinity Flex recipe default (only this line)
```

Omitting a format from `export.formats` means it is **not** generated on `fonts:lock` or auto-export — even though the exporter service exists in the container.

## Symfinity defaults (V0)

| Layer | Policy |
|-------|--------|
| Flex recipe `0.1` | `css_variables` only |
| Shipped package `config/packages/font_manager.yaml` | Same as recipe |
| Full multi-format setup | Opt-in in app config — see [exports.md](./exports.md) |

**Deferred for default recipe** (intake): `tailwind_config`, `scss_bootstrap`, Figma/style-dictionary exports, `export.auto_detect: true`.

## ui-kernel pairing

When `symfinity/ui-kernel` is installed, consumers **may** align `css_variables` export names with `--ui-font-family-*` tokens. See ui-kernel `docs/font-manager-pairing.md` (when published in split). No automatic runtime coupling in v0.

## Related

- [configuration.md](./configuration.md) — full config tree
- [exports.md](./exports.md) — format reference
- [migration-from-google-fonts.md](./migration-from-google-fonts.md) — legacy google-fonts successor
