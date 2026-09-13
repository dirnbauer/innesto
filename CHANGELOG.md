# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
