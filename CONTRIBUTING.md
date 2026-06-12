# Contributing to Font Manager

> **Read-only mirror.** This repository (`symfinity/font-manager`) is published from the Symfinity product monorepo (`packages/font-manager/`). **Do not expect pull requests to be merged here.**

## Report bugs or request features

Open an [issue](https://github.com/symfinity/font-manager/issues) on this repository.

## Contribute code

### External contributors

1. Open an issue describing the change (or comment on an existing one).
2. Open a **proposal pull request** against `main` on this repository **or** attach a patch to the issue.
3. Maintainers review here but **land the change in the product monorepo**. Your split PR will be **closed** with a link to the monorepo commit or PR when shipped.
4. This mirror updates on the next release split — there is no separate merge on this repo.

### Symfinity org collaborators

Open a pull request on the **product monorepo** (`packages/font-manager/`) on a `feature/{spec-id}-{slug}` branch. Do not rely on merging on this mirror.

## Quality bar

Before we port your patch:

- PHPUnit and PHPStan pass (see [.github/workflows/ci.yml](.github/workflows/ci.yml))
- Tests for behavior changes
- CHANGELOG entries are added by maintainers at release time unless requested

## Security

Report vulnerabilities privately — see [.github/SECURITY.md](.github/SECURITY.md). Do not file security issues in public GitHub issues.
