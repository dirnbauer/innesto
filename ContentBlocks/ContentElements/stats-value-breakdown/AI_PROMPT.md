# Finish the Innesto graft: innesto/stats-value-breakdown

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-value-breakdown`.
The upstream shadcn registry component "Stats with Value Breakdown" (A stats with value breakdown block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-15.tsx
```tsx
import { cn } from "@/lib/utils";

const data = [
  {
    label: "After 1 year",
    value: "$2,400",
    percentage: "+8.2%",
  },
  {
    label: "After 5 years",
    value: "$14,800",
    percentage: "+24.6%",
  },
  {
    label: "After 10 years",
    value: "$38,500",
    percentage: "+52.1%",
  },
];

export function Stats15() {
  return (
    <div className="w-full max-w-2xs">
      <h3 className="text-balance text-sm font-medium text-foreground">
        Investment growth projection
      </h3>
      <ul role="list" className="mt-2 divide-y divide-border text-sm">
        {data.map((item, index) => (
          <li key={index} className="flex items-center justify-between py-3">
            <span className="text-muted-foreground">{item.label}</span>
            <span className="flex items-center gap-3 tabular-nums">
              <span className="text-right font-medium text-foreground">
                {item.value}
              </span>
              <span className="h-5 w-px bg-border" aria-hidden="true" />
              <span
                className={cn(
                  "rounded px-1.5 py-1 text-center text-xs font-semibold w-15",
                  "bg-emerald-50 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400"
                )}
              >
                {item.percentage}
              </span>
            </span>
          </li>
        ))}
      </ul>
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
   every class with `.innesto-stats-value-breakdown`. Honor
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