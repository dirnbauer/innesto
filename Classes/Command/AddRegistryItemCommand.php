<?php

declare(strict_types=1);

namespace Dirnbauer\Innesto\Command;

use Dirnbauer\Innesto\Registry\ElementScaffolder;
use Dirnbauer\Innesto\Registry\RegistryClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

#[AsCommand(
    name: 'innesto:add',
    description: 'Fetch a shadcn registry item and scaffold it as a Content Blocks element'
)]
final class AddRegistryItemCommand extends Command
{
    public function __construct(
        private readonly RegistryClient $registryClient,
        private readonly ElementScaffolder $scaffolder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'item',
                InputArgument::REQUIRED,
                'Registry item: full JSON URL or shorthand like "magicui/marquee" / "shadcn/button"'
            )
            ->addOption(
                'key',
                'k',
                InputOption::VALUE_REQUIRED,
                'Element key (folder name); defaults to the registry item name'
            )
            ->addOption(
                'target',
                't',
                InputOption::VALUE_REQUIRED,
                'Target ContentElements directory; defaults to EXT:innesto/ContentBlocks/ContentElements'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reference = (string)$input->getArgument('item');

        $item = $this->registryClient->fetchItem($reference);
        $io->section(sprintf('Fetched "%s" (%s)', $item['name'], $item['type'] ?? 'unknown type'));

        $elementKey = (string)($input->getOption('key') ?? $item['name']);
        $elementKey = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $elementKey) ?? $elementKey);

        $target = (string)($input->getOption('target')
            ?? ExtensionManagementUtility::extPath('innesto') . 'ContentBlocks/ContentElements');

        $written = $this->scaffolder->scaffold($item, $elementKey, $target);
        $io->listing(array_map(static fn(string $p): string => $elementKey . '/' . $p, $written));

        $dependencies = array_merge(
            (array)($item['dependencies'] ?? []),
            (array)($item['registryDependencies'] ?? [])
        );
        if ($dependencies !== []) {
            $io->warning('Upstream dependencies not fetched (resolve manually if needed): ' . implode(', ', $dependencies));
        }

        $io->success(sprintf('Element "innesto/%s" scaffolded.', $elementKey));
        $io->text([
            'Next steps:',
            '  1. Translate sources/*.tsx markup to templates/frontend.html (Fluid 5).',
            '  2. Model component props as fields in config.yaml.',
            '  3. Port styles in assets/frontend.css onto the Desiderio tokens.',
            '  4. vendor/bin/typo3 extension:setup && cache:flush',
        ]);
        return Command::SUCCESS;
    }
}
