# Innesto

[![CI](https://github.com/dirnbauer/innesto/actions/workflows/ci.yml/badge.svg)](https://github.com/dirnbauer/innesto/actions/workflows/ci.yml)
[![TYPO3 14.3](https://img.shields.io/badge/TYPO3-14.3-orange.svg)](https://get.typo3.org/version/14)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4%2B-777bb3.svg)](https://www.php.net/supported-versions.php)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

## What it is

*Innesto* is Italian for a graft. It grafts [shadcn/ui](https://ui.shadcn.com) registry components onto TYPO3 as editor-managed Content Blocks styled by [Desiderio](https://github.com/dirnbauer/desiderio): one command fetches a registry item, converts its CSS to Desiderio's semantic tokens and scaffolds a complete content element; a finishing pass turns the React markup into Fluid that composes Desiderio components.

Nineteen finished grafts ship with it — marquee, orbiting circles, terminal, case studies and the complete 15-element [blocks.so stats family](https://blocks.so/stats). The frontend is Fluid 5, CSS and SVG; only the area chart needs a small JavaScript renderer. The upstream TSX stays in `sources/` for provenance: there is no React runtime and no frontend build step.

Because every graft composes Desiderio components and paints itself only from Desiderio tokens, it follows every preset and dark mode without knowing they exist. Three test layers hold it to that contract: a composition test (which components a template may use), a package test (metadata, collection tables, token-only styling, demo data) and Desiderio's own Fluid 5 linter.

## Requirements

- TYPO3 14.3 LTS
- PHP 8.4+ (CI runs 8.4 and 8.5)
- Desiderio 4.3+ and Content Blocks 2.4+
- `typo3/cms-fluid-styled-content` — Desiderio's site set depends on it

## Install

Composer does not inherit repositories from dependencies, so register all three in the consuming project:

```bash
composer config repositories.desiderio vcs https://github.com/dirnbauer/desiderio.git
composer config repositories.visual-editor-enhancements vcs https://github.com/dirnbauer/typo3-visual-editor-enhancements.git
composer config repositories.innesto vcs https://github.com/dirnbauer/innesto.git
composer require webconsulting/innesto:^2.3
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

## Configure

Add the site set to the site that should offer the grafts:

```yaml
# config/sites/<identifier>/config.yaml
dependencies:
  - webconsulting/innesto
```

That pulls in Desiderio and registers every shipped block in the content wizard. Desiderio-based sites restrict the wizard to what their set graph lists, so removing an entry from `Configuration/Sets/Innesto/config.yaml` hides an element without uninstalling anything — existing records keep rendering. An existing installation keeps its CTypes, fields and collection tables.

## Use

```bash
# Graft a NEW element; the 19 shipped keys are refused.
vendor/bin/typo3 innesto:add magicui/marquee --key partner-marquee

# Fill a dedicated, existing page with demo content.
vendor/bin/typo3 innesto:seed <page-uid>
vendor/bin/typo3 innesto:seed <page-uid> --element terminal
```

`innesto:add` converts the registry CSS and writes `config.yaml`, a Fluid stub, the upstream source, a backend preview, labels, an icon and a finishing prompt. Translating React state and modelling editor fields is the finishing pass — by hand, or with `--ai` and the installed Claude CLI.

`innesto:seed` takes demo values from each element's `library.json` and writes through DataHandler. A rerun skips CTypes that already exist on the page, hidden records included. `--force` replaces the records of the selected CTypes, collection children and all; the transaction rolls back if DataHandler reports an error.

## Develop

```bash
composer install
composer ci                     # cgl, phpstan, unit, functional
composer ci:tests:unit
composer ci:tests:functional    # SQLite, no database server needed
composer ci:phpstan             # level 8, no baseline
composer ci:cgl -- --dry-run
docker run --rm -v $PWD:/project ghcr.io/typo3-documentation/render-guides:latest --config=Documentation
```

Functional tests use isolated SQLite databases and never touch a local demo database. `ci:tests:unit` alone checks every shipped element against the package contract, so it is also the checklist a new graft has to pass.

## Docs

Full manual in [`Documentation/`](Documentation/Index.rst): what a graft is, installation and the site set, configuration, the command reference and the finishing checklist, and a developer reference covering the component contract and the test suites.

## License

GPL-2.0-or-later. Preserved upstream sources keep their original licenses.
