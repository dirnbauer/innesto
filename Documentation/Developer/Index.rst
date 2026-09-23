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
        -   Writes the whole element directory in one all-or-nothing
            pass — config, templates, assets, labels, the upstream
            sources and :file:`AI_PROMPT.md`. Refuses to overwrite, and
            rejects keys that would collide on CType or on a source
            basename.

    *   -   :php:`Registry\SetRegistrar`
        -   Appends ``innesto/<key>`` to the site set, idempotently and
            preserving the file's existing YAML shape.

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

    *   -   I5
        -   Headings come from ``d:atom.typography``; a hand-written
            ``<hN>`` in a frontend template is a failure.

    *   -   I6
        -   Icons come from ``d:atom.icon``. The only inline ``<svg>`` a
            graft may keep is a data graphic — a progress ring or donut,
            recognised by its ``pathLength``.

:php:`Tests\Functional\Templates\ShippedTemplatesLintTest` adds what a
static check cannot do: it runs Desiderio's Fluid 5 linter over both
templates of all 19 elements with the runtime rendering context, and
requires zero errors and zero skipped checks. A Desiderio component that
gains a required argument, or renames one, fails that test instead of
producing a white page.

..  _developer-package:

The package contract
====================

:php:`Tests\Unit\ElementPackageConformanceTest` checks everything around
the template, once per element and with the element's name in the data
set, so a failure says which graft is wrong:

..  list-table::
    :header-rows: 1

    *   -   Rule
        -   What it enforces

    *   -   P1
        -   The package is complete: config, both templates, stylesheet,
            icon and labels. The icon is a well-formed 16x16
            ``currentColor`` line drawing; :file:`labels.xlf` is valid
            XLIFF with a ``title`` and a ``description`` unit.

    *   -   P2
        -   ``name``, ``typeName``, title and description are set, the
            wizard group is registered in
            :file:`Configuration/TCA/Overrides/tt_content.php` and is not
            ``default``, keywords exist, and the block is listed in
            :file:`Configuration/Sets/Innesto/config.yaml` — without that
            entry the element stays invisible.

    *   -   P3
        -   Every Collection declares an explicit ``table:`` that starts
            with ``innesto_`` and is unique across the extension. Two
            elements sharing a table corrupt both schemas.

    *   -   P4
        -   The element offers the ``TYPO3/Appearance`` basic *and*
            passes ``frame_class``, ``space_before_class`` and
            ``space_after_class`` into ``d:layout.section``, so the
            editor's choice actually reaches the markup.

    *   -   P5
        -   No colour literals in CSS, JavaScript or the template — only
            the semantic tokens — and no inline ``<script>``.

    *   -   P6
        -   Every configured editor field is rendered by the frontend
            template. A field nothing renders is either dead or a
            forgotten graft.

    *   -   P7
        -   :file:`library.json` is a demo record :bash:`innesto:seed`
            can apply: every key is a configured field, Collections are
            lists of child objects, everything else is scalar.

..  _developer-previews:

Backend previews
================

Every element previews itself in the page module on Desiderio's
``Preview`` card: UID and page chips, the heading, the scalar fields worth
recognising (eyebrow, intro, speed, …) and each Collection as a list of its
entries with their main values. Labels come from
:file:`Resources/Private/Language/preview.xlf` and its German translation.
:php:`Tests\Unit\BackendPreviewConformanceTest` rejects hard-coded label
text and label keys that are missing in either language.

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
conversion, idempotent site-set registration, the backend previews and
both contracts above — including the scaffolded stub, which has to satisfy
them from the start.

The functional suite boots the installed TYPO3 release with Content
Blocks, Visual Editor, the Vite asset collector, Desiderio and Innesto.
It seeds the whole family through DataHandler, renders every element
through the frontend, and covers repeated runs, hidden records,
replacement ordering and the rollback of parent and child records when a
write fails.
