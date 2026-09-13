..  include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

The shadcn/ui registries — shadcn itself, Magic UI, shadcnblocks,
blocks.so — publish hundreds of finished React components. TYPO3 cannot
run them, but it can *graft* them: keep the visual design and the
markup structure, throw away the React runtime, and re-express the
component as a Content Block whose fields an editor fills in.

That is what this extension automates as far as automation reaches, and
documents where it does not.

..  _introduction-graft:

What a graft is
===============

:bash:`innesto:add` fetches a registry item and writes a complete Content
Blocks scaffold: :file:`config.yaml`, a Fluid frontend template, a backend
preview, element CSS converted from the registry's theme variables to
Desiderio's semantic tokens, a backend icon, labels, the upstream source
kept for provenance, and a finishing prompt.

What the generator cannot do is the part that matters: React state, props
and JSX have to become editor fields and Fluid. The finishing pass — by
hand, or with the :bash:`--ai` flag — does that, and the shipped elements
are the worked examples.

..  _introduction-shipped:

What ships
==========

Nineteen elements, all of them finished:

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Grafted from
        -   Notable because

    *   -   ``innesto/marquee``
        -   Magic UI marquee
        -   Pure-CSS infinite scroll

    *   -   ``innesto/orbiting-circles``
        -   Magic UI orbiting circles
        -   Staggered CSS orbits, no JavaScript

    *   -   ``innesto/case-studies``
        -   shadcnblocks case-studies2
        -   Nested Collections and File fields

    *   -   ``innesto/terminal``
        -   Magic UI terminal
        -   A typewriter re-created in pure CSS

    *   -   ``innesto/stats-*`` (15 elements)
        -   The complete `blocks.so/stats <https://blocks.so/stats>`__
            family
        -   Collections of metrics, one with decimal chart data and a
            sparkline asset script

Every one of them composes Desiderio layout components and paints itself
exclusively from Desiderio's semantic tokens, so a graft follows every
preset and dark mode without knowing they exist.

..  _introduction-contract:

The contract a graft must keep
==============================

Innesto sits on top of Desiderio, so it is held to Desiderio's own rules
and tested against them:

*   every frontend template composes at least one ``d:`` component inside
    a ``d:layout.section`` root, and renders no partials
*   organisms stay out — those belong to page templates
*   only components that exist in the installed Desiderio collection are
    referenced
*   every template parses under Fluid 5 with the runtime rendering
    context, so a renamed component argument is a failing test and not a
    white page

See :ref:`the developer reference <developer>` for how those are checked.
