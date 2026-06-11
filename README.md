# Innesto

*innesto (it.) — graft.* A TYPO3 v14 experiment that grafts components from
[shadcn/ui registries](https://registry.directory/) onto the
[Desiderio](https://github.com/dirnbauer/desiderio) design system as Content
Blocks elements.

## What it does

1. **Companion extension.** Innesto depends on Desiderio; its site set pulls
   in the whole Desiderio set, so every grafted element automatically uses the
   active shadcn theme preset, semantic tokens, and Fluid 5 component atoms.
2. **Own content elements.** Elements live in
   `ContentBlocks/ContentElements/` and are auto-discovered by TYPO3 Content
   Blocks. Eighteen finished grafts ship with the extension: the
   [Magic UI marquee](https://magicui.design/docs/components/marquee) and
   orbiting-circles, the
   [shadcnblocks case-studies2](https://www.shadcnblocks.com/block/case-studies2)
   block (quotes + metrics, modeled as nested Collections), and the complete
   15-element stats family from [blocks.so](https://blocks.so/) (trending,
   badges, progress bars, circular progress, area charts, dashboards,
   breakdowns — all rendered without React, via tokens, CSS and SVG).
3. **Registry glue code.** A console command fetches any registry item — every
   registry cataloged on [registry.directory](https://registry.directory/)
   speaks the same `registry-item` JSON schema — and scaffolds a Content
   Blocks element from it:

   ```bash
   vendor/bin/typo3 innesto:add magicui/marquee
   vendor/bin/typo3 innesto:add shadcn/button
   vendor/bin/typo3 innesto:add @shadcnblocks/case-studies2 --key case-studies
   vendor/bin/typo3 innesto:add blocks/stats-09 --key stats-progress
   vendor/bin/typo3 innesto:add https://magicui.design/r/globe.json --key globe
   vendor/bin/typo3 innesto:add magicui/orbiting-circles --ai   # + AI finishing pass
   ```

📖 **[The complete manual — adding content elements from the shadcn registry](Documentation/AddingContentElements.md)**
walks through two full grafts step by step — with backend and frontend
screenshots — from picking a component to seeing it render.

## What the pipeline converts automatically — and what it can't

| Registry item part | Conversion |
| --- | --- |
| `cssVars` (theme/light/dark tokens) | ✅ automatic — emitted as CSS custom properties; Desiderio uses the same shadcn variable names, so they map 1:1 |
| `css` (keyframes, rules) | ✅ automatic — serialized into the element's `assets/frontend.css` |
| Tailwind `@theme` animation entries | ✅ automatic — custom property + matching utility class (the Desiderio Tailwind build does not scan grafted elements) |
| Site-set registration (New Content Element wizard visibility) | ✅ automatic — the block is appended to the Innesto set's `optionalDependencies` |
| Registry `categories` → wizard group | ✅ automatic — the item's first category becomes the element's wizard group; the blocks.so families (Stats, Grid List, Onboarding, …) ship pre-registered |
| React/TSX markup | ⚠️ scaffolded — the source is preserved under `sources/`, the Fluid 5 template is generated as a stub with TODO markers; structural markup translates quickly, hooks/state need Alpine.js or a manual pass (or the `--ai` finishing pass) |
| Component props | ⚠️ manual — model them as Content Blocks fields in `config.yaml` (covered by the `--ai` finishing pass) |

That last row is the honest limit: React components are programs, not
documents, so a fully mechanical React→Fluid conversion is not possible.
The glue code does everything deterministic and leaves a clearly marked
finishing pass.

## Install

```bash
composer config repositories.innesto vcs https://github.com/dirnbauer/innesto
composer require dirnbauer/innesto:@dev
vendor/bin/typo3 extension:setup
```

Then add the set to your site's `config.yaml`:

```yaml
dependencies:
  - dirnbauer/innesto
```

## Grafting a component in four steps

```bash
# 1. Pick an item on https://registry.directory/ and fetch it — shorthand
#    (@ optional) for shadcn/magicui/shadcnblocks/blocks, item-JSON URL for the rest:
vendor/bin/typo3 innesto:add @shadcnblocks/case-studies2 --key case-studies

# 2. Finish the graft — translate React → Fluid, model props as fields
#    (the scaffold includes a tailored AI_PROMPT.md, or rerun with --ai):
cd ContentBlocks/ContentElements/case-studies
claude -p "$(cat AI_PROMPT.md)" --permission-mode acceptEdits

# 3. Create the new tables and flush caches:
vendor/bin/typo3 extension:setup && vendor/bin/typo3 cache:flush

# 4. Add the element in the New Content Element wizard — done.
```

The [manual](Documentation/AddingContentElements.md) walks through both worked
examples: the marquee (motion, CSS modifiers) and the case-studies block
(structured content, nested Collections, File fields).

## Element anatomy

```
ContentBlocks/ContentElements/<key>/
├── AI_PROMPT.md                 # reproducible prompt for the finishing pass
├── config.yaml                  # fields modeled from the component props
├── templates/frontend.html      # Fluid 5, uses Desiderio d: atoms + tokens
├── templates/backend-preview.fluid.html
├── assets/frontend.css          # converted css/cssVars, semantic tokens only
├── assets/icon.svg
├── language/labels.xlf
└── sources/<original>.tsx       # upstream source, kept for provenance
```

Upstream component sources keep their original licenses (Magic UI marquee:
MIT). The extension itself is GPL-2.0-or-later, like TYPO3.
