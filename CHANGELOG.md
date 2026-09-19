# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.2.0] - 2026-09-19

Built against Desiderio 4.1.7.

### Added

- `Tests/Unit/ElementPackageConformanceTest`: the package contract, checked once per element with the element's name in the data set — complete package and valid 16x16 `currentColor` icon and XLIFF (P1), metadata and site-set registration (P2), explicit, `innesto_`-prefixed and extension-wide unique Collection tables (P3), the Appearance palette actually reaching `d:layout.section` (P4), token-only styling and no inline `<script>` (P5), every configured editor field rendered (P6), and a `library.json` the seeder can apply (P7). It replaces `scripts/audit-content-elements.php`, which checked most of this from a bespoke script CI ran once.
- `ContentElementConformanceTest` rules I5 (headings come from `d:atom.typography`) and I6 (icons come from `d:atom.icon`; only a `pathLength` data graphic may stay an inline `<svg>`).
- A test that pins the scaffolded template stub to the same rules, so a fresh graft never starts with a red suite.

### Changed

- Every element composes Desiderio components instead of hand-rolled markup. The copy-pasted eyebrow + headline block becomes `d:molecule.sectionIntro` in seventeen templates, headings render through `d:atom.typography`, and trend, status and chevron icons become `d:atom.icon` — so grafts now follow the theme's heading scale and the site's icon library. 55 duplicated `.__intro` / `.__eyebrow` / `.__headline` rules are deleted from fourteen stylesheets.
- Fourteen hard-coded English strings move into XLIFF; the screen-reader trend announcements reuse Desiderio's existing `trend.*` units instead of near-duplicates.
- `ElementScaffolder` writes `AI_PROMPT.md` as part of the element, inside the same all-or-nothing loop as every other file. `Registry\FinishingPromptBuilder` is gone, and with it a class that existed to return one heredoc and a second, separate write that could fail on its own.
- The scaffolded stub now ships the `d:layout.section` Appearance wiring and a `d:molecule.sectionIntro` instead of a bare `<h2>`.
- `Configuration/TCA/Overrides/tt_content.php` registers the three wizard groups the shipped elements use plus the scaffolder's `components` fallback; ten speculative groups and their labels are removed.

### Fixed

- The editor's Appearance palette did nothing. Every element declares the `TYPO3/Appearance` basic, but no template passed `frame_class`, `space_before_class` or `space_after_class` to `d:layout.section`, so frame and spacing choices never reached the markup. All nineteen section roots wire them now, and P4 guards it.

### Removed

- `scripts/audit-content-elements.php` and its CI job, `Build/Scripts/runTests.sh`, `Build/vite.config.mjs`, the checked-in `.ddev` profile, a stale documentation screenshot, and the developer manual's local-DDEV-demo chapter that described them.

## [2.1.0] - 2026-09-13

Built against Desiderio 4.1.1.

### Added

- `Tests/Unit/ContentElementConformanceTest`: every shipped element composes at least one `d:` component inside a `d:layout.section` root, renders no partials, references only components that exist in the installed Desiderio collection, composes no organisms, and declares the Desiderio namespace canonically — mirroring Desiderio's own atomic-design conformance test.
- `Tests/Functional/Templates/ShippedTemplatesLintTest`: Desiderio 4.1's Fluid 5 linter runs over both templates of all 19 elements with the runtime rendering context, requiring zero errors and zero skipped checks. A component that gains a required argument now fails a test instead of producing a white page.
- `CHANGELOG.md`, `.php-cs-fixer.dist.php` on `typo3/coding-standards`, and the `composer ci`, `ci:cgl`, `ci:phpstan`, `ci:tests:unit` and `ci:tests:functional` scripts.

### Changed

- Requires Desiderio `^4.1.1` and TYPO3 14.3.7.
- `Documentation/` is now a rendered TYPO3 RST manual — introduction, installation, configuration, usage and a developer reference. The four Markdown files it held are gone; their content lives on in the RST pages.
- One CI workflow with lint, coding standards, PHPStan, unit tests on PHP 8.4 and 8.5, functional tests against MariaDB 10.11 and the content element audit, replacing the `runTests.sh -s ci` matrix.
- Tests run through the TYPO3 testing framework under `Build/phpunit/`; the functional suite still defaults to SQLite.
- PHPStan analyses `Configuration/` and `Tests/` as well as `Classes/`, from a single root `phpstan.neon`, at level 8 with the TYPO3 and PHPUnit extensions and no baseline.
- `extra.typo3/cms` carries the version and `Package.providesPackages`, the convention the other extensions in the family use.

### Fixed

- `typo3/cms-fluid-styled-content` is now a declared requirement. Desiderio 4.1.1 made its site set depend on it — `lib.contentElement` is assigned by fluid_styled_content — and since the Innesto set depends on the Desiderio set, a site without FSC refused to resolve with *depends on unavailable sets*.
- Eight PHPStan findings in the test code that widening the analysis paths surfaced: untyped iterables in data providers and fixtures, and an unguarded `glob()`/`file_get_contents()` pair in the frontend rendering test.

[2.1.0]: https://github.com/dirnbauer/innesto/releases/tag/v2.1.0
