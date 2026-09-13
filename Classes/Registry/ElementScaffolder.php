<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Registry;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes a Content Blocks element skeleton for a fetched registry item:
 * converted CSS, the original sources for the manual finishing pass, a
 * config.yaml, labels, an icon, and a Fluid 5 template stub that already
 * follows the Desiderio conventions (d:layout.section, per-element CSS,
 * semantic tokens).
 */
final class ElementScaffolder
{
    public function __construct(private readonly CssConverter $cssConverter) {}

    /**
     * @param array<string, mixed> $item
     * @return list<string> relative paths written
     */
    public function scaffold(array $item, string $elementKey, string $targetDirectory): array
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $elementKey)) {
            throw new \InvalidArgumentException('Element keys must contain lowercase letters, numbers and single hyphens.');
        }
        $elementDir = rtrim($targetDirectory, '/') . '/' . $elementKey;
        if (file_exists($elementDir) || is_link($elementDir)) {
            throw new \RuntimeException('Element directory already exists: ' . $elementDir, 1765432104);
        }
        $typeName = 'innesto_' . str_replace('-', '', $elementKey);
        foreach (glob(rtrim($targetDirectory, '/') . '/*/config.yaml') ?: [] as $configFile) {
            if ((Yaml::parseFile($configFile)['typeName'] ?? null) === $typeName) {
                throw new \InvalidArgumentException('Element key conflicts with the existing CType in ' . $configFile);
            }
        }
        $sources = [];
        foreach ($item['files'] ?? [] as $file) {
            $name = basename((string)($file['path'] ?? 'source.txt'));
            if ($name === '' || $name === '.' || $name === '..' || isset($sources[$name])) {
                throw new \InvalidArgumentException('Registry source filenames must be non-empty and unique: ' . $name);
            }
            $sources[$name] = (string)($file['content'] ?? '');
        }

        // Render everything before creating the directory, so malformed registry
        // data cannot leave an incomplete element that blocks a retry.
        $files = [
            'assets/frontend.css' => $this->buildCss($item, $elementKey),
            'assets/icon.svg' => $this->buildIcon(),
            'config.yaml' => $this->buildConfig($item, $elementKey, $typeName),
            'language/labels.xlf' => $this->buildLabels($item, $elementKey),
            'templates/frontend.html' => $this->buildTemplate($item, $elementKey),
        ];
        foreach ($sources as $name => $content) {
            $files['sources/' . $name] = $content;
        }
        $filesystem = new Filesystem();
        try {
            foreach ($files as $path => $content) {
                $filesystem->dumpFile($elementDir . '/' . $path, $content);
            }
        } catch (\Throwable $exception) {
            $filesystem->remove($elementDir);
            throw $exception;
        }
        return array_keys($files);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildCss(array $item, string $elementKey): string
    {
        $converted = $this->cssConverter->convert($item);
        $header = sprintf(
            "/* Grafted from registry item \"%s\" (%s). Tokens map onto the Desiderio shadcn variables. */\n",
            $item['name'] ?? $elementKey,
            $item['title'] ?? 'no title'
        );
        $skeleton = sprintf(
            "\n.innesto-%1\$s {\n    /* TODO: port component styles from sources/ — use var(--primary), var(--muted), var(--radius), … */\n}\n",
            $elementKey
        );
        return $header . $converted . $skeleton;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildConfig(array $item, string $elementKey, string $typeName): string
    {
        $title = (string)($item['title'] ?? ucwords(str_replace('-', ' ', $elementKey)));
        $description = (string)($item['description'] ?? 'Grafted from a shadcn registry item.');
        // The registry item's first category becomes the wizard group. If the
        // registry does not expose categories, keep the element out of TYPO3's
        // generic "default" bucket and place it in Innesto's Components group.
        $group = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', (string)(($item['categories'] ?? [])[0] ?? 'components')) ?? 'components');
        return Yaml::dump([
            'name' => 'innesto/' . $elementKey,
            'typeName' => $typeName,
            'title' => $title,
            'description' => $description,
            'group' => $group ?: 'components',
            'keywords' => $this->buildKeywords($item, $elementKey, $group),
            'prefixFields' => false,
            'basics' => ['TYPO3/Appearance'],
            'fields' => [['identifier' => 'header', 'useExistingField' => true]],
        ], 4, 2) . "# TODO: model component props from sources/ as Content Blocks fields.\n";
    }

    /**
     * @param array<string, mixed> $item
     * @return list<string>
     */
    private function buildKeywords(array $item, string $elementKey, string $group): array
    {
        $keywords = [
            str_replace('-', ' ', $group),
            str_replace('-', ' ', $elementKey),
        ];

        foreach ((array)($item['categories'] ?? []) as $category) {
            $keywords[] = str_replace('-', ' ', (string)$category);
        }

        $sourceText = implode(' ', [
            (string)($item['title'] ?? ''),
            (string)($item['description'] ?? ''),
            (string)($item['name'] ?? ''),
        ]);
        $stopWords = array_fill_keys([
            'and', 'are', 'can', 'for', 'from', 'into', 'that', 'the', 'this',
            'used', 'with', 'your',
        ], true);

        foreach (preg_split('/[^a-z0-9]+/i', strtolower($sourceText)) ?: [] as $word) {
            if (strlen($word) < 3 || isset($stopWords[$word])) {
                continue;
            }
            $keywords[] = $word;
        }

        $unique = [];
        foreach ($keywords as $keyword) {
            $keyword = trim(strtolower($keyword));
            if ($keyword !== '') {
                $unique[$keyword] = $keyword;
            }
        }

        return array_slice(array_values($unique), 0, 8);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildTemplate(array $item, string $elementKey): string
    {
        $sourceFiles = implode(', ', array_map(
            static fn(array $f): string => basename((string)($f['path'] ?? '')),
            $item['files'] ?? []
        ));
        return <<<HTML
<html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
      xmlns:cb="http://typo3.org/ns/TYPO3/CMS/ContentBlocks/ViewHelpers"
      xmlns:d="http://typo3.org/ns/Webconsulting/Desiderio/Components/ComponentCollection"
      data-namespace-typo3-fluid="true">

<f:asset.css identifier="innesto-$elementKey" href="{cb:assetPath()}/frontend.css"/>

<!--
    TODO: finish the graft. The upstream React source is preserved in
    sources/ ($sourceFiles). Translate its markup to Fluid here; interactive
    behaviour goes to Alpine.js or a small script in assets/. Styles belong
    in assets/frontend.css using the Desiderio semantic tokens.
-->
<d:layout.section spacing="md" class="innesto-$elementKey">
    <d:layout.container>
        <f:if condition="{data.header}">
            <h2>{data -> f:render.text(field: 'header')}</h2>
        </f:if>
    </d:layout.container>
</d:layout.section>

</html>
HTML . "\n";
    }

    /**
     * @param array<string, mixed> $item
     */
    private function buildLabels(array $item, string $elementKey): string
    {
        $title = htmlspecialchars((string)($item['title'] ?? $elementKey), ENT_XML1);
        $description = htmlspecialchars((string)($item['description'] ?? ''), ENT_XML1);
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en">
  <file id="$elementKey">
    <unit id="title">
      <segment>
        <source>$title</source>
      </segment>
    </unit>
    <unit id="description">
      <segment>
        <source>$description</source>
      </segment>
    </unit>
  </file>
</xliff>
XML . "\n";
    }

    private function buildIcon(): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" class="icon-root">
  <title>Innesto graft icon</title>
  <style>.icon-root{color:var(--icon-color-primary,currentColor)}.accent{stroke:var(--icon-color-accent,currentColor);fill:none}</style>
  <path d="M8 13.5V7"/>
  <path d="M8 7C8 4.5 6 3 3.5 3c0 2.5 1.5 4.5 4.5 4Z"/>
  <path d="M8 9c0-2.5 2-4 4.5-4 0 2.5-1.5 4.5-4.5 4Z" class="accent"/>
</svg>
SVG . "\n";
    }

}
