..  include:: /Includes.rst.txt

..  _start:

=======
Innesto
=======

:Extension key:
    innesto

:Package name:
    webconsulting/innesto

:Version:
    |release|

:Language:
    en

:Author:
    webconsulting business services gmbh

:License:
    This document is published under the
    `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
    license.

:Rendered:
    |today|

----

*Innesto* is Italian for a graft. The extension grafts
`shadcn/ui <https://ui.shadcn.com>`__ registry components onto TYPO3 as
Content Blocks styled with `Desiderio <https://github.com/dirnbauer/desiderio>`__:
one command fetches a registry item, converts its CSS and scaffolds a
complete content element; a finishing pass turns the React markup into
Fluid that composes Desiderio components.

Nineteen finished grafts ship with the extension.

----

..  card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :class: pb-4
    :card-height: 100

    ..  card:: Introduction

        What a graft is, what ships, and what the finishing pass has to
        do that no generator can.

        ..  card-footer:: :ref:`Read the introduction <introduction>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Installation

        Composer, the site set, and the Desiderio version this release
        is built against.

        ..  card-footer:: :ref:`Install the extension <installation>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Usage

        Grafting a new component, finishing the scaffold, and seeding
        demo records.

        ..  card-footer:: :ref:`Graft a component <usage>`
            :button-style: btn btn-secondary stretched-link

    ..  card:: Developer reference

        The component and package contracts every graft is held to, and
        the test suites that enforce them.

        ..  card-footer:: :ref:`Read the reference <developer>`
            :button-style: btn btn-secondary stretched-link

..  toctree::
    :maxdepth: 2
    :titlesonly:

    Introduction/Index
    Installation/Index
    Configuration/Index
    Usage/Index
    Developer/Index

..  toctree::
    :hidden:

    Sitemap
