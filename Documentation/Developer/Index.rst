..  include:: /Includes.rst.txt

..  _developer:

===================
Developer reference
===================

..  _developer-classes:

The pieces
==========

..  list-table::
    :header-rows: 1

    *   -   Class
        -   Responsibility

    *   -   :php:`Registry\RegistryClient`
        -   Resolves a registry shorthand or URL to an item JSON and
            validates it. A malformed item is a :php:`RuntimeException`,
            never a half-written scaffold.

    *   -   :php:`Registry\CssConverter`
        -   Rewrites the registry's theme variables into Desiderio's
            semantic tokens, including the ``.dark`` block and keyframes.

    *   -   :php:`Registry\ElementScaffolder`
        -   Writes the element directory. Refuses to overwrite, and
            rejects keys that would collide on CType or on a source
            basename.

    *   -   :php:`Registry\SetRegistrar`
        -   Appends ``innesto/<key>`` to the site set, idempotently and
            preserving the file's existing YAML shape.

    *   -   :php:`Registry\FinishingPromptBuilder`
        -   Builds :file:`AI_PROMPT.md` — the instructions the finishing
            pass follows.

    *   -   :php:`Command\SeedDemoRecordsCommand`
        -   Seeds demo records through DataHandler, inside a transaction.

..  _developer-contract:

The component contract
======================

Innesto grafts compose Desiderio, so they are held to Desiderio's atomic
design rules. :php:`Tests\Unit\ContentElementConformanceTest` checks them
statically over every shipped template:

..  list-table::
    :header-rows: 1

    *   -   Rule
        -   What it enforces

    *   -   I1
        -   At least one ``d:`` component inside a ``d:layout.section``
            root; no ``f:render partial=``.

    *   -   I2
        -   Every referenced ``d:`` component exists in the installed
            Desiderio collection under
            :file:`Resources/Private/Components/<Layer>/<Name>/`.

    *   -   I3
        -   No organisms. Desiderio reserves those for page templates,
            and Innesto ships content elements.

    *   -   I4
        -   The Desiderio namespace is declared with its canonical URI
            exactly where ``d:`` tags are used — and nowhere else.

:php:`Tests\Functional\Templates\ShippedTemplatesLintTest` adds what a
static check cannot do: it runs Desiderio's Fluid 5 linter over both
templates of all 19 elements with the runtime rendering context, and
requires zero errors and zero skipped checks. A Desiderio component that
gains a required argument, or renames one, fails that test instead of
producing a white page.

..  _developer-audit:

The content element audit
=========================

..  code-block:: bash

    composer audit:content-elements

Checks metadata, wizard registration, Collection table uniqueness,
template references, token-only CSS, backend previews, icons and XLIFF
across every element. It runs without a TYPO3 installation when
``ext-yaml`` is available; otherwise point ``AUDIT_AUTOLOAD`` at a
Composer autoloader that provides ``symfony/yaml``.

..  _developer-tests:

Tests
=====

..  code-block:: bash

    composer install
    composer ci                   # cgl, phpstan, unit, functional
    composer ci:tests:unit
    composer ci:tests:functional  # SQLite by default, no database server
    composer ci:phpstan           # level 8, no baseline
    composer ci:cgl -- --dry-run

The unit suite covers registry URL handling, the generated YAML and XML,
source and CType collisions, invalid paths, non-overwrite behaviour, CSS
conversion, idempotent site-set registration and the component contract.

The functional suite boots the installed TYPO3 release with Content
Blocks, Visual Editor, the Vite asset collector, Desiderio and Innesto.
It seeds the whole family through DataHandler, renders every element
through the frontend, and covers repeated runs, hidden records,
replacement ordering and the rollback of parent and child records when a
write fails.

..  _developer-demo:

A local demo
============

DDEV serves :file:`.Build/public`. After :bash:`ddev start` and
:bash:`ddev composer install`, run :bash:`ddev exec vendor/bin/typo3 setup`
— for the DDEV database, host, database, user and password are all ``db``.
Create a site, add ``webconsulting/innesto`` to its dependencies, drop the
setup wizard's placeholder ``page`` TypoScript so Desiderio's set renders,
and build the theme delivery:

..  code-block:: bash

    ddev exec npx --yes vite@8.2.0 build --config Build/vite.config.mjs

Then enable the built assets in :file:`config/system/additional.php`:

..  code-block:: php

    <?php
    if (getenv('IS_DDEV_PROJECT') === 'true') {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '^innesto\\.ddev\\.site$';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['vite_asset_collector']['useDevServer'] = '0';
    }

..  code-block:: bash

    ddev exec vendor/bin/typo3 extension:setup
    ddev exec vendor/bin/typo3 innesto:seed <page-uid>
    ddev exec vendor/bin/typo3 cache:flush
