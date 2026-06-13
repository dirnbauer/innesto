#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Innesto content-element contract audit.
 *
 * Verifies every graft under ContentBlocks/ContentElements against the rules
 * the README and finishing pass promise: parseable config, uniquely-prefixed
 * Collection tables, no reserved child identifiers, every configured field
 * rendered, token-only styling (no raw colours), no inline scripts, a backend
 * preview, a valid 16x16 icon, well-formed XLIFF, and site-set registration.
 *
 * Dependency-light on purpose so CI can run it without resolving the TYPO3 /
 * Desiderio runtime: YAML is read via ext-yaml when present, otherwise via
 * symfony/yaml from an autoloader (AUDIT_AUTOLOAD env or <root>/vendor).
 *
 * Exit code 0 = green (no errors; warnings allowed), 1 = errors found,
 * 2 = the audit could not run.
 *
 * Usage: php scripts/audit-content-elements.php [repo-root]
 */

$root = rtrim($argv[1] ?? getcwd(), DIRECTORY_SEPARATOR);
$elementsRoot = $root . '/ContentBlocks/ContentElements';
if (!is_dir($elementsRoot)) {
    fwrite(STDERR, "Missing ContentBlocks/ContentElements in {$root}\n");
    exit(2);
}

/** @var callable(string):array<mixed> $parseYaml */
$parseYaml = (static function () use ($root): callable {
    if (function_exists('yaml_parse_file')) {
        return static fn(string $f): array => (array)yaml_parse_file($f);
    }
    foreach ([getenv('AUDIT_AUTOLOAD') ?: '', $root . '/vendor/autoload.php', getcwd() . '/vendor/autoload.php'] as $autoload) {
        if ($autoload !== '' && is_file($autoload)) {
            require $autoload;
            if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
                return static fn(string $f): array => (array)\Symfony\Component\Yaml\Yaml::parseFile($f);
            }
        }
    }
    fwrite(STDERR, "No YAML reader: install ext-yaml or point AUDIT_AUTOLOAD at a symfony/yaml autoloader.\n");
    exit(2);
})();

// Registered wizard groups (blocks.so families) + always-valid 'default'.
$registeredGroups = ['default' => true];
$tcaOverride = $root . '/Configuration/TCA/Overrides/tt_content.php';
if (is_file($tcaOverride)) {
    if (preg_match_all("/^\s*'([a-z0-9-]+)' =>/m", (string)file_get_contents($tcaOverride), $m)) {
        foreach ($m[1] as $g) {
            $registeredGroups[$g] = true;
        }
    }
}

// Site-set registered blocks.
$setBlocks = [];
$setConfig = $root . '/Configuration/Sets/Innesto/config.yaml';
if (is_file($setConfig)) {
    $set = $parseYaml($setConfig);
    foreach ((array)($set['optionalDependencies'] ?? []) as $dep) {
        $setBlocks[$dep] = true;
    }
}

$colorPattern = '/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(|\boklch\(/';

$errors = [];
$warnings = [];
$tables = [];          // table => element (uniqueness)
$elements = [];

/**
 * Recursively collect field identifiers and validate Collection tables.
 *
 * @param array<mixed> $fields
 * @param array<string,bool> $rendered identifiers present in frontend.html
 */
$walkFields = function (array $fields, string $key, array &$err, array &$warn, array &$tables, array $rendered) use (&$walkFields): void {
    foreach ($fields as $field) {
        if (!is_array($field) || !isset($field['identifier'])) {
            continue;
        }
        $id = (string)$field['identifier'];
        $type = (string)($field['type'] ?? '');

        if ($id === 'label') {
            $err[] = "child field named 'label' is reserved by Content Blocks — rename to 'title'";
        }

        if ($type === 'Collection') {
            $table = (string)($field['table'] ?? '');
            if ($table === '') {
                $err[] = "Collection '{$id}' has no explicit table: (cross-element table sharing corrupts schemas)";
            } else {
                if (!str_starts_with($table, 'innesto_')) {
                    $err[] = "Collection '{$id}' table '{$table}' is not prefixed with 'innesto_'";
                }
                if (isset($tables[$table])) {
                    $err[] = "Collection table '{$table}' already used by '{$tables[$table]}' (must be unique)";
                } else {
                    $tables[$table] = $key . ':' . $id;
                }
            }
            $walkFields((array)($field['fields'] ?? []), $key, $err, $warn, $tables, $rendered);
            continue;
        }

        if ($type === 'File' || !empty($field['useExistingField'])) {
            continue; // File needs FAL; existing fields (header) are rendered via their own helper
        }
        // Every configured editor field should be rendered somewhere.
        if (!isset($rendered[$id])) {
            $warn[] = "configured field '{$id}' is not referenced in frontend.html (backend-only?)";
        }
    }
};

foreach (glob($elementsRoot . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
    $key = basename($dir);
    $elements[] = $key;
    $err = [];
    $warn = [];

    $configFile = $dir . '/config.yaml';
    if (!is_file($configFile)) {
        $errors[$key] = ['missing config.yaml'];
        continue;
    }
    $config = $parseYaml($configFile);

    // name / typeName / metadata
    if (($config['name'] ?? '') !== 'innesto/' . $key) {
        $err[] = "name '" . ($config['name'] ?? '') . "' should be 'innesto/{$key}'";
    }
    if (($config['typeName'] ?? '') === '') {
        $err[] = 'missing typeName';
    }
    if (trim((string)($config['title'] ?? '')) === '') {
        $err[] = 'missing title';
    }
    if (trim((string)($config['description'] ?? '')) === '') {
        $warn[] = 'missing description';
    }
    $group = (string)($config['group'] ?? 'default');
    if (!isset($registeredGroups[$group])) {
        $warn[] = "group '{$group}' is not registered in Configuration/TCA/Overrides/tt_content.php";
    }
    if (!isset($setBlocks['innesto/' . $key])) {
        $err[] = "not registered in Configuration/Sets/Innesto/config.yaml (hidden from the wizard)";
    }

    // Templates / assets presence
    $frontend = $dir . '/templates/frontend.html';
    $frontendSrc = is_file($frontend) ? (string)file_get_contents($frontend) : '';
    if ($frontendSrc === '') {
        $err[] = 'missing templates/frontend.html';
    } else {
        if (!str_contains($frontendSrc, 'frontend.css')) {
            $warn[] = 'frontend.html does not include its frontend.css';
        }
        if (preg_match('/<script\b/', $frontendSrc)) {
            $err[] = 'inline <script> in frontend.html (move to assets/ + f:asset.script)';
        }
    }
    if (!is_file($dir . '/templates/backend-preview.fluid.html')) {
        $err[] = 'missing templates/backend-preview.fluid.html';
    }
    if (!is_file($dir . '/assets/frontend.css')) {
        $err[] = 'missing assets/frontend.css';
    }

    // Build the set of identifiers referenced in the frontend template.
    $rendered = [];
    if (preg_match_all('/[A-Za-z_][A-Za-z0-9_]*\.([a-z0-9_]+)|field:\s*\'([a-z0-9_]+)\'/', $frontendSrc, $rm)) {
        foreach (array_merge($rm[1], $rm[2]) as $hit) {
            if ($hit !== '') {
                $rendered[$hit] = true;
            }
        }
    }
    $walkFields((array)($config['fields'] ?? []), $key, $err, $warn, $tables, $rendered);

    // Icon
    $icon = $dir . '/assets/icon.svg';
    if (!is_file($icon)) {
        $err[] = 'missing assets/icon.svg';
    } else {
        $svg = (string)file_get_contents($icon);
        $dom = new DOMDocument();
        if (!@$dom->loadXML($svg)) {
            $err[] = 'icon.svg is not well-formed XML';
        }
        if (!str_contains($svg, 'viewBox="0 0 16 16"')) {
            $warn[] = 'icon.svg is not a 16x16 viewBox';
        }
        if (!str_contains($svg, 'currentColor')) {
            $warn[] = 'icon.svg does not use currentColor (theme-aware paint)';
        }
    }

    // Labels
    $labels = $dir . '/language/labels.xlf';
    if (!is_file($labels)) {
        $err[] = 'missing language/labels.xlf';
    } else {
        $xlf = (string)file_get_contents($labels);
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xlf)) {
            $err[] = 'labels.xlf is not well-formed XML';
        } else {
            foreach (['title', 'description'] as $unit) {
                if (!preg_match('/id="' . $unit . '"/', $xlf)) {
                    $warn[] = "labels.xlf has no '{$unit}' unit";
                }
            }
        }
    }

    // Token-only styling: scan CSS / JS / templates for raw colour literals.
    foreach (glob($dir . '/assets/*.{css,js}', GLOB_BRACE) ?: [] as $asset) {
        foreach (preg_split('/\R/', (string)file_get_contents($asset)) as $n => $line) {
            if (preg_match($colorPattern, $line)) {
                $err[] = 'raw colour in ' . basename($asset) . ':' . ($n + 1) . ' — use semantic tokens';
            }
        }
    }
    foreach (preg_split('/\R/', $frontendSrc) as $n => $line) {
        if (preg_match($colorPattern, $line)) {
            $err[] = 'raw colour in frontend.html:' . ($n + 1);
        }
    }

    if ($err) {
        $errors[$key] = $err;
    }
    if ($warn) {
        $warnings[$key] = $warn;
    }
}

// Report
$reset = "\033[0m";
$red = "\033[31m";
$yellow = "\033[33m";
$green = "\033[32m";
echo "Innesto content-element audit — " . count($elements) . " elements, " . count($tables) . " collection tables\n";
echo str_repeat('-', 64) . "\n";
foreach ($elements as $key) {
    $e = $errors[$key] ?? [];
    $w = $warnings[$key] ?? [];
    if (!$e && !$w) {
        echo "{$green}✓{$reset} {$key}\n";
        continue;
    }
    $mark = $e ? "{$red}✗{$reset}" : "{$yellow}!{$reset}";
    echo "{$mark} {$key}\n";
    foreach ($e as $msg) {
        echo "    {$red}error{$reset}  {$msg}\n";
    }
    foreach ($w as $msg) {
        echo "    {$yellow}warn{$reset}   {$msg}\n";
    }
}
echo str_repeat('-', 64) . "\n";
$errorCount = array_sum(array_map('count', $errors));
$warnCount = array_sum(array_map('count', $warnings));
printf("%d error(s), %d warning(s)\n", $errorCount, $warnCount);
exit($errorCount > 0 ? 1 : 0);
