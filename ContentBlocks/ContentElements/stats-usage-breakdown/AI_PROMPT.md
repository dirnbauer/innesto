# Finish the Innesto graft: innesto/stats-usage-breakdown

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-usage-breakdown`.
The upstream shadcn registry component "Stats with Usage Breakdown" (A stats with usage breakdown block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-14.tsx
```tsx
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

interface UsageItem {
  label: string;
  amount: number;
  percentage: number;
  color: "emerald" | "amber" | "rose";
}

const data: UsageItem[] = [
  { label: "Compute", amount: 450, percentage: 52.3, color: "emerald" },
  { label: "Storage", amount: 285, percentage: 33.1, color: "amber" },
  { label: "Bandwidth", amount: 125, percentage: 14.6, color: "rose" },
];

const colorClasses = {
  emerald: "bg-emerald-500 dark:bg-emerald-400",
  amber: "bg-amber-500 dark:bg-amber-400",
  rose: "bg-rose-500 dark:bg-rose-400",
};

export function Stats14() {
  return (
    <Card className="w-full max-w-sm shadow-none">
      <CardContent className="flex flex-col justify-between pt-0">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="text-balance text-sm font-bold text-foreground">Usage</h3>
            <Badge
              variant="secondary"
              className="bg-amber-50 text-amber-700 ring-1 ring-amber-500/30 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20"
            >
              +12.5%
            </Badge>
          </div>

          <p className="text-pretty mt-2 flex items-baseline gap-2">
            <span className="text-xl text-foreground">$860</span>
            <span className="text-sm text-muted-foreground">this month</span>
          </p>

          <div className="mt-4">
            <p className="text-pretty text-sm font-medium text-foreground">
              Resource breakdown
            </p>
            <div className="mt-2 flex items-center gap-0.5">
              {data.map((item, index) => (
                <div
                  key={index}
                  className={`${colorClasses[item.color]} h-1.5 rounded-xs`}
                  style={{ width: `${item.percentage}%` }}
                />
              ))}
            </div>
          </div>

          <ul role="list" className="mt-5 space-y-2">
            {data.map((item, index) => (
              <li key={index} className="flex items-center gap-2 text-xs">
                <span
                  className={`${colorClasses[item.color]} size-2.5 rounded-xs`}
                  aria-hidden="true"
                />
                <span className="text-foreground">{item.label}</span>
                <span className="text-muted-foreground">
                  (${item.amount} / {item.percentage}%)
                </span>
              </li>
            ))}
          </ul>
        </div>

        <p className="text-pretty mt-6 text-xs text-muted-foreground">
          Configure limits in{" "}
          <a
            href="#"
            className="text-emerald-600 hover:underline dark:text-emerald-400"
          >
            resource settings.
          </a>
        </p>
      </CardContent>
    </Card>
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
   every class with `.innesto-stats-usage-breakdown`. Honor
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