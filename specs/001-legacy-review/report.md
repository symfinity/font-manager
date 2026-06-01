# Legacy Review Report — symfinity/font-manager

**Date**: 2026-05-31  
**Source**: `src/import-neuralglitch/packages/font-manager` (neuralglitch)  
**Migrated**: `packages/font-manager/`  
**QA**: 257 PHPUnit tests, PHPStan max, consumer `make test` green; M2 recipe + dogfood smoke green

## 1. Architecture overview

| Area | Summary |
|------|---------|
| **Role** | Symfony bundle — multi-provider webfont delivery, dev CDN + prod lock, Twig `font_manager()`, CLI workflow |
| **Entry** | `FontManagerBundle` → `FontManagerExtension` loads `config/services.yaml` + `font_manager` config tree |
| **Twig** | `FontManagerExtension` + lazy `FontManagerRuntime` (CDN vs locked manifest) |
| **Providers** | `ProviderRegistry` — Google, Bunny, Fontsource, Local |
| **Lock workflow** | `FontLockManager`, `FontDownloader`, manifest JSON, `fonts:lock` scans templates |
| **Exporters** | `ExporterRegistry` — 13 formats; `ExporterOrchestrator` + `FormatAutoDetector` on lock |
| **Integrations** | HTTP client (providers), optional AssetMapper (dev, lock paths), no DB/Messenger |

**Major `src/` modules**

| Path | Purpose |
|------|---------|
| `DependencyInjection/` | Config tree, extension loader |
| `Provider/` | CDN/link generation per backend |
| `Service/` | Download, lock, build-tool detect, performance/fallback helpers |
| `Exporter/` | CSS, SCSS, JS, design-token export formats |
| `Twig/` | `font_manager()` |
| `Command/` | `fonts:lock`, `search`, `status`, `prune`, `validate`, `export`, `migrate-from-google-fonts`, … |
| `Enum/` / `Model/` | Font variants, collections |

## 2. Relevance/risk matrix

| Area | Relevance | Risk | Notes |
|------|-----------|------|-------|
| Config `font_manager:` prefix | High | Safe | Unchanged from legacy; recipe copies minimal defaults |
| Twig `font_manager()` API | High | Safe | Signature stable; primary consumer contract |
| Provider HTTP (Google/Bunny/CDN) | High | Caution | Network + third-party uptime; tests mock HTTP |
| Lock manifest + `assets/fonts/` | High | Caution | Prod path; `fonts:lock` may fetch remote fonts |
| 13 exporters in DI | High | Safe | Registered in container; **enabled** only via config `export.formats` |
| Flex recipe defaults | High | Safe | M2: `google` + `css_variables` only; Bunny/Fontsource off in recipe |
| `fonts:migrate-from-google-fonts` | High | Safe | Successor path for `neuralglitch/google-fonts` |
| PHPUnit suite | High | Safe | 257 tests; monorepo via `mono qa:test` |
| README / badges | Medium | Safe | M3: aligned floors; removed stale CI/Packagist badges |
| Package `.github/` CI | Low | Safe | Not ported; defer Infection/Psalm in product CI |
| ui-kernel pairing | Medium | Safe | Optional `suggest`; no hard dependency |
| Production CDN defaults | Medium | Dangerous | Recipe + docs: enable lock in prod (`when@prod`) |

## 3. Modernization candidates

| Candidate | Effort | Dependency |
|-----------|--------|------------|
| Wire unused `FontManagerRuntime` performance deps (0.3.0 placeholders) | M | Product need for resource hints |
| Package-level Psalm/Infection in monorepo QA profile | S | Defer per intake; optional later |
| Symfony 8.x floor bump | M | Org **048** before widen |
| ui-kernel dogfood pairing demo | S | **009** pairing doc exists |
| Split-repo CI publish | M | M4 Packagist + mirror |
| Trim `ExporterOrchestrator` auto-detect default | S | M3 doc policy only |

## 4. Refactor assessment

**Safe zones**

- README, migration docs, configuration examples
- Recipe snapshot under `recipes/symfinity/font-manager/`
- PHPStan ignore lines for prepared-for-future properties

**Dangerous zones**

- Changing Twig function signature or default provider URLs (template BC)
- Removing exporters without deprecation (CLI `fonts:formats` lists all registered)
- Default-enabling Tailwind/Bootstrap exporters in Flex recipe (063 / intake defer)
- Disabling google-fonts migrate command before G5 abandon

**Dead-code suspicions**

- `FontManagerRuntime` injected performance/variable-font services — prepared for 0.3.0; PHPStan suppressed; keep until feature lands or remove with explicit spec.

## 5. Dependency bottlenecks

| Topic | State |
|-------|--------|
| PHP | `>=8.2` — matches symfinity consumer |
| Symfony | `^7.4` — org import floor |
| Asset Mapper | `require-dev` in package; lock workflow in dev/test |
| Cross-package | No require on `ui-kernel`; optional pairing via `css_variables` export |
| Monorepo discovery | Path repo `packages/*`; slug `font-manager` matches Composer name |
| Wave-1 abandon | Blocked on M4 G2/G5 — both `neuralglitch/font-manager` and `google-fonts` → same replacement |

## M3 fixes applied

1. **README** — PHP 8.2+ / Symfony 7.4+ badges; removed stale GitHub Actions and Packagist badges; fixed migration section (neuralglitch + google-fonts links); quick start uses **google** default (matches recipe).
2. **`docs/exporter-policy.md`** — documents DI registration vs config-enabled formats (r1_must exporter policy).
3. **`docs/configuration.md`** — Symfinity default vs full example; link to exporter policy.
4. **`docs/migration.md`** — Flex recipe pointer; cross-link google-fonts successor doc.
5. This spec + report under `specs/001-legacy-review/`.

## Verdict

**No maintainer POLL required** — direct rename port; public API and `font_manager:` config prefix unchanged. Recipe + migration docs satisfy wave-1 **G3**. **M4** (Packagist + dual G5 abandon) scheduled **before or with** first public `symfinity/symfinity` release — G0–G3 done, **no blockers**. Runbook: [m4-public-release-checklist.md](../../docs/m4-public-release-checklist.md).
