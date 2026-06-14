# Contract: Pairing preset catalog

**Feature**: symfinity **056**  
**Package**: `packages/font-manager/`  
**Config root**: `font_manager.pairings`  
**Status**: Normative for implement

## Purpose

Declare approved Fonttrio pairings in Symfony config for idempotent re-import, team policy, and cross-package references (ui-themer `metadata.font_pairing`) — without shipping preset JSON inside Flex recipe defaults.

---

## Config tree

```yaml
font_manager:
    pairings:
        # Last imported pairing id (optional; set by fonts:import-pairing)
        active: editorial

        # Approved presets — source slug or URL
        catalog:
            editorial:
                source: '@fonttrio/editorial'
                label: 'Editorial'
                categories: [serif, editorial, elegant]

            # Additional entries added by maintainer or future recipe opt-in
            # swiss:
            #     source: '@fonttrio/swiss'
            #     label: 'Swiss Neo-Grotesk'

        # Written by import adapter — semantic role → font slug
        active_roles:
            body: source-serif-4
            heading: playfair-display
            mono: jetbrains-mono
```

| Key | Required | Notes |
|-----|----------|-------|
| `pairings.catalog.{id}.source` | yes | `@fonttrio/{slug}` or HTTPS URL |
| `pairings.catalog.{id}.label` | no | Defaults to registry `title` on first import |
| `pairings.catalog.{id}.categories` | no | Informational; from registry |
| `pairings.active` | no | Set after successful import |
| `pairings.active_roles` | no | `{ body, heading, mono }` → font config slug |

---

## Catalog operations

| Operation | Command / trigger | Behavior |
|-----------|---------------------|----------|
| Import by id | `fonts:import-pairing @fonttrio/editorial` | Fetch, merge fonts, set `active` + `active_roles` |
| Import from catalog | `fonts:import-pairing editorial` (optional sugar) | Resolve `catalog.editorial.source` |
| Import all catalog | `fonts:import-pairing --all-catalog` | Sequential import; last wins `active` — **SHOULD** warn |
| List catalog | `fonts:status` extended output | Show catalog ids + active pairing |

**Idempotency**: Re-importing same pairing **MUST NOT** duplicate font entries; **MAY** update weights/subsets if registry changed (log warning).

---

## Option A — export alias table (normative)

When `pairings.active_roles` is set and fonts are locked, `css_variables` exporter **MUST** apply:

| Role key | Source cssVars key | Exported semantic variable | Kernel doc mapping |
|----------|-------------------|----------------------------|-------------------|
| `body` | `--font-body` | `--font-family-sans` | `--ui-font-family-sans` (consumer CSS) |
| `mono` | `--font-mono` | `--font-family-mono` | `--ui-font-family-mono` |
| `heading` | `--font-heading` | `--font-heading` | No kernel token v1 |

Per-family vars (`--font-{slug}` from Fonttrio `font.variable`) **MUST** precede semantic aliases in output file order.

**MUST NOT** rename exporter output file path from M2 default (`assets/styles/fonts-variables.css`) unless consumer config overrides.

---

## Provenance block (export metadata)

`css_variables` or companion `json` export **MAY** include metadata (optional v1 — document in README if deferred):

```json
{
  "metadata": {
    "font_pairing": {
      "id": "editorial",
      "source": "https://www.fonttrio.xyz/r/editorial.json",
      "imported_at": "2026-06-07T12:00:00Z",
      "adapter": "fonttrio-v1"
    }
  }
}
```

---

## ui-themer cross-link (documentation only)

ui-themer theme YAML **MAY** reference the same pairing id:

```yaml
metadata:
  display_name: Editorial Brand
  font_pairing: editorial   # convention — not validated by ui-themer in 056
```

| MUST | MUST NOT |
|------|----------|
| Document convention in font-manager + ui-themer horizon docs | ui-themer auto-run `fonts:import-pairing` on theme load |
| Use matching id string as `pairings.catalog` key | Import colour theme JSON in 056 |

See [figma-import-horizon](../_org/contracts/ui-themer/figma-import-horizon.md) program note.

---

## Flex recipe boundary

Default `recipes/symfinity/font-manager/0.1` **MUST NOT** enable `pairings.catalog` entries — keeps recipe minimal per intake. Consumers opt in via config or post-install docs.

Optional future recipe version **MAY** ship commented catalog examples.

---

## Fixtures (tests + dogfood)

| File | Purpose |
|------|---------|
| `tests/Fixtures/Fonttrio/editorial.json` | Style registry snapshot (committed) |
| `tests/Fixtures/Fonttrio/playfair-display.json` | Font dependency |
| `tests/Fixtures/Fonttrio/source-serif-4.json` | Font dependency |
| `tests/Fixtures/Fonttrio/jetbrains-mono.json` | Font dependency |
| `fixtures/font-recipe-dogfood/` | Extend M2 smoke: import editorial fixture → lock |

Commit fixtures from live registry at implement time; record snapshot date in fixture README comment.

---

## Validation

| Check | Expected |
|-------|----------|
| Unknown catalog id | CLI error before network |
| `active_roles.body` missing after import | Adapter error — body role required |
| Export without lock | Existing font-manager behavior unchanged |
| Catalog empty | CLI slug/URL import still works |
