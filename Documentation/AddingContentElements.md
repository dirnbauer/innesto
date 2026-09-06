# Adding content elements

Innesto generates a Content Blocks scaffold from a shadcn registry item.
Registry CSS is converted automatically; React markup, state and props need
to be translated into Fluid templates and editor fields.

## Fetch a new component

Use an unused key: the 19 shipped elements already exist, and `innesto:add`
refuses to overwrite them.

```bash
vendor/bin/typo3 innesto:add magicui/marquee --key partner-marquee
vendor/bin/typo3 innesto:add @shadcnblocks/case-studies2 --key customer-stories
vendor/bin/typo3 innesto:add blocks/stats-09 --key project-progress
vendor/bin/typo3 innesto:add https://example.com/r/component.json --key custom-card
```

| Option | Meaning |
| --- | --- |
| `--key`, `-k` | Element folder and name; defaults to the registry item name |
| `--target`, `-t` | ContentElements directory; defaults to this extension |
| `--ai` | Run the finishing pass with the installed `claude` executable |

Keys use lowercase letters, numbers and single hyphens. Known shorthands are
`shadcn`, `magicui`, `shadcnblocks` and `blocks`; a leading `@` is optional.
Other registries need a complete item JSON URL. Registry dependencies are
reported for manual review and are not downloaded recursively.
Keys must also produce a unique CType: `demo-card` and `democard` conflict
because Innesto's existing CType convention removes hyphens.

The command writes:

```text
ContentBlocks/ContentElements/<key>/
├── config.yaml
├── templates/frontend.html
├── assets/frontend.css
├── assets/icon.svg
├── language/labels.xlf
├── sources/<upstream-file>
└── AI_PROMPT.md
```

Source basenames must be unique to avoid losing files during extraction.
Registry titles and descriptions are serialized with Symfony YAML, including
quotes and multiline text.

For the default target, the command adds `innesto/<key>` to
`Configuration/Sets/Innesto/config.yaml`. A custom target needs that entry in
the consuming site's set manually. Desiderio sites restrict available blocks
through site sets, so an unlisted block may remain hidden in the wizard.

## Finish the scaffold

With `--ai`, the command invokes Claude from the new element directory.
`INNESTO_CLAUDE_BIN` can select the executable. In DDEV, the host's Claude CLI
usually is not installed inside the container. Run the generated prompt from
the host instead:

```bash
cd ContentBlocks/ContentElements/partner-marquee
claude -p "$(cat AI_PROMPT.md)" --permission-mode acceptEdits
```

If the finishing process fails, the scaffold and prompt remain available.
Run the prompt from the existing folder; repeating `innesto:add` would fail
because that folder already exists.

Finish these files, then review the generated changes:

1. **`templates/frontend.html`:** translate markup to Fluid 5; use Desiderio
   layout components and render editor text with `f:render.text`. Keep the
   per-element `f:asset.css` include. Use CSS for motion where possible and
   load any JavaScript through `f:asset.script`.
2. **`config.yaml`:** model component props as fields. Keep `header` as an
   existing field. Give each Collection a unique `innesto_*` table, including
   nested Collections. Use `title` instead of the reserved child identifier
   `label`. Preserve existing CTypes and field names when editing a block.
3. **`assets/frontend.css`:** use Desiderio semantic variables such as
   `var(--primary)`, `var(--muted)` and `var(--border)`. Prefix selectors with
   the element name and support `prefers-reduced-motion` for animations.
4. **`templates/backend-preview.fluid.html`:** provide a preview using the
   `Preview` layout and Desiderio's `content-preview.css`.
5. **`library.json`:** provide demo values keyed by field identifier.
   Collections contain arrays of child objects. Keep numeric values numeric,
   including decimal chart data.

Finished examples are in this repository: `marquee` for animation,
`case-studies` for nested Collections and File fields, and `stats-area-chart`
for decimal chart data. See also [Terminal](Elements/Terminal.md) and
[Blocks stats](Elements/BlocksStats.md).

Remove the temporary `AI_PROMPT.md` after finishing. Keep upstream sources
and license notices for provenance.

## Activate and check

```bash
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

Schema setup is needed when a block gains database fields or Collection tables.
Run this repository's `composer audit:content-elements`, then inspect the
content wizard, edit form, backend preview and frontend. The audit checks
metadata, registration, Collection table uniqueness, assets, icons and XLIFF;
runtime tests additionally execute the commands and render templates.

## Seed demo records

```bash
vendor/bin/typo3 innesto:seed <page-uid>
vendor/bin/typo3 innesto:seed <page-uid> -e terminal -e case-studies
vendor/bin/typo3 innesto:seed <page-uid> -e terminal --force
```

Use a dedicated, existing page. Records are appended after its current
content. A normal rerun skips existing CTypes, including hidden records.
`--force` replaces all records of each selected CType on that page, including
collection children, so use it only for demo content you intend to replace.
Other content remains in place.

`fixture.json`, if supplied, overrides `library.json`. Missing values retain
TYPO3's field defaults; the command no longer invents placeholder metrics.
An element without a fixture gets its configured title and field defaults.
File fields remain empty and must be populated through the backend.

DataHandler creates relations and updates the reference index. Invalid page
UIDs, unknown element keys, malformed fixture data and DataHandler errors
produce failures. Replacements are created before old records are deleted;
the database transaction rolls back if DataHandler reports an error. Keep the
content and its collection tables on the same database connection.
