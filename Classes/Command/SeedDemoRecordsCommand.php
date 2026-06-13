<?php

declare(strict_types=1);

namespace Webconsulting\Innesto\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Seeds one demo record per Innesto content element onto a page, so every
 * graft can be inspected in the page module and on the frontend right after
 * `extension:setup`. Values come from an element's fixture.json when present
 * (same flat identifier => value format Desiderio uses), otherwise they are
 * derived from config.yaml. Records are created through DataHandler, so
 * inline children, counter columns, and the reference index stay consistent.
 */
#[AsCommand(
    name: 'innesto:seed',
    description: 'Create one demo record per Innesto content element on a page'
)]
final class SeedDemoRecordsCommand extends Command
{
    /**
     * Identifier-keyed demo values for Textarea fields; generic fallback is
     * a humanized identifier. Kept in sync with the shipped elements.
     */
    private const TEXT_SAMPLES = [
        'eyebrow' => 'Pattern Library',
        'value' => '1,234',
        'current_value' => '450 GB',
        'limit_value' => '1 TB',
        'delta' => '+8.3%',
        'change' => '+8.3%',
        'difference' => '+1,204',
        'percentage' => '+12%',
        'percentage_change' => '+4.1%',
        'previous' => 'from 1,108',
        'badge_text' => '+12.5%',
        'quote' => 'Innesto turned a registry component into an editor-managed element in minutes.',
        'author_name' => 'Jane Demo',
        'author_role' => 'CTO',
        'detail' => '3/5 goals',
        'limit' => '996 of 10,000',
        'link_text' => 'View more',
        'target' => '/ 150 GB',
        'subtext' => 'On track',
        'current' => '$250',
        'total' => '$1,000',
        'amount' => '4.2 GB',
        'summary_text' => 'Using storage',
        'used_value' => '8,300 MB',
        'total_text' => 'of 15 GB',
        'free_title' => 'Free',
        'free_amount' => '6.7 GB',
        'period_label' => 'Last 30 days',
        'updated_label' => 'Updated just now',
        'cta_text' => 'Upgrade',
        'intro' => 'You are currently on the demo plan.',
        'ticker' => 'ACME',
        'card_title' => 'Usage',
        'total_value' => '$860',
        'total_caption' => 'this month',
        'breakdown_title' => 'Resource breakdown',
        'footnote' => 'Configure limits in resource settings.',
        'footnote_link_text' => 'Settings',
        'title' => 'Demo metric',
    ];

    private const NUMBER_SAMPLES = [72, 48, 85, 64, 31];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $dataMap = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('page', InputArgument::REQUIRED, 'Target page uid for the demo records')
            ->addOption(
                'element',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Only seed the given element key(s), e.g. -e stats-trending -e case-studies'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Delete existing records of an element\'s CType on the page and reseed them'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pageUid = (int)$input->getArgument('page');
        $filter = (array)$input->getOption('element');

        Bootstrap::initializeBackendAuthentication();
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);

        $page = $this->connectionPool->getConnectionForTable('pages')
            ->select(['uid', 'title'], 'pages', ['uid' => $pageUid, 'deleted' => 0])->fetchAssociative();
        if ($page === false) {
            $io->error(sprintf('Page %d does not exist.', $pageUid));
            return Command::FAILURE;
        }

        $foreignContent = $this->countForeignContent($pageUid);
        if ($foreignContent > 0) {
            $io->warning(sprintf(
                'Page %d already holds %d non-Innesto content element(s). Demo records are appended below them, '
                . 'but a dedicated sysfolder keeps your real content clean.',
                $pageUid,
                $foreignContent
            ));
        }

        $elementsDirectory = ExtensionManagementUtility::extPath('innesto') . 'ContentBlocks/ContentElements';
        $created = $skipped = $deleted = 0;
        // Append demo records after the page's existing content so they never
        // jump above real elements; chaining each new record after the previous
        // one keeps DataHandler from reusing (colliding) sorting values.
        $predecessor = $this->lastElementUid($pageUid);

        foreach (glob($elementsDirectory . '/*/config.yaml') ?: [] as $configFile) {
            $elementKey = basename(dirname($configFile));
            if ($filter !== [] && !in_array($elementKey, $filter, true)) {
                continue;
            }
            $config = Yaml::parseFile($configFile);
            $typeName = (string)$config['typeName'];

            $existing = $this->findExisting($pageUid, $typeName);
            if ($existing !== []) {
                if (!$input->getOption('force')) {
                    $io->text(sprintf(' · %-28s skipped — uid %s exists (use --force to reseed)', $elementKey, implode(',', $existing)));
                    $skipped++;
                    continue;
                }
                $this->deleteRecords($existing);
                $deleted += count($existing);
            }

            $fixtureFile = dirname($configFile) . '/fixture.json';
            $fixture = is_file($fixtureFile)
                ? (array)json_decode((string)file_get_contents($fixtureFile), true)
                : [];

            $newId = $this->addRecord($config, $fixture, $pageUid, $typeName, $predecessor);
            $predecessor = $newId;
            $io->text(sprintf(' · %-28s queued (%s)', $elementKey, $newId));
            $created++;
        }

        if ($this->dataMap === []) {
            $io->success(sprintf('Nothing to do on page %d ("%s") — %d element(s) skipped.', $pageUid, $page['title'], $skipped));
            return Command::SUCCESS;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($this->dataMap, []);
        $dataHandler->process_datamap();

        if ($dataHandler->errorLog !== []) {
            $io->error(array_merge(['DataHandler reported errors:'], $dataHandler->errorLog));
            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Seeded %d demo record(s) on page %d ("%s")%s%s. Flush frontend caches to see them.',
            $created,
            $pageUid,
            $page['title'],
            $skipped > 0 ? sprintf(', skipped %d existing', $skipped) : '',
            $deleted > 0 ? sprintf(', replaced %d', $deleted) : ''
        ));
        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $fixture
     * @param int|string $predecessor real uid or NEW id to position after; 0 = top of page
     */
    private function addRecord(array $config, array $fixture, int $pageUid, string $typeName, int|string $predecessor = 0): string
    {
        $newId = StringUtility::getUniqueId('NEW');
        // DataHandler positioning: positive pid = top of page; negative target
        // (real uid or NEW id) = insert immediately after that record.
        $target = $predecessor === 0 ? $pageUid : '-' . $predecessor;
        $record = [
            'pid' => $target,
            'CType' => $typeName,
            'colPos' => 0,
            'header' => (string)($fixture['header'] ?? $config['title']),
        ];
        foreach ((array)$config['fields'] as $field) {
            $identifier = (string)$field['identifier'];
            if (!empty($field['useExistingField']) && $identifier === 'header') {
                continue;
            }
            $column = !empty($field['prefixField']) ? $typeName . '_' . $identifier : $identifier;
            $value = $this->buildFieldValue($field, $fixture[$identifier] ?? null, $pageUid, 0);
            if ($value !== null) {
                $record[$column] = $value;
            }
        }
        $this->dataMap['tt_content'][$newId] = $record;
        return $newId;
    }

    /**
     * Returns the datamap value for one field: a scalar for plain fields, a
     * comma list of NEW ids for Collections (children are appended to the
     * datamap recursively), or null when the field cannot be seeded (File).
     *
     * @param array<string, mixed> $field
     */
    private function buildFieldValue(array $field, mixed $fixtureValue, int $pageUid, int $index): string|int|null
    {
        $type = (string)($field['type'] ?? '');
        switch ($type) {
            case 'Collection':
                $table = (string)$field['table'];
                $items = is_array($fixtureValue) ? array_values($fixtureValue) : null;
                $childFields = (array)$field['fields'];
                // A collection holding a single Number is a data series (chart
                // points) — two rows would draw a straight line.
                $isDataSeries = count($childFields) === 1 && ($childFields[0]['type'] ?? '') === 'Number';
                $count = $items !== null ? count($items) : max($isDataSeries ? 6 : 2, (int)($field['minItems'] ?? 0));
                $childIds = [];
                for ($i = 0; $i < $count; $i++) {
                    $childId = StringUtility::getUniqueId('NEW');
                    $child = ['pid' => $pageUid];
                    foreach ((array)$field['fields'] as $childField) {
                        $childIdentifier = (string)$childField['identifier'];
                        $childFixture = $items[$i][$childIdentifier] ?? null;
                        $value = $this->buildFieldValue($childField, $childFixture, $pageUid, $i);
                        if ($value !== null) {
                            $child[$childIdentifier] = $value;
                        }
                    }
                    $this->dataMap[$table][$childId] = $child;
                    $childIds[] = $childId;
                }
                return implode(',', $childIds);
            case 'File':
                // Needs sys_file plumbing — provide images manually or via fixture-driven FAL later.
                return null;
            case 'Number':
                return is_numeric($fixtureValue)
                    ? (int)$fixtureValue
                    : self::NUMBER_SAMPLES[$index % count(self::NUMBER_SAMPLES)];
            case 'Select':
                return is_string($fixtureValue) && $fixtureValue !== ''
                    ? $fixtureValue
                    : (string)($field['default'] ?? ($field['items'][0]['value'] ?? ''));
            case 'Checkbox':
                return is_numeric($fixtureValue) ? (int)$fixtureValue : (int)($field['default'] ?? 0);
            case 'Link':
                return is_string($fixtureValue) && $fixtureValue !== '' ? $fixtureValue : 'https://example.com/';
            default:
                if (is_scalar($fixtureValue) && (string)$fixtureValue !== '') {
                    return (string)$fixtureValue;
                }
                $identifier = (string)$field['identifier'];
                $sample = self::TEXT_SAMPLES[$identifier]
                    ?? ucfirst(str_replace('_', ' ', $identifier));
                // Vary repeated collection rows so lists do not look cloned.
                return $index > 0 && isset(self::TEXT_SAMPLES[$identifier]) && $identifier === 'title'
                    ? $sample . ' ' . ($index + 1)
                    : $sample;
        }
    }

    /**
     * @return list<int>
     */
    private function findExisting(int $pageUid, string $typeName): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $rows = $queryBuilder->select('uid')->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter($typeName))
            )
            ->executeQuery()->fetchFirstColumn();
        return array_map(intval(...), $rows);
    }

    /**
     * Highest-sorting live content element in colPos 0 on the page, or 0 when
     * the page is empty — the anchor demo records are appended after.
     */
    private function lastElementUid(int $pageUid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $uid = $queryBuilder->select('uid')->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('colPos', $queryBuilder->createNamedParameter(0, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->orderBy('sorting', 'DESC')->setMaxResults(1)
            ->executeQuery()->fetchOne();
        return (int)$uid;
    }

    /**
     * Number of live content elements on the page that are not Innesto demos —
     * used to warn before mixing demo records into a page's real content.
     */
    private function countForeignContent(int $pageUid): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        return (int)$queryBuilder->count('uid')->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                $queryBuilder->expr()->notLike('CType', $queryBuilder->createNamedParameter('innesto\_%'))
            )
            ->executeQuery()->fetchOne();
    }

    /**
     * @param list<int> $uids
     */
    private function deleteRecords(array $uids): void
    {
        $commandMap = ['tt_content' => array_fill_keys($uids, ['delete' => 1])];
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
    }
}
