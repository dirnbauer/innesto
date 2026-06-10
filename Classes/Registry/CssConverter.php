<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Registry;

/**
 * Converts the `css` and `cssVars` blocks of a shadcn registry item into a
 * plain per-element stylesheet. Desiderio uses the same semantic shadcn
 * variables (--primary, --muted, --radius, …), so token values map 1:1;
 * Tailwind `@theme` animation entries become a CSS custom property plus a
 * matching utility class, because the Desiderio Tailwind build does not scan
 * grafted elements.
 */
final class CssConverter
{
    /**
     * @param array<string, mixed> $item a decoded registry item
     */
    public function convert(array $item): string
    {
        $parts = [];

        $cssVars = $item['cssVars'] ?? [];
        if (is_array($cssVars)) {
            $parts[] = $this->convertVarsScope($cssVars['theme'] ?? [], ':root', emitUtilities: true);
            $parts[] = $this->convertVarsScope($cssVars['light'] ?? [], ':root');
            $parts[] = $this->convertVarsScope($cssVars['dark'] ?? [], '.dark');
        }

        $css = $item['css'] ?? [];
        if (is_array($css)) {
            $parts[] = $this->serializeBlock($css, 0);
        }

        return trim(implode("\n", array_filter($parts))) . "\n";
    }

    /**
     * @param array<string, string> $vars
     */
    private function convertVarsScope(array $vars, string $selector, bool $emitUtilities = false): string
    {
        if ($vars === []) {
            return '';
        }
        $declarations = [];
        $utilities = [];
        foreach ($vars as $name => $value) {
            $name = ltrim((string)$name, '-');
            $declarations[] = sprintf('    --%s: %s;', $name, $value);
            if ($emitUtilities && str_starts_with($name, 'animate-')) {
                $utilities[] = sprintf(".%s {\n    animation: var(--%s);\n}", $name, $name);
            }
        }
        $out = sprintf("%s {\n%s\n}", $selector, implode("\n", $declarations));
        if ($utilities !== []) {
            $out .= "\n" . implode("\n", $utilities);
        }
        return $out;
    }

    /**
     * Recursively serializes the nested registry `css` object
     * (selectors/at-rules → blocks, strings → declarations).
     *
     * @param array<string, mixed> $rules
     */
    private function serializeBlock(array $rules, int $depth): string
    {
        $indent = str_repeat('    ', $depth);
        $lines = [];
        foreach ($rules as $key => $value) {
            if (is_array($value)) {
                $lines[] = sprintf("%s%s {", $indent, $key);
                $lines[] = $this->serializeBlock($value, $depth + 1);
                $lines[] = $indent . '}';
            } else {
                $lines[] = sprintf('%s%s: %s;', $indent . '    ', $key, $value);
            }
        }
        return implode("\n", $lines);
    }
}
