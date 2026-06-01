# M4 public release checklist — symfinity/font-manager

**Status:** Scheduled — execute **before or with** the first public `symfinity/symfinity` product release.  
**Intake port track:** M0–M3 **done** (2026-05-31). **No blockers** for M4 timing.  
**Program:** [wave-1 migration](../../../../../classified/explore/ORG-neuralglitch-packagist-wave-1-migration.md)

Wave-1 **font-manager** M4 runs in the **same release batch** as `symfinity/omnia-ipsum` and other first-public V0 utilities — not as a standalone early abandon.

## Preconditions (done)

| Gate | State |
|------|--------|
| **G0** | symfinity **001** consumer dogfood — done |
| **G1** | Port + QA green — 257 PHPUnit, PHPStan max, `make test` |
| **G2 prep** | Recipe `recipes/symfinity/font-manager/0.1/` validates |
| **G3** | [migration.md](./migration.md), [migration-from-google-fonts.md](./migration-from-google-fonts.md) |

## Execute with first public symfinity release

Run in order when the maintainer cuts the **first public symfinity product** wave (split + Packagist + recipes publish):

### 1. Split mirror + tag (G2)

From `symfinity/symfinity` monorepo (container QA green):

```bash
# Example — exact mono commands per repo docs at release time
vendor/bin/mono release:publish --package=symfinity/font-manager --dry-run
vendor/bin/mono release:publish --package=symfinity/font-manager
```

- Split mirror repo receives tag (changed-only split policy).
- Register **`symfinity/font-manager`** on Packagist if not already registered.

### 2. Flex recipe publish (G2)

```bash
vendor/bin/mono recipes:validate
vendor/bin/mono recipes:publish --dry-run
# publish symfinity/font-manager 0.1 sources → symfinity/recipes main; flex/main via CI
```

Consumer Flex endpoint: **`symfinity/recipes` `flex/main`** (not legacy `neuralglitch/symfony-recipes`).

### 3. Verify install (G2 exit)

In a greenfield or dogfood consumer:

```bash
composer require symfinity/font-manager
# Flex applies recipe; bundle dev/test; font_manager.yaml minimal
php bin/console fonts:status
```

### 4. Abandon legacy Packagist (G5) — after G2 true

**Manual** neuralglitch Packagist account (not product `mono.json`):

| Abandon | `replacement=` |
|---------|----------------|
| `neuralglitch/font-manager` | `symfinity/font-manager` |
| `neuralglitch/google-fonts` | `symfinity/font-manager` |

Toolkit (when split mirror registered): `vendor/bin/mono packagist:abandon` — see mono **052**; **neuralglitch/** packages may require Packagist UI or neuralglitch API token.

**Hard rule:** G5 only after `composer require symfinity/font-manager` works from Packagist.

### 5. Optional (G4)

- Article/tutorial addendum: neuralglitch → symfinity namespace table.
- google-fonts readers: link [migration-from-google-fonts.md](./migration-from-google-fonts.md).

### 6. Post-abandon (G6)

Archive legacy `github.com/neuralglitch/font-manager` (and google-fonts repo) **after** G5, per wave-1 program.

## Intake M4 completion criteria

Mark intake **M4 done** when all are true:

- [ ] `symfinity/font-manager` installable from Packagist with stable tag
- [ ] Recipe on `symfinity/recipes` Flex endpoint
- [ ] G5 abandon for **both** `neuralglitch/font-manager` and `neuralglitch/google-fonts`
- [ ] Intake + ROADMAP rows updated

## Not in M4

- Chameleon **V1** packages (ui-kernel publish strategy is separate)
- Assigning semver in planning docs — tag at release time only (**21**)
- Infection/Psalm in default product CI (deferred per intake)
