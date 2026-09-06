<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Tests\Functional;

use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use Webconsulting\Innesto\Command\SeedDemoRecordsCommand;
use Webconsulting\Innesto\Tests\Fixtures\RejectDemoWrite;

final class SeedDemoRecordsTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form', 'workspaces'];
    protected array $testExtensionsToLoad = [
        'friendsoftypo3/content-blocks',
        'praetorius/vite-asset-collector',
        'friendsoftypo3/visual-editor',
        'webconsulting/visual-editor-enhancements',
        'webconsulting/desiderio',
        'webconsulting/innesto',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->getConnectionPool()->getConnectionForTable('pages')->insert('pages', [
            'uid' => 1, 'pid' => 0, 'title' => 'Innesto test page', 'slug' => '/', 'doktype' => 1, 'is_siteroot' => 1,
        ]);
        $this->getConnectionPool()->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 1, 'pid' => 0, 'username' => 'innesto-test-editor', 'admin' => 1,
        ]);
        $this->setUpBackendUser(1);
    }

    public function testSeedsAllElementsFromLibraryFixturesAndIsIdempotent(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(0, $tester->execute(['page' => '1']), $tester->getDisplay());
        self::assertCount(19, $this->records());

        $terminal = $this->getConnectionPool()->getConnectionForTable('innesto_terminal_lines')
            ->select(['text'], 'innesto_terminal_lines', ['deleted' => 0])->fetchFirstColumn();
        self::assertContains('npm install @signalbureau/browser', $terminal, json_encode($terminal, JSON_THROW_ON_ERROR));
        $point = $this->getConnectionPool()->getConnectionForTable('innesto_stats_area_chart_points')
            ->select(['value'], 'innesto_stats_area_chart_points', ['deleted' => 0], [], ['uid' => 'ASC'])->fetchOne();
        self::assertSame(168.4, (float)$point, 'Decimal chart values must survive DataHandler.');

        $records = $this->records();
        self::assertSame(0, $tester->execute(['page' => '1']), $tester->getDisplay());
        self::assertSame($records, $this->records(), 'A second run must not write duplicate records.');
    }

    public function testForceReplacesLastElementAndPreservesOtherContentAndRelations(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->insert('tt_content', [
            'uid' => 100, 'pid' => 1, 'CType' => 'header', 'header' => 'Existing content', 'sorting' => 256,
        ]);
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(0, $tester->execute(['page' => '1', '--element' => ['terminal']]), $tester->getDisplay());
        $before = $this->records();
        self::assertSame('Existing content', $before[0]['header']);
        self::assertSame(0, $tester->execute(['page' => '1', '--element' => ['terminal'], '--force' => true]), $tester->getDisplay());
        $after = $this->records();
        self::assertCount(2, $after);
        self::assertSame($before[0], $after[0]);
        self::assertNotSame($before[1]['uid'], $after[1]['uid']);
        self::assertSame(8, $this->getConnectionPool()->getConnectionForTable('innesto_terminal_lines')
            ->count('*', 'innesto_terminal_lines', ['deleted' => 0]));
    }

    public function testUnknownElementFailsWithoutChangingThePage(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(1, $tester->execute(['page' => '1', '--element' => ['does-not-exist']]));
        self::assertSame([], $this->records());
    }

    public function testFailedReplacementRollsBackNewRecordsAndChildren(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(0, $tester->execute(['page' => '1', '--element' => ['terminal']]));
        $before = $this->records();
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['innesto-test-reject'] = RejectDemoWrite::class;
        try {
            self::assertSame(1, $tester->execute(['page' => '1', '--element' => ['terminal'], '--force' => true]));
            self::assertStringContainsString('Demo write rejected', $tester->getDisplay());
            self::assertSame($before, $this->records());
            $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
            self::assertSame(1, (int)$connection->executeQuery('SELECT COUNT(*) FROM tt_content')->fetchOne());
            self::assertSame(8, (int)$connection->executeQuery('SELECT COUNT(*) FROM innesto_terminal_lines')->fetchOne());
        } finally {
            unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['innesto-test-reject']);
        }
    }

    public function testHiddenDemoRecordsAreNotDuplicated(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(0, $tester->execute(['page' => '1', '--element' => ['terminal']]));
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->update('tt_content', ['hidden' => 1], ['pid' => 1]);
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['hidden' => 1], ['uid' => 1]);
        self::assertSame(0, $tester->execute(['page' => '1', '--element' => ['terminal']]));
        self::assertCount(1, $this->records());
        self::assertSame(1, (int)$connection->executeQuery('SELECT hidden FROM tt_content WHERE pid = 1 AND deleted = 0')->fetchOne());
        self::assertStringNotContainsString('non-Innesto', $tester->getDisplay());
    }

    public function testInvalidPageArgumentDoesNotAccidentallySeedAnExistingPage(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(1, $tester->execute(['page' => '1oops']));
        self::assertSame([], $this->records());
    }

    public function testAllShippedElementsRenderThroughTheFrontend(): void
    {
        $tester = new CommandTester($this->get(SeedDemoRecordsCommand::class));
        self::assertSame(0, $tester->execute(['page' => '1']), $tester->getDisplay());
        $this->get(SiteWriter::class)->write('innesto', [
            'rootPageId' => 1,
            'base' => 'https://innesto.example/',
            'dependencies' => ['webconsulting/innesto'],
            'languages' => [[
                'title' => 'English', 'enabled' => true, 'languageId' => 0,
                'base' => '/', 'locale' => 'en_US.UTF-8',
            ]],
        ]);
        // Render Innesto content through TYPO3 without the consuming site's Vite page shell.
        file_put_contents($this->getInstancePath() . '/typo3conf/sites/innesto/setup.typoscript', <<<'TS'
page >
page = PAGE
page.10 = CONTENT
page.10.table = tt_content
page.10.select.orderBy = sorting
page.10.select.where = {#colPos}=0
TS);
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://innesto.example/'));
        $html = (string)$response->getBody();
        self::assertSame(200, $response->getStatusCode(), substr(strip_tags($html), 0, 3000));
        foreach (glob(dirname(__DIR__, 2) . '/ContentBlocks/ContentElements/*/library.json') as $fixturePath) {
            $fixture = json_decode(file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);
            self::assertStringContainsString(htmlspecialchars($fixture['header']), $html, basename(dirname($fixturePath)));
        }
        preg_match_all('/data-values="([^"]*)"/', $html, $charts);
        self::assertCount(3, $charts[1]);
        self::assertSame(168.4, json_decode($charts[1][0], true, 512, JSON_THROW_ON_ERROR)[0]);
        self::assertStringNotContainsString('Oops, an error occurred', $html);
    }

    private function records(): array
    {
        return $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->executeQuery('SELECT uid, header, sorting, CType FROM tt_content WHERE pid = 1 AND deleted = 0 ORDER BY sorting')
            ->fetchAllAssociative();
    }
}
