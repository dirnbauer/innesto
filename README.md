# Innesto

[![CI](https://github.com/dirnbauer/innesto/actions/workflows/ci.yml/badge.svg)](https://github.com/dirnbauer/innesto/actions/workflows/ci.yml)

Innesto turns shadcn registry components into editor-managed TYPO3 Content
Blocks styled by [Desiderio](https://github.com/dirnbauer/desiderio). It ships
19 finished elements: marquee, orbiting circles, terminal, case studies, and
[15 stats blocks](Documentation/Elements/BlocksStats.md).

The frontend uses Fluid 5, CSS and SVG. Only the area chart needs a small
JavaScript renderer. Upstream TSX files remain in `sources/` for provenance;
there is no React runtime or frontend build step in this extension.

## Requirements and installation

- TYPO3 **14.3.6 or newer within v14** (the [latest release](https://get.typo3.org/list/version/14) checked on 2026-09-06).
- PHP **8.4 or 8.5**.
- Desiderio **4.0.6 or newer within v4** and Content Blocks **2.2 or newer within v2**.

In the consuming TYPO3 project's Composer configuration, register all three
repositories. Composer does not inherit repositories from dependencies:

```bash
composer config repositories.desiderio vcs https://github.com/dirnbauer/desiderio.git
composer config repositories.visual-editor-enhancements vcs https://github.com/dirnbauer/typo3-visual-editor-enhancements.git
composer config repositories.innesto vcs https://github.com/dirnbauer/innesto.git
composer require webconsulting/innesto:^2.0
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

Add the site set in your site's `config.yaml`:

```yaml
dependencies:
  - webconsulting/innesto
```

This includes Desiderio and registers all shipped blocks in the content wizard.
An existing installation keeps its CTypes, fields and collection tables.

## Commands

```bash
# Scaffold a NEW element; choose a key that does not already exist.
vendor/bin/typo3 innesto:add magicui/marquee --key partner-marquee

# Create demo content on a dedicated, existing page.
vendor/bin/typo3 innesto:seed <page-uid>
vendor/bin/typo3 innesto:seed <page-uid> --element terminal
```

Scaffolding converts registry CSS and generates configuration, a Fluid stub,
source files and a temporary finishing prompt. Translating React logic and
modeling editor fields still require a finishing pass. The optional `--ai`
flag runs the installed Claude CLI. See the [command reference](Documentation/AddingContentElements.md).

Demo values come from each element's `library.json`. Existing records,
including hidden ones, are skipped. `--force` replaces records of the selected
CTypes on that page through DataHandler, including their collection children.

## Development

This repository is an extension. Its DDEV configuration provides a separate
local runtime, with PHP 8.4 and generated files under ignored directories.

```bash
ddev start
ddev composer install
ddev exec Build/Scripts/runTests.sh -s ci
```

The test suite exercises registry scaffolding, YAML registration, real TYPO3
DataHandler seeding and Fluid rendering. Functional tests use isolated SQLite
databases; they do not modify the local demo database. CI runs on PHP 8.4 and
8.5 with installed TYPO3 dependencies, PHP lint, PHPStan and the content audit.

For individual checks:

```bash
ddev exec Build/Scripts/runTests.sh -s unit
ddev exec Build/Scripts/runTests.sh -s functional
ddev composer phpstan
ddev composer audit:content-elements
```

On a host with PHP 8.4+ and the required extensions, `composer install` and
`Build/Scripts/runTests.sh -s ci` also work. `-p 8.5` selects a `php8.5` binary;
`PHP_BIN=/path/to/php` selects another executable.

See [Development](Documentation/Development.md) for local demo setup and
[Adding content elements](Documentation/AddingContentElements.md) for the
contract new grafts must satisfy. The extension is GPL-2.0-or-later; preserved
upstream sources retain their original licenses.
