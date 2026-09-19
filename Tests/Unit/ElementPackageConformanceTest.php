<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The package contract every shipped graft has to satisfy — the rules the
 * README, the finishing prompt and the seeder rely on, checked per element:
 *
 *  P1 the element package is complete (config, both templates, CSS, icon,
 *     labels)
 *  P2 its metadata is wired into TYPO3 (name, typeName, a registered wizard
 *     group, keywords, and the site set entry without which the element stays
 *     invisible)
 *  P3 Collection tables are explicit, innesto-prefixed and unique across the
 *     extension — sharing one corrupts both schemas
 *  P4 the editor's Appearance palette reaches d:layout.section
 *  P5 styling stays on the Desiderio tokens: no raw colour literals, no
 *     inline <script>
 *  P6 every configured editor field is rendered by the frontend template
 *  P7 library.json is a demo record the seeder can actually apply
 *
 * Template composition (which components a graft may use) is asserted in
 * ContentElementConformanceTest; parsing in the functional
 * ShippedTemplatesLintTest.
 */
final class ElementPackageConformanceTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../..';

    private const string ELEMENTS = self::ROOT . '/ContentBlocks/ContentElements';

    /** Colour literals that bypass the semantic tokens. */
    private const string RAW_COLOUR = '/#[0-9a-fA-F]{3,8}\b|\brgba?\(|\bhsla?\(|\boklch\(/';

    /**
     * @return iterable<string, array{string}>
     */
    public static function elementKeys(): iterable
    {
        $directories = glob(self::ELEMENTS . '/*', GLOB_ONLYDIR);
        self::assertIsArray($directories);
        self::assertNotEmpty($directories);

        foreach ($directories as $directory) {
            $key = basename($directory);
            yield $key => [$key];
        }
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p1TheElementPackageIsComplete(string $key): void
    {
        foreach ([
            'config.yaml',
            'library.json',
            'assets/frontend.css',
            'assets/icon.svg',
            'language/labels.xlf',
            'templates/frontend.html',
            'templates/backend-preview.fluid.html',
        ] as $file) {
            self::assertFileExists(self::ELEMENTS . '/' . $key . '/' . $file);
        }

        $icon = $this->read($key, 'assets/icon.svg');
        self::assertNotFalse(simplexml_load_string($icon), 'icon.svg must be well-formed XML');
        self::assertStringContainsString('viewBox="0 0 16 16"', $icon, 'icons are 16x16 line art');
        self::assertStringContainsString('currentColor', $icon, 'icons paint with currentColor');

        $labels = $this->read($key, 'language/labels.xlf');
        self::assertNotFalse(simplexml_load_string($labels), 'labels.xlf must be well-formed XML');
        foreach (['title', 'description'] as $unit) {
            self::assertStringContainsString('id="' . $unit . '"', $labels);
        }
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p2TheMetadataIsWiredIntoTypo3(string $key): void
    {
        $config = $this->config($key);

        self::assertSame('innesto/' . $key, $config['name'] ?? null);
        self::assertMatchesRegularExpression('/^innesto_[a-z0-9]+$/D', (string)($config['typeName'] ?? ''));
        self::assertNotSame('', trim((string)($config['title'] ?? '')));
        self::assertNotSame('', trim((string)($config['description'] ?? '')));

        $group = (string)($config['group'] ?? 'default');
        self::assertNotSame('default', $group, 'grafts belong in a semantic wizard group');
        self::assertContains($group, self::registeredWizardGroups());

        $keywords = $config['keywords'] ?? null;
        self::assertIsArray($keywords);
        self::assertNotEmpty($keywords);
        foreach ($keywords as $keyword) {
            self::assertIsString($keyword);
            self::assertNotSame('', trim($keyword));
            self::assertNotSame('default', strtolower(trim($keyword)));
        }

        self::assertContains(
            'innesto/' . $key,
            self::siteSetBlocks(),
            'unlisted blocks stay hidden in the wizard on sites that restrict content blocks per set',
        );
    }

    #[Test]
    public function p3CollectionTablesAreScopedAndUnique(): void
    {
        $owners = [];
        foreach (self::elementKeys() as [$key]) {
            foreach ($this->walk($this->config($key)['fields'] ?? []) as $field) {
                if (($field['type'] ?? '') !== 'Collection') {
                    continue;
                }
                $table = (string)($field['table'] ?? '');
                self::assertNotSame('', $table, $key . ': Collection ' . $field['identifier'] . ' needs an explicit table');
                self::assertStringStartsWith('innesto_', $table, $key . ': Collection tables carry the extension prefix');
                self::assertArrayNotHasKey($table, $owners, sprintf(
                    '%s: Collection table %s is already used by %s',
                    $key,
                    $table,
                    $owners[$table] ?? '',
                ));
                $owners[$table] = $key;
            }
        }

        self::assertNotEmpty($owners);
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p4TheAppearancePaletteReachesTheSectionRoot(string $key): void
    {
        $config = $this->config($key);
        self::assertContains(
            'TYPO3/Appearance',
            (array)($config['basics'] ?? []),
            'every element offers the editor the Appearance palette',
        );

        $template = $this->read($key, 'templates/frontend.html');
        foreach (['frame_class', 'space_before_class', 'space_after_class'] as $column) {
            self::assertStringContainsString(
                '{data.' . $column . '}',
                $template,
                'the editor choice must reach d:layout.section, otherwise the palette does nothing',
            );
        }
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p5StylingStaysOnSemanticTokens(string $key): void
    {
        $assets = glob(self::ELEMENTS . '/' . $key . '/assets/*.{css,js}', GLOB_BRACE);
        self::assertIsArray($assets);

        $violations = [];
        foreach ([...$assets, self::ELEMENTS . '/' . $key . '/templates/frontend.html'] as $file) {
            foreach (preg_split('/\R/', (string)file_get_contents($file)) ?: [] as $index => $line) {
                if (preg_match(self::RAW_COLOUR, $line) === 1) {
                    $violations[] = basename($file) . ':' . ($index + 1) . ' ' . trim($line);
                }
            }
        }
        self::assertSame([], $violations, 'use var(--primary), var(--muted), … instead of colour literals');

        $template = $this->read($key, 'templates/frontend.html');
        self::assertDoesNotMatchRegularExpression('/<script\b/', $template, 'behaviour belongs in assets/ + f:asset.script');
        self::assertStringContainsString('frontend.css', $template, 'the element must include its own stylesheet');
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p6EveryConfiguredFieldIsRendered(string $key): void
    {
        $template = $this->read($key, 'templates/frontend.html');

        $unrendered = [];
        foreach ($this->walk($this->config($key)['fields'] ?? []) as $field) {
            $identifier = (string)$field['identifier'];
            if (($field['type'] ?? '') === 'File' || ($field['useExistingField'] ?? false) === true) {
                continue; // FAL references and core fields render through their own helpers.
            }
            if (preg_match('/[.\']' . preg_quote($identifier, '/') . '\b/', $template) !== 1) {
                $unrendered[] = $identifier;
            }
        }

        self::assertSame([], $unrendered, 'an editor field nothing renders is either dead or a forgotten graft');
    }

    #[Test]
    #[DataProvider('elementKeys')]
    public function p7TheDemoRecordMatchesTheConfiguredFields(string $key): void
    {
        $fixture = json_decode($this->read($key, 'library.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($fixture);
        self::assertFalse($fixture !== [] && array_is_list($fixture), 'library.json is an object keyed by field identifier');

        $this->assertFixtureMatchesFields($fixture, (array)($this->config($key)['fields'] ?? []), $key);
    }

    /**
     * @param array<string, mixed> $fixture
     * @param array<mixed> $fields
     */
    private function assertFixtureMatchesFields(array $fixture, array $fields, string $path): void
    {
        $byIdentifier = [];
        foreach ($fields as $field) {
            if (is_array($field) && isset($field['identifier'])) {
                $byIdentifier[(string)$field['identifier']] = $field;
            }
        }

        foreach ($fixture as $identifier => $value) {
            $field = $byIdentifier[$identifier] ?? null;
            self::assertNotNull($field, $path . ': demo value "' . $identifier . '" has no field in config.yaml');

            if (($field['type'] ?? '') !== 'Collection') {
                self::assertIsScalar($value, $path . '.' . $identifier . ': demo values are scalar');
                continue;
            }

            self::assertIsArray($value, $path . '.' . $identifier . ': a Collection takes a list of children');
            self::assertTrue(array_is_list($value), $path . '.' . $identifier . ': a Collection takes a list of children');
            foreach ($value as $index => $child) {
                self::assertIsArray($child, $path . '.' . $identifier . '[' . $index . ']: children are objects');
                $this->assertFixtureMatchesFields(
                    $child,
                    (array)($field['fields'] ?? []),
                    $path . '.' . $identifier . '[' . $index . ']',
                );
            }
        }
    }

    /**
     * Fields of an element, Collection children included.
     *
     * @param array<mixed> $fields
     * @return list<array<string, mixed>>
     */
    private function walk(array $fields): array
    {
        $flat = [];
        foreach ($fields as $field) {
            if (!is_array($field) || !isset($field['identifier'])) {
                continue;
            }
            self::assertNotSame('label', $field['identifier'], '"label" is reserved by Content Blocks — use "title"');
            $flat[] = $field;
            if (($field['type'] ?? '') === 'Collection') {
                $flat = [...$flat, ...$this->walk((array)($field['fields'] ?? []))];
            }
        }

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $key): array
    {
        $config = Yaml::parseFile(self::ELEMENTS . '/' . $key . '/config.yaml');
        self::assertIsArray($config);

        return $config;
    }

    private function read(string $key, string $file): string
    {
        $contents = file_get_contents(self::ELEMENTS . '/' . $key . '/' . $file);
        self::assertIsString($contents);

        return $contents;
    }

    /**
     * @return list<string>
     */
    private static function registeredWizardGroups(): array
    {
        $source = (string)file_get_contents(self::ROOT . '/Configuration/TCA/Overrides/tt_content.php');
        preg_match_all("/^\s*'([a-z0-9-]+)' => 'LLL:/m", $source, $matches);

        return $matches[1];
    }

    /**
     * @return list<string>
     */
    private static function siteSetBlocks(): array
    {
        $set = Yaml::parseFile(self::ROOT . '/Configuration/Sets/Innesto/config.yaml');
        self::assertIsArray($set);

        return array_values(array_map(strval(...), (array)($set['optionalDependencies'] ?? [])));
    }
}
