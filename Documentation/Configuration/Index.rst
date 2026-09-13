..  include:: /Includes.rst.txt

..  _configuration:

=============
Configuration
=============

There is no extension configuration and no TypoScript constant to set.
What can be configured is which grafts a site offers, and where new ones
are written.

..  _configuration-set:

The site set
============

:file:`Configuration/Sets/Innesto/config.yaml` is the whole configuration
surface:

..  code-block:: yaml

    name: webconsulting/innesto
    label: 'Innesto'
    dependencies:
      - webconsulting/desiderio
    optionalDependencies:
      - innesto/marquee
      - innesto/terminal
      # … one entry per shipped element

Content Blocks exposes every block as a virtual site set named after the
block. Desiderio-based sites restrict the wizard to the blocks their set
graph pulls in, so an element is offered only when it appears in
``optionalDependencies`` — and :bash:`innesto:add` appends new grafts
there automatically.

Remove an entry to hide an element from the wizard without uninstalling
anything; existing records keep rendering.

..  _configuration-target:

Writing grafts elsewhere
========================

:bash:`innesto:add --target` writes the scaffold into another extension's
:file:`ContentBlocks/ContentElements/` directory. The site-set entry is
then *not* written, because the set belongs to the target extension: add
``innesto/<key>`` — or whatever the target's block prefix is — to that
extension's set yourself.

..  _configuration-ai:

The finishing executable
========================

With :bash:`--ai`, the command runs Claude from the new element directory.
``INNESTO_CLAUDE_BIN`` selects the executable. Inside DDEV the host's
Claude CLI is usually not available in the container; run the generated
prompt from the host instead, as described in :ref:`Usage <usage-finish>`.
