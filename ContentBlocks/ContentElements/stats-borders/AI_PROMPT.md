# Finish the Innesto graft: innesto/stats-borders

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-borders`.
The upstream shadcn registry component "Stats with Borders" (A stats with borders block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-02.tsx
```tsx
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { TrendingDown, TrendingUp } from "lucide-react";

const stats = [
  {
    metric: "Active Users",
    current: "128,456",
    previous: "115,789",
    difference: "10.9%",
    trend: "up",
  },
  {
    metric: "Conversion Rate",
    current: "5.32%",
    previous: "6.18%",
    difference: "0.86%",
    trend: "down",
  },
  {
    metric: "Avg. Session Duration",
    current: "3m 42s",
    previous: "3m 15s",
    difference: "13.8%",
    trend: "up",
  },
];

export default function Stats02() {
  return (
    <div className="flex items-center justify-center p-10">
      <div className="grid grid-cols-1 divide-y bg-border divide-border overflow-hidden rounded-lg md:grid-cols-3 md:divide-x md:divide-y-0">
        {stats.map((item) => (
          <Card
            key={item.metric}
            className="rounded-none border-0 shadow-sm py-0"
          >
            <CardContent className="p-4 sm:p-6">
              <CardTitle className="text-base font-normal">
                {item.metric}
              </CardTitle>
              <div className="mt-1 flex items-baseline gap-2 md:block lg:flex">
                <div className="tabular-nums flex items-baseline text-2xl font-semibold text-primary">
                  {item.current}
                  <span className="tabular-nums ml-2 text-sm font-medium text-muted-foreground">
                    from {item.previous}
                  </span>
                </div>

                <Badge
                  variant="outline"
                  className={cn(
                    "tabular-nums inline-flex items-center px-1.5 ps-2.5 py-0.5 text-xs font-medium md:mt-2 lg:mt-0",
                    item.trend === "up"
                      ? "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                      : "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400"
                  )}
                >
                  {item.trend === "up" ? (
                    <TrendingUp className="mr-0.5 -ml-1 h-5 w-5 shrink-0 self-center text-green-500" />
                  ) : (
                    <TrendingDown className="mr-0.5 -ml-1 h-5 w-5 shrink-0 self-center text-red-500" />
                  )}

                  <span className="sr-only">
                    {" "}
                    {item.trend === "up" ? "Increased" : "Decreased"} by{" "}
                  </span>
                  {item.difference}
                </Badge>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
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
   every class with `.innesto-stats-borders`. Honor
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