..  include:: /Includes.rst.txt

..  _usage:

=====
Usage
=====

..  _usage-add:

Grafting a component
====================

..  code-block:: bash

    vendor/bin/typo3 innesto:add magicui/marquee --key partner-marquee
    vendor/bin/typo3 innesto:add @shadcnblocks/case-studies2 --key customer-stories
    vendor/bin/typo3 innesto:add blocks/stats-09 --key project-progress
    vendor/bin/typo3 innesto:add https://example.com/r/component.json --key custom-card

..  list-table::
    :header-rows: 1

    *   -   Option
        -   Meaning

    *   -   ``--key``, ``-k``
        -   Element folder and name; defaults to the registry item name

    *   -   ``--target``, ``-t``
        -   ContentElements directory; defaults to this extension

    *   -   ``--ai``
        -   Run the finishing pass with the installed ``claude``
            executable

Keys are lowercase letters, digits and single hyphens, and must produce a
unique CType — ``demo-card`` and ``democard`` collide, because the CType
convention removes hyphens. The 19 shipped keys already exist and the
command refuses to overwrite them.

``shadcn``, ``magicui``, ``shadcnblocks`` and ``blocks`` are known
shorthands; a leading ``@`` is optional. Any other registry needs a
complete item JSON URL. Registry dependencies are reported for manual
review and never downloaded recursively.

The command writes:

..  code-block:: text

    ContentBlocks/ContentElements/<key>/
    ├── config.yaml
    ├── templates/frontend.html
    ├── assets/frontend.css
    ├── assets/icon.svg
    ├── language/labels.xlf
    ├── sources/<upstream-file>
    └── AI_PROMPT.md

..  _usage-finish:

Finishing the scaffold
======================

With :bash:`--ai` the finishing pass runs immediately. Otherwise — or when
it fails, which leaves the scaffold and the prompt in place — run the
prompt from the element directory:

..  code-block:: bash

    cd ContentBlocks/ContentElements/partner-marquee
    claude -p "$(cat AI_PROMPT.md)" --permission-mode acceptEdits

Do not rerun :bash:`innesto:add` for a folder that already exists.

Five files need real work, and the review afterwards is not optional:

#.  :file:`templates/frontend.html` — translate the markup to Fluid 5.
    Compose Desiderio layout components, render editor text through
    ``f:render.text``, keep the per-element ``f:asset.css`` include,
    prefer CSS for motion and load any JavaScript with
    ``f:asset.script``.
#.  :file:`config.yaml` — model the component's props as fields. Keep
    ``header`` as the shared heading field. Give every Collection its own
    ``innesto_*`` table, nested Collections included, and use ``title``
    instead of the reserved child identifier ``label``. When editing an
    existing block, preserve its CTypes and field names.
#.  :file:`assets/frontend.css` — semantic variables only
    (``var(--primary)``, ``var(--muted)``, ``var(--border)``), selectors
    prefixed with the element name, and a ``prefers-reduced-motion``
    branch for anything that animates.
#.  :file:`templates/backend-preview.fluid.html` — a preview on the
    ``Preview`` layout with Desiderio's :file:`content-preview.css`:
    translated UID and page chips, every Collection as a
    ``d-ce-preview__collection`` list, labels from
    :file:`Resources/Private/Language/preview.xlf` (English and German).
#.  :file:`library.json` — demo values keyed by field identifier;
    Collections hold arrays of child objects and numeric values stay
    numeric, decimals included.

Remove :file:`AI_PROMPT.md` afterwards. Keep the upstream sources and
their license notices.

Worked examples live in this repository: ``marquee`` for animation,
``case-studies`` for nested Collections and File fields, ``stats-area-chart``
for decimal chart data, and ``terminal`` for a JavaScript-free typewriter.

..  _usage-activate:

Activating a new element
========================

..  code-block:: bash

    composer ci:tests:unit
    vendor/bin/typo3 extension:setup
    vendor/bin/typo3 cache:flush

The unit suite is the finishing checklist: it holds the new element to
the same package and composition contract as the shipped ones. Schema
setup is needed whenever a block gains database fields or Collection
tables. Then inspect the wizard, the edit form, the backend preview and
the frontend.

..  _usage-seed:

Seeding demo records
====================

..  code-block:: bash

    vendor/bin/typo3 innesto:seed <page-uid>
    vendor/bin/typo3 innesto:seed <page-uid> -e terminal -e case-studies
    vendor/bin/typo3 innesto:seed <page-uid> -e terminal --force

Records are appended after the page's current content. A normal rerun
skips CTypes that already exist there, hidden records included, so it is
idempotent. :bash:`--force` replaces every record of each selected CType
on that page, collection children included — use it only for demo content
you mean to replace. Everything else on the page is left alone.

:file:`fixture.json` overrides :file:`library.json` when present. Missing
values fall back to TYPO3's field defaults rather than invented
placeholders, and File fields stay empty; fill those in the backend.

Writes go through DataHandler, so relations are created and the reference
index is updated. Invalid page UIDs, unknown element keys, malformed
fixtures and DataHandler errors all fail the command. Replacements are
written before the old records are deleted and the transaction rolls back
on a DataHandler error, so a failed :bash:`--force` leaves the page as it
was. Keep the content and its collection tables on the same database
connection.

..  _usage-elements:

Two elements worth a closer look
================================

Terminal
--------

The upstream Magic UI component drives its typewriter with
``motion/react``. The graft re-creates that motion in pure CSS: each line
carries an inline ``--index`` and the reveal and typing animations are
staggered off it, so the element ships zero JavaScript. It honours
``prefers-reduced-motion`` and an editor *Animate* toggle.

``command`` lines get a ``$`` prompt and type out character by character;
``output``, ``success`` and ``muted`` lines fade in. Every colour,
including the window dots, is a Desiderio token mixed with ``color-mix``.

The stats family
----------------

The 15 ``innesto/stats-*`` elements are the complete
`blocks.so/stats <https://blocks.so/stats>`__ family, keyed semantically
rather than by number — ``stats-01`` became ``innesto/stats-trending``,
``stats-10`` became ``innesto/stats-area-chart``, and so on. The original
TSX of each one is kept under :file:`sources/` for provenance.

The conversion was the same five steps every time: repeated metrics
became Collections with explicit ``innesto_*`` tables, hard-coded React
arrays became editor fields, JSX became Fluid composing Desiderio layout
components, colours became semantic tokens, and each element gained a
backend preview, labels and a 16×16 icon.
