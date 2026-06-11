# Finish the Innesto graft: innesto/stats-usage-dashboard

You are inside the element directory `/Users/dirnbauer/projects/innesto/ContentBlocks/ContentElements/stats-usage-dashboard`.
The upstream shadcn registry component "Stats Usage Dashboard" (A stats usage dashboard block.) was scaffolded
here; your job is the finishing pass that cannot be done mechanically.

## Upstream sources

### sources/stats-12.tsx
```tsx
"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { ChartContainer, type ChartConfig } from "@/components/ui/chart";
import { Cell, Pie, PieChart } from "recharts";

interface UsageItem {
  name: string;
  current: string;
  limit: string;
  percentage: number;
  href?: string;
}

const usageData: UsageItem[] = [
  { name: "ISR Reads", current: "358K", limit: "1M", percentage: 35.8 },
  { name: "Edge Requests", current: "317K", limit: "1M", percentage: 31.7 },
  {
    name: "Fast Origin Transfer",
    current: "3.07 GB",
    limit: "10 GB",
    percentage: 30.7,
  },
  {
    name: "Speed Insights Data Points",
    current: "791",
    limit: "10K",
    percentage: 7.9,
  },
  {
    name: "Fast Data Transfer",
    current: "4.98 GB",
    limit: "100 GB",
    percentage: 5.0,
  },
  {
    name: "Function Duration",
    current: "3.1 GB-Hrs",
    limit: "100 GB-Hrs",
    percentage: 3.1,
  },
  {
    name: "Web Analytics Events",
    current: "1.3K",
    limit: "50K",
    percentage: 2.6,
  },
  { name: "ISR Writes", current: "4.8K", limit: "200K", percentage: 2.4 },
  {
    name: "Function Invocations",
    current: "19K",
    limit: "1M",
    percentage: 1.9,
  },
  {
    name: "Image Optimization - Cache Reads",
    current: "4.3K",
    limit: "300K",
    percentage: 1.4,
  },
];

const chartConfig = {
  used: {
    label: "Used",
    color: "hsl(var(--primary))",
  },
  remaining: {
    label: "Remaining",
    color: "hsl(var(--muted))",
  },
} satisfies ChartConfig;

function DonutChart({ percentage }: { percentage: number }) {
  const backgroundData = [{ name: "background", value: 100, fill: "#E5E7EB" }];
  const foregroundData = [
    {
      name: "used",
      value: Math.max(0, Math.min(100, Number(percentage))),
      fill: "#3B82F6",
    },
    {
      name: "empty",
      value: 100 - Math.max(0, Math.min(100, Number(percentage))),
      fill: "transparent",
    },
  ];

  return (
    <ChartContainer config={chartConfig} className="w-6 h-6 shrink-0 aspect-square">
      <PieChart>
        <Pie
          data={backgroundData}
          dataKey="value"
          nameKey="name"
          cx="50%"
          cy="50%"
          innerRadius={6}
          outerRadius={10}
          isAnimationActive={false}
        >
          {backgroundData.map((entry, index) => (
            <Cell key={`bg-cell-${index}`} fill={entry.fill} />
          ))}
        </Pie>
        <Pie
          data={foregroundData}
          dataKey="value"
          nameKey="name"
          cx="50%"
          cy="50%"
          innerRadius={6}
          outerRadius={10}
          startAngle={90}
          endAngle={-270}
        >
          {foregroundData.map((entry, index) => (
            <Cell key={`fg-cell-${index}`} fill={entry.fill} />
          ))}
        </Pie>
      </PieChart>
    </ChartContainer>
  );
}

export default function Stats12() {
  return (
    <Card className="w-full max-w-md gap-3 py-5 shadow-2xs">
      <CardHeader className="px-5">
        <div className="flex items-center justify-between">
          <div className="flex flex-col">
            <h3 className="text-balance text-sm font-medium">Last 30 days</h3>
            <p className="text-pretty text-xs text-muted-foreground font-medium">
              Updated just now
            </p>
          </div>
          <Button size="sm" className="h-6 text-xs font-medium">
            Upgrade
          </Button>
        </div>
      </CardHeader>

      <CardContent className="pt-0 px-3">
        <div className="space-y-0">
          {usageData.map((item, index) => (
            <div
              key={item.name}
              className={`flex items-center gap-3 p-2 rounded-sm transition-colors hover:bg-muted/50 ${
                index % 2 === 1 ? "bg-muted/20" : ""
              }`}
            >
              <DonutChart percentage={item.percentage} />
              <span className="text-sm flex-1 truncate leading-4">{item.name}</span>
              <span className="text-xs font-medium tabular-nums tracking-tighter text-muted-foreground">
                {item.current} / <span className="text-foreground">{item.limit}</span>
              </span>
            </div>
          ))}
        </div>
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
   every class with `.innesto-stats-usage-dashboard`. Honor
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