<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The graft contract, mirroring Desiderio's atomic-design conformance test
 * for the elements Innesto adds on top of it:
 *
 *  I1 every content element composes at least one d: component inside a
 *     d:layout.section root and renders no partials
 *  I2 every d: component a template references exists in the Desiderio
 *     component collection that is actually installed
 *  I3 organisms stay out — Innesto ships content elements, not page
 *     templates, and Desiderio's A2 rule reserves organisms for those
 *  I4 the Desiderio namespace is declared with its canonical URI wherever
 *     a d: tag is used
 *
 * Parsing is not asserted here but in
 * Tests/Functional/Templates/ShippedTemplatesLintTest, which runs
 * Desiderio's Fluid 5 linter over the same files with the runtime
 * rendering context.
 */
final class ContentElementConformanceTest extends TestCase
{
    private const string ROOT = __DIR__ . '/../..';

    private const string DESIDERIO_COMPONENTS = self::ROOT . '/vendor/webconsulting/desiderio/Resources/Private/Components';

    private const string NAMESPACE_URI = 'http://typo3.org/ns/Webconsulting/Desiderio/Components/ComponentCollection';

    #[Test]
    public function i1EveryElementComposesComponentsInsideASectionRoot(): void
    {
        $violations = [];
        foreach ($this->frontendTemplates() as $path => $source) {
            if (preg_match('/<d:[a-z]+\.[a-zA-Z]+/', $source) !== 1) {
                $violations[] = $path . ' [I1] uses no d: component';
            }
            if (!str_contains($source, '<d:layout.section')) {
                $violations[] = $path . ' [I1] has no d:layout.section root';
            }
            if (preg_match('/f:render\b[^>]*\bpartial\s*[=:]/', $source) === 1) {
                $violations[] = $path . ' [I1] renders a partial; compose components instead';
            }
        }
        self::assertSame([], $violations);
    }

    #[Test]
    public function i2EveryReferencedComponentExistsInTheInstalledCollection(): void
    {
        self::assertDirectoryExists(
            self::DESIDERIO_COMPONENTS,
            'Desiderio 4.1+ keeps its component collection in Resources/Private/Components',
        );

        $violations = [];
        foreach ($this->allTemplates() as $path => $source) {
            foreach ($this->referencedComponents($source) as $component) {
                [$layer, $name] = $component;
                $file = sprintf('%s/%s/%s/%s.fluid.html', self::DESIDERIO_COMPONENTS, $layer, $name, $name);
                if (!is_file($file)) {
                    $violations[] = sprintf('%s [I2] d:%s.%s does not exist in Desiderio', $path, lcfirst($layer), lcfirst($name));
                }
            }
        }
        self::assertSame([], $violations);
    }

    #[Test]
    public function i3NoOrganismsAreComposed(): void
    {
        $violations = [];
        foreach ($this->allTemplates() as $path => $source) {
            if (str_contains($source, '<d:organism.') || str_contains($source, '{d:organism.')) {
                $violations[] = $path . ' [I3] composes an organism; those belong to page templates';
            }
        }
        self::assertSame([], $violations);
    }

    #[Test]
    public function i4TheDesiderioNamespaceIsDeclaredCanonically(): void
    {
        $violations = [];
        foreach ($this->allTemplates() as $path => $source) {
            $usesComponents = preg_match('/<d:[a-z]+\.[a-zA-Z]+/', $source) === 1;
            $declares = str_contains($source, 'xmlns:d="' . self::NAMESPACE_URI . '"');
            if ($usesComponents && !$declares) {
                $violations[] = $path . ' [I4] uses d: without declaring the Desiderio namespace';
            }
            if (!$usesComponents && $declares) {
                $violations[] = $path . ' [I4] declares the Desiderio namespace without using it';
            }
        }
        self::assertSame([], $violations);
    }

    /**
     * @return list<array{string, string}> layer and component directory name
     */
    private function referencedComponents(string $source): array
    {
        if (preg_match_all('/<d:([a-z]+)\.([a-zA-Z]+)/', $source, $matches, PREG_SET_ORDER) < 1) {
            return [];
        }

        $components = [];
        foreach ($matches as $match) {
            $components[$match[1] . '.' . $match[2]] = [ucfirst($match[1]), ucfirst($match[2])];
        }

        return array_values($components);
    }

    /**
     * Every shipped content element's frontend template, keyed by relative path.
     *
     * @return array<string, string>
     */
    private function frontendTemplates(): array
    {
        $files = glob(self::ROOT . '/ContentBlocks/ContentElements/*/templates/frontend.html');
        self::assertIsArray($files);
        self::assertGreaterThanOrEqual(19, count($files), 'Every shipped element must have a frontend template');

        $templates = [];
        foreach ($files as $file) {
            $templates[$this->relative($file)] = $this->stripComments((string)file_get_contents($file));
        }
        ksort($templates);

        return $templates;
    }

    /**
     * Frontend and backend-preview templates of every element.
     *
     * @return array<string, string>
     */
    private function allTemplates(): array
    {
        $files = glob(self::ROOT . '/ContentBlocks/ContentElements/*/templates/*.html');
        self::assertIsArray($files);

        $templates = [];
        foreach ($files as $file) {
            $templates[$this->relative($file)] = $this->stripComments((string)file_get_contents($file));
        }
        ksort($templates);

        return $templates;
    }

    private function relative(string $absolute): string
    {
        $root = realpath(self::ROOT);
        self::assertIsString($root);

        return ltrim(str_replace($root, '', (string)realpath($absolute)), '/');
    }

    private function stripComments(string $source): string
    {
        return (string)preg_replace('/<f:comment>.*?<\/f:comment>|<!--.*?-->/s', '', $source);
    }
}
