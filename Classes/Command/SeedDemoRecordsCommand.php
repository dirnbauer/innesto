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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/** Seeds library.json values through DataHandler, including nested collections. */
#[AsCommand(name: 'innesto:seed', description: 'Create one demo record per Innesto content element on a page')]
final class SeedDemoRecordsCommand extends Command
{
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
            ->addOption('element', 'e', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only seed the given element key(s)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Replace existing records of the selected CTypes on the page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pageUid = filter_var($input->getArgument('page'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($pageUid === false) {
            $io->error('Page must be a positive integer uid.');
            return Command::FAILURE;
        }
        $filter = $input->getOption('element');
        $configFiles = glob(ExtensionManagementUtility::extPath('innesto') . 'ContentBlocks/ContentElements/*/config.yaml') ?: [];
        $unknown = array_diff($filter, array_map(static fn(string $path): string => basename(dirname($path)), $configFiles));
        if ($unknown !== []) {
            $io->error('Unknown element(s): ' . implode(', ', $unknown));
            return Command::FAILURE;
        }

        Bootstrap::initializeBackendAuthentication();
        $GLOBALS['LANG'] = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
        $pageQuery = $this->connectionPool->getQueryBuilderForTable('pages');
        $pageQuery->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $page = $pageQuery->select('title')->from('pages')
            ->where($pageQuery->expr()->eq('uid', $pageQuery->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->executeQuery()->fetchAssociative();
        if ($page === false) {
            $io->error(sprintf('Page %d does not exist.', $pageUid));
            return Command::FAILURE;
        }

        $records = $this->pageRecords($pageUid);
        $foreignContent = count(array_filter($records, static fn(array $record): bool => !str_starts_with($record['CType'], 'innesto_')));
        if ($foreignContent > 0) {
            $io->warning(sprintf('Page %d contains %d non-Innesto element(s). Demo records will be appended; use a dedicated demo page.', $pageUid, $foreignContent));
        }

        $skipped = 0;
        $deletions = $elements = [];
        foreach ($configFiles as $configFile) {
            $elementKey = basename(dirname($configFile));
            if ($filter !== [] && !in_array($elementKey, $filter, true)) {
                continue;
            }
            $config = Yaml::parseFile($configFile);
            $existing = array_keys(array_filter($records, static fn(array $record): bool => $record['CType'] === $config['typeName']));
            if ($existing !== [] && !$input->getOption('force')) {
                $io->text(sprintf('Skipped %s — uid %s exists (use --force to reseed)', $elementKey, implode(',', $existing)));
                $skipped++;
                continue;
            }
            $deletions += array_fill_keys($existing, ['delete' => 1]);
            $elements[] = ['config' => $config, 'fixture' => $this->loadFixture(dirname($configFile))];
            $io->text('Queued ' . $elementKey);
        }
        if ($elements === []) {
            $io->success(sprintf('Nothing to do on page %d ("%s") — %d element(s) skipped.', $pageUid, $page['title'], $skipped));
            return Command::SUCCESS;
        }

        // A positive pid inserts at the top; a negative uid/NEW id inserts after
        // that record. Never use a record being replaced as the sorting anchor.
        $target = $pageUid;
        foreach ($records as $uid => $record) {
            if ((int)$record['colPos'] === 0 && !isset($deletions[$uid])) {
                $target = '-' . $uid;
                break;
            }
        }
        $dataMap = [];
        foreach ($elements as ['config' => $config, 'fixture' => $fixture]) {
            $newId = StringUtility::getUniqueId('NEW');
            $dataMap['tt_content'][$newId] = [
                'pid' => $target,
                'CType' => $config['typeName'],
                'colPos' => 0,
                'header' => $config['title'],
                ...$this->buildFields($config['fields'], $fixture, $pageUid, $dataMap, $config['typeName']),
            ];
            $target = '-' . $newId;
        }

        try {
            $this->connectionPool->getConnectionForTable('tt_content')->transactional(static function () use ($dataMap, $deletions): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, ['tt_content' => $deletions]);
                $dataHandler->process_datamap();
                if ($dataHandler->errorLog === []) {
                    $dataHandler->process_cmdmap();
                }
                if ($dataHandler->errorLog !== []) {
                    throw new \RuntimeException('DataHandler reported errors: ' . implode('; ', $dataHandler->errorLog));
                }
            });
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
        $io->success(sprintf(
            'Seeded %d demo record(s) on page %d ("%s"); skipped %d, replaced %d.',
            count($elements),
            $pageUid,
            $page['title'],
            $skipped,
            count($deletions)
        ));
        return Command::SUCCESS;
    }

    /** @return array<int, array{CType: string, colPos: int|string}> */
    private function pageRecords(int $pageUid): array
    {
        $query = $this->connectionPool->getQueryBuilderForTable('tt_content');
        // Include hidden and scheduled records in both duplicate detection and sorting.
        $query->getRestrictions()->removeAll()->add(new DeletedRestriction());
        /** @var array<int, array{CType: string, colPos: int|string}> $records */
        $records = $query->select('uid', 'CType', 'colPos')->from('tt_content')
            ->where($query->expr()->eq('pid', $query->createNamedParameter($pageUid, Connection::PARAM_INT)))
            ->orderBy('sorting', 'DESC')->addOrderBy('uid', 'DESC')
            ->executeQuery()->fetchAllAssociativeIndexed();
        return $records;
    }

    /** @return array<string, mixed> */
    private function loadFixture(string $directory): array
    {
        $path = $directory . (is_file($directory . '/fixture.json') ? '/fixture.json' : '/library.json');
        $fixture = is_file($path) ? json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
        if (!is_array($fixture) || ($fixture !== [] && array_is_list($fixture))) {
            throw new \UnexpectedValueException('Demo fixture must be a JSON object: ' . $path);
        }
        return $fixture;
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @param array<string, mixed> $fixture
     * @param array<array<string, array<string, mixed>>> $dataMap Table names mapped to NEW record IDs and fields.
     * @return array<string, string|int|float>
     */
    private function buildFields(array $fields, array $fixture, int $pageUid, array &$dataMap, string $prefix = ''): array
    {
        $values = [];
        foreach ($fields as $field) {
            $identifier = $field['identifier'];
            $value = $fixture[$identifier] ?? null;
            if ($value === null || ($field['type'] ?? '') === 'File') {
                continue; // DataHandler supplies defaults; File fields need editor-managed FAL references.
            }
            if (($field['type'] ?? '') === 'Collection') {
                if (!is_array($value) || !array_is_list($value)) {
                    throw new \UnexpectedValueException('Expected a collection of demo values for ' . $identifier);
                }
                $childIds = [];
                foreach ($value as $item) {
                    if (!is_array($item)) {
                        throw new \UnexpectedValueException('Collection demo entries must be objects.');
                    }
                    $childId = StringUtility::getUniqueId('NEW');
                    $dataMap[$field['table']][$childId] = ['pid' => $pageUid, ...$this->buildFields($field['fields'], $item, $pageUid, $dataMap)];
                    $childIds[] = $childId;
                }
                $value = implode(',', $childIds);
            }
            if (!is_scalar($value)) {
                throw new \UnexpectedValueException('Expected a scalar demo value for ' . $identifier);
            }
            $column = $prefix !== '' && !empty($field['prefixField']) ? $prefix . '_' . $identifier : $identifier;
            $values[$column] = is_bool($value) ? (int)$value : $value;
        }
        return $values;
    }
}
