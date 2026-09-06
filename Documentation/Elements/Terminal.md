# Terminal (`innesto/terminal`)

An animated terminal that types out commands and reveals their output line by
line — grafted from the
[Magic UI terminal](https://magicui.design/docs/components/terminal) registry
component.

![The Terminal element on the frontend](../Images/terminal-frontend.png)

## What it does

The upstream component drives its typewriter and sequential reveal with
`motion/react`. The finishing pass re-created that motion in **pure CSS**, so the
element ships **zero JavaScript**: each line carries an inline `--index` and the
reveal/typing animations are staggered off it (the same trick the
`orbiting-circles` graft uses). It honours `prefers-reduced-motion` and an
editor "Animate" toggle.

## Editor fields

| Field | Type | Purpose |
| --- | --- | --- |
| `header` | shared heading | Optional section heading above the window |
| `terminal_title` | Text | Window title bar label (e.g. a path) |
| `terminal_lines` | Collection (`innesto_terminal_lines`) | The lines |
| └ `kind` | Select | `command` · `output` · `success` · `muted` |
| └ `text` | Textarea | The line text |
| `speed` | Select | Typing speed: slow · normal · fast |
| `animate` | Checkbox | Typewriter + reveal on/off |

`command` lines get a `$` prompt and type out character by character; the others
fade in. Colours — including the window dots — come entirely from the Desiderio
semantic tokens via `color-mix`, so the element follows every preset and dark
mode.

## Demo and customization

`innesto:seed <page-uid> -e terminal` uses the maintained `library.json` demo.
The old terminal-only `fixture.json` has been removed so all 19 elements use
the same fixture format. Existing content records are unaffected.

The [command reference](../AddingContentElements.md) explains how to create
another graft. Use a new key, for example `--key project-terminal`, because
the shipped `terminal` directory already exists.
