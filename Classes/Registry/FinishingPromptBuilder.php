<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Registry;

/**
 * Builds the prompt for the AI finishing pass — the one step of a graft that
 * cannot be mechanical: translating the upstream React component into Fluid 5,
 * modeling its props as Content Blocks fields, and porting its styles onto the
 * Desiderio tokens. The prompt is always written to AI_PROMPT.md inside the
 * element folder so the pass is reproducible with any agent.
 */
final class FinishingPromptBuilder
{
    /**
     * @param array<string, mixed> $item the registry item
     */
    public function build(array $item, string $elementKey, string $elementDir): string
    {
        $sources = '';
        foreach ($item['files'] ?? [] as $file) {
            $sources .= sprintf(
                "\n### sources/%s\n```tsx\n%s\n```\n",
                basename((string)($file['path'] ?? 'source.txt')),
                trim((string)($file['content'] ?? ''))
            );
        }
        $title = (string)($item['title'] ?? $elementKey);
        $description = (string)($item['description'] ?? '');

        return <<<MARKDOWN
# Finish the Innesto graft: innesto/$elementKey

You are inside the element directory `$elementDir`.
The upstream shadcn registry component "$title" ($description) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources
$sources

## Task

1. **templates/frontend.html** — translate the component markup to Fluid 5.
   Replace the TODO stub. Keep the existing `d:layout.section` /
   `d:layout.container` wrapper and the `f:asset.css` include. Editor content
   comes from `{data.<field>}`; render text fields through
   `{data -> f:render.text(field: '<field>')}`. Repeatable content is a
   Collection field iterated with `<f:for each="{data.<field>}" as="entry">`.
   Interactive behaviour (hover/toggle state) goes to CSS where possible,
   otherwise Alpine.js (`x-data` attributes, no inline <script>).
2. **config.yaml** — model the component props as fields. Conventions:
   Select (`renderType: selectSingle`) for enums, Checkbox
   (`renderType: checkboxToggle`) for booleans, Textarea `rows: 1` for short
   text, Collection for repeatable children. NEVER name a Collection child
   field `label` — that identifier is reserved by Content Blocks and breaks
   the generated table; use `title` instead. Keep `useExistingField: true`
   for `header`.
3. **assets/frontend.css** — port the component styles. Use ONLY the semantic
   tokens (`var(--primary)`, `var(--muted)`, `var(--card)`, `var(--border)`,
   `var(--radius)`, `var(--shadow-sm)`, …) — never hard-coded colors. Prefix
   every class with `.innesto-$elementKey`. Honor
   `prefers-reduced-motion: reduce` for any animation.
4. **templates/backend-preview.fluid.html** — create it if missing, modeled
   on the Desiderio previews (`f:layout name="Preview"`, the `d-ce-preview`
   card markup, `EXT:desiderio/Resources/Public/Css/content-preview.css`).

## Reference

Finished examples live in the Desiderio package next to this extension:
`ContentBlocks/ContentElements/` of `webconsulting/desiderio` (e.g. the
`marquee` element in this extension, or `logo-carousel` / `header-section`
in Desiderio). Match their structure and idioms exactly.

When you are done, list any prop you intentionally did not model and why.
MARKDOWN;
    }
}
