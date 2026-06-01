# Legacy review — symfinity/font-manager

**Status**: Executed (M3, 2026-05-31)  
**Created**: 2026-05-31

## Purpose

Understand the migrated package before feature specs or refactors. Gate for wave-1 B3 / intake milestone M3.

## Scope

- Architecture and module boundaries (providers, exporters, lock workflow)
- Dependencies (Composer, Symfony, AssetMapper dev usage)
- Release slice vs intake (`r1_must` / `defer` / `drop`)
- Exporter registration policy vs Flex recipe defaults
- Legacy risks and technical debt
- Test coverage gaps
- Modernization opportunities (candidates only)
- Safe vs dangerous refactor areas
- google-fonts successor documentation gaps

## Anti-patterns

- No blind rewrites
- No speculative abstractions
- No uncontrolled modernization
- No removing legacy code without relevance analysis
- No enabling all 13 exporters by default in Flex recipe

## Deliverables (Phase 4)

1. Architecture overview — [report.md](./report.md#1-architecture-overview)
2. Relevance/risk matrix (min 8 rows) — [report.md](./report.md#2-relevancerisk-matrix)
3. Modernization candidates — [report.md](./report.md#3-modernization-candidates)
4. Refactor assessment — [report.md](./report.md#4-refactor-assessment)
5. Dependency bottlenecks — [report.md](./report.md#5-dependency-bottlenecks)

## M3 checklist (intake release slice)

| Item | Tier | Result |
|------|------|--------|
| Core bundle + providers | r1_must | Pass |
| Twig `font_manager()` + lock manifest | r1_must | Pass |
| CLI lock/search/status/prune + migrate-from-google-fonts | r1_must | Pass |
| Flex recipe minimal (`css_variables` only) | r1_must | Pass — `recipes/symfinity/font-manager/0.1/` |
| Migration guide font-manager | r1_must | Pass — [docs/migration.md](../../docs/migration.md) |
| Migration guide google-fonts | r1_must | Pass — [docs/migration-from-google-fonts.md](../../docs/migration-from-google-fonts.md) |
| Exporter policy documented | r1_must | Pass — [docs/exporter-policy.md](../../docs/exporter-policy.md) |
| Bunny/Fontsource recipe defaults | defer | Deferred — off in recipe; enable in app config |
| Export formats beyond css_variables | defer | Deferred — opt-in per [exports.md](../../docs/exports.md) |
| Infection in default CI | defer | Deferred — no package `.github/` in monorepo |
| Packagist / dual abandon | M4 | Scheduled — first public symfinity release ([checklist](../../docs/m4-public-release-checklist.md)) |

## Boundary

- No `specs/002+` until maintainer approves Phase 4 report
- Feature specs use normal Spec Kit in product repo when needed

## Verdict

See [report.md](./report.md#verdict) — port track complete; M4 bundled with first public symfinity release.
