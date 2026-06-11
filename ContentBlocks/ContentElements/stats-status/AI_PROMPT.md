# Finish the Innesto graft: innesto/stats-status

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-status`.
The upstream shadcn registry component "Stats with Status" (A stats with status block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-06.tsx
```tsx
"use client";

import { Card, CardContent } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { AlertTriangle, Check, ChevronRight, Eye } from "lucide-react";
import Link from "next/link";

const data = [
  {
    name: "Europe",
    stat: "$10,023",
    goalsAchieved: 3,
    status: "observe",
    href: "#",
  },
  {
    name: "North America",
    stat: "$14,092",
    goalsAchieved: 5,
    status: "within",
    href: "#",
  },
  {
    name: "Asia",
    stat: "$113,232",
    goalsAchieved: 1,
    status: "critical",
    href: "#",
  },
];

export default function Stats06() {
  return (
    <div className="flex items-center justify-center p-10 w-full">
      <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 w-full">
        {data.map((item) => (
          <Card key={item.name} className="p-6 relative shadow-2xs">
            <CardContent className="p-0">
              <dt className="text-sm font-medium text-muted-foreground">{item.name}</dt>
              <dd className="tabular-nums text-3xl font-semibold text-foreground">{item.stat}</dd>
              <div className="group relative mt-6 flex items-center space-x-4 rounded-md bg-muted/60 p-2 hover:bg-muted">
                <div className="flex w-full items-center justify-between truncate">
                  <div className="flex items-center space-x-3">
                    <span
                      className={cn(
                        "flex h-9 w-9 shrink-0 items-center justify-center rounded",
                        item.status === "within"
                          ? "bg-emerald-500 text-white"
                          : item.status === "observe"
                            ? "bg-yellow-500 text-white"
                            : "bg-red-500 text-white",
                      )}
                    >
                      {item.status === "within" ? (
                        <Check className="size-4 shrink-0" aria-hidden={true} />
                      ) : item.status === "observe" ? (
                        <Eye className="size-4 shrink-0" aria-hidden={true} />
                      ) : (
                        <AlertTriangle className="size-4 shrink-0" aria-hidden={true} />
                      )}
                    </span>
                    <dd>
                      <p className="text-pretty text-sm text-muted-foreground">
                        <Link href={item.href} className="focus:outline-none">
                          {/* Extend link to entire card */}
                          <span className="absolute inset-0" aria-hidden={true} />
                          {item.goalsAchieved}/5 goals
                        </Link>
                      </p>
                      <p
                        className={cn(
                          "text-sm font-medium",
                          item.status === "within"
                            ? "text-emerald-800 dark:text-emerald-500"
                            : item.status === "observe"
                              ? "text-yellow-800 dark:text-yellow-500"
                              : "text-red-800 dark:text-red-500",
                        )}
                      >
                        {item.status}
                      </p>
                    </dd>
                  </div>
                  <ChevronRight
                    className="size-5 shrink-0 text-muted-foreground/60 group-hover:text-muted-foreground"
                    aria-hidden={true}
                  />
                </div>
              </div>
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
   every class with `.innesto-stats-status`. Honor
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