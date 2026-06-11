# Finish the Innesto graft: innesto/stats-cards

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-cards`.
The upstream shadcn registry component "Stats with Card Layout" (A stats with card layout block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-03.tsx
```tsx
"use client";

import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";

const data = [
  {
    name: "Unique visitors",
    stat: "10,450",
    change: "-12.5%",
    changeType: "negative",
  },
  {
    name: "Bounce rate",
    stat: "56.1%",
    change: "+1.8%",
    changeType: "positive",
  },
  {
    name: "Visit duration",
    stat: "5.2min",
    change: "+19.7%",
    changeType: "positive",
  },
  {
    name: "Conversion rate",
    stat: "3.2%",
    change: "-2.4%",
    changeType: "negative",
  },
];

export default function Stats03() {
  return (
    <div className="flex items-center justify-center p-10 w-full">
      <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 w-full">
        {data.map((item) => (
          <Card key={item.name} className="p-6 py-4 shadow-2xs">
            <CardContent className="p-0">
              <dt className="text-sm font-medium text-muted-foreground">{item.name}</dt>
              <dd className="mt-2 flex items-baseline space-x-2.5">
                <span className="tabular-nums text-3xl font-semibold text-foreground">
                  {item.stat}
                </span>
                <span
                  className={cn(
                    item.changeType === "positive"
                      ? "text-green-800 dark:text-green-400"
                      : "text-red-800 dark:text-red-400",
                    "text-sm font-medium",
                  )}
                >
                  {item.change}
                </span>
              </dd>
            </CardContent>
          </Card>
        ))}
      </dl>
    </div>
  );
}
```


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
   every class with `.innesto-stats-cards`. Honor
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