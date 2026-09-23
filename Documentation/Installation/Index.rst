..  include:: /Includes.rst.txt

..  _installation:

============
Installation
============

..  _installation-requirements:

Requirements
============

*   TYPO3 14.3 LTS
*   PHP 8.4 or newer
*   :composer:`friendsoftypo3/content-blocks` 2.4 or newer
*   `webconsulting/desiderio <https://github.com/dirnbauer/desiderio>`__
    4.3 or newer — the component collection every graft composes
*   :composer:`typo3/cms-fluid-styled-content` — Desiderio's site set
    depends on it, and the Innesto set depends on Desiderio's

..  _installation-composer:

Install with Composer
=====================

Desiderio is distributed through GitHub rather than Packagist, so the
repository declaration is part of the package:

..  code-block:: bash

    composer require webconsulting/innesto
    vendor/bin/typo3 extension:setup

..  _installation-site-set:

Add the site set
================

Innesto ships the site set ``webconsulting/innesto``. Add it to the site
that should offer the grafts:

..  code-block:: yaml
    :caption: config/sites/<identifier>/config.yaml

    dependencies:
      - webconsulting/innesto

The set depends on ``webconsulting/desiderio`` and lists every shipped
element as an optional dependency. Desiderio restricts the available
blocks per site set, so an element that is not listed stays out of the
*New Content Element* wizard — which is exactly how a site chooses a
subset.

..  _installation-verify:

Verify
======

..  code-block:: bash

    vendor/bin/typo3 innesto:seed <page-uid>
    vendor/bin/typo3 cache:flush

The page then holds one record of every shipped element, filled from the
maintained demo fixtures. Use a page you are happy to fill with demo
content; see :ref:`Seeding demo records <usage-seed>` for what a rerun
does.
