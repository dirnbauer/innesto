<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Functional\Templates;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Webconsulting\Desiderio\Templates\LintFinding;
use Webconsulting\Desiderio\Templates\LintOptions;
use Webconsulting\Desiderio\Templates\TemplateLinter;

/**
 * Desiderio 4.1 ships a Fluid 5 lint gate; Innesto grafts onto Desiderio, so
 * it is held to the same bar. The linter parses every shipped template with
 * the rendering context TYPO3 uses at runtime and reports what the parser
 * would throw — unknown ViewHelpers, unknown or missing arguments, undeclared
 * namespaces, unknown components — plus unresolvable partials and removed
 * constructs.
 *
 * That covers what a static test cannot: a Desiderio component gaining a
 * required argument, or an argument being renamed, breaks these templates and
 * this test says so before an editor sees a white page.
 */
final class ShippedTemplatesLintTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['fluid_styled_content', 'form', 'workspaces'];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/content-blocks',
        'praetorius/vite-asset-collector',
        'friendsoftypo3/visual-editor',
        'webconsulting/visual-editor-enhancements',
        'webconsulting/desiderio',
        'webconsulting/innesto',
    ];

    #[Test]
    public function everyShippedTemplateParsesWithoutErrors(): void
    {
        $report = $this->get(TemplateLinter::class)->lint(new LintOptions(['EXT:innesto']));

        self::assertGreaterThanOrEqual(
            38,
            $report->getFilesScanned(),
            'Both templates of all 19 content elements must be scanned',
        );
        self::assertSame([], array_map(
            static fn(LintFinding $finding): string => sprintf(
                '%s:%s [%s] %s',
                $finding->file,
                $finding->line ?? '-',
                $finding->rule,
                $finding->message,
            ),
            $report->getErrors(),
        ));
    }

    #[Test]
    public function nothingIsLeftUnchecked(): void
    {
        $report = $this->get(TemplateLinter::class)->lint(new LintOptions(['EXT:innesto']));

        self::assertSame([], array_map(
            static fn(LintFinding $finding): string => $finding->file . ': ' . $finding->message,
            $report->getSkipped(),
        ), 'Innesto templates only use f:, cb: and d: — every namespace is installed, so nothing may be skipped');
    }
}
