# Blocks stats (`innesto/stats-*`)

Innesto ships the complete stats family from
[blocks.so/stats](https://blocks.so/stats) as TYPO3 Content Blocks. The upstream
page exposes 15 shadcn/ui registry items (`@blocks-so/stats-01` through
`@blocks-so/stats-15`); this extension keeps the original TSX source for
provenance and gives each finished content element a semantic editor key.

## Mapping

| blocks.so item | Upstream title | Innesto element | Source kept in |
| --- | --- | --- | --- |
| `stats-01` | Stats with Trending | `innesto/stats-trending` | `sources/stats-01.tsx` |
| `stats-02` | Stats with Borders | `innesto/stats-borders` | `sources/stats-02.tsx` |
| `stats-03` | Stats with Card Layout | `innesto/stats-cards` | `sources/stats-03.tsx` |
| `stats-04` | Stats with Badges | `innesto/stats-badges` | `sources/stats-04.tsx` |
| `stats-05` | Stats with Links | `innesto/stats-links` | `sources/stats-05.tsx` |
| `stats-06` | Stats with Status | `innesto/stats-status` | `sources/stats-06.tsx` |
| `stats-07` | Stats with Circular Progress | `innesto/stats-circular-progress` | `sources/stats-07.tsx` |
| `stats-08` | Stats with Circular Progress and Links | `innesto/stats-circular-links` | `sources/stats-08.tsx` |
| `stats-09` | Stats with Progress | `innesto/stats-progress` | `sources/stats-09.tsx` |
| `stats-10` | Stats with Area Chart | `innesto/stats-area-chart` | `sources/stats-10.tsx` |
| `stats-11` | Stats Dashboard with Progress Bars | `innesto/stats-dashboard` | `sources/stats-11.tsx` |
| `stats-12` | Stats Usage Dashboard | `innesto/stats-usage-dashboard` | `sources/stats-12.tsx` |
| `stats-13` | Stats with Segmented Progress | `innesto/stats-segmented-progress` | `sources/stats-13.tsx` |
| `stats-14` | Stats with Usage Breakdown | `innesto/stats-usage-breakdown` | `sources/stats-14.tsx` |
| `stats-15` | Stats with Value Breakdown | `innesto/stats-value-breakdown` | `sources/stats-15.tsx` |

All 15 elements live under `ContentBlocks/ContentElements/`, use the `stats`
wizard group, and are listed in
`Configuration/Sets/Innesto/config.yaml` as `optionalDependencies` so they
appear in the New Content Element wizard on Desiderio sites.

## What changed from React

The blocks.so components are React examples. Innesto preserves them in
`sources/`, but the runtime is Fluid, CSS, SVG, and one small asset script for
the area-chart sparkline. The finishing pass for each element did the same
conversion work:

1. Model repeated metrics as Content Blocks `Collection` fields with explicit
   `innesto_*` table names.
2. Replace hard-coded React demo arrays with editor fields in `config.yaml`.
3. Translate JSX structure to `templates/frontend.html` and Desiderio layout
   components.
4. Move styling into `assets/frontend.css` with semantic tokens instead of raw
   colors.
5. Add `templates/backend-preview.fluid.html`, `language/labels.xlf`, and a
   16x16 backend icon for each element.
6. Register every block in the Innesto site set so editors can add it.

## Checked steps

These are the checks to repeat after adding another blocks.so family:

```bash
# The blocks page currently lists stats-01 through stats-15.
# Compare that inventory with local element folders and sources.
find ContentBlocks/ContentElements -mindepth 1 -maxdepth 1 -type d -name 'stats-*' | sort

# Each Collection must declare a stable, prefixed table. This prints nothing
# when every Collection is followed by table:.
awk '
  /type:[[:space:]]*Collection/ { file=FILENAME; line=FNR; need=1; next }
  need && /table:/ { need=0; next }
  need { print file ":" line ": Collection missing table:"; need=0 }
' ContentBlocks/ContentElements/stats-*/config.yaml

# Run the full contract audit. If host PHP has no ext-yaml, point AUDIT_AUTOLOAD
# at an autoloader that provides symfony/yaml.
composer audit:content-elements

# Validate package metadata.
composer validate --no-check-publish
```

The full audit checks config parsing, site-set registration, collection table
uniqueness, template references, token-only CSS, no inline scripts, backend
previews, icons, and XLIFF files.
