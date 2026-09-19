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
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Webconsulting\Innesto\Registry\ElementScaffolder;
use Webconsulting\Innesto\Registry\RegistryClient;
use Webconsulting\Innesto\Registry\SetRegistrar;

#[AsCommand(
    name: 'innesto:add',
    description: 'Fetch a shadcn registry item and scaffold it as a Content Blocks element'
)]
final class AddRegistryItemCommand extends Command
{
    public function __construct(
        private readonly RegistryClient $registryClient,
        private readonly ElementScaffolder $scaffolder,
        private readonly SetRegistrar $setRegistrar,
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
            )
            ->addOption(
                'ai',
                null,
                InputOption::VALUE_NONE,
                'Run the AI finishing pass via the claude CLI (set INNESTO_CLAUDE_BIN to override the binary)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reference = (string)$input->getArgument('item');

        $item = $this->registryClient->fetchItem($reference);
        $io->section(sprintf('Fetched "%s" (%s)', $item['name'], $item['type'] ?? 'unknown type'));

        $elementKey = (string)($input->getOption('key') ?? $item['name']);
        $elementKey = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $elementKey) ?? $elementKey), '-');

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

        $this->registerInSiteSet($io, 'innesto/' . $elementKey, $input->getOption('target') === null);

        $elementDir = rtrim($target, '/') . '/' . $elementKey;
        if (!$input->getOption('ai')) {
            $io->text([
                'Next steps:',
                '  1. In ' . $elementDir . ': claude -p "$(cat ' . ElementScaffolder::PROMPT_FILE . ')" --permission-mode acceptEdits',
                '  2. Review the result, then from the project root: vendor/bin/typo3 extension:setup && vendor/bin/typo3 cache:flush',
            ]);
            return Command::SUCCESS;
        }

        return $this->runFinishingPass($io, $elementDir, (string)file_get_contents(
            $elementDir . '/' . ElementScaffolder::PROMPT_FILE
        ));
    }

    /**
     * A block that is not listed in a site set stays hidden in the New Content
     * Element wizard on every site that restricts content blocks per set —
     * which Desiderio does — so this is part of every graft, not an extra.
     */
    private function registerInSiteSet(SymfonyStyle $io, string $blockName, bool $ownTarget): void
    {
        $setConfigPath = ExtensionManagementUtility::extPath('innesto') . 'Configuration/Sets/Innesto/config.yaml';
        if ($ownTarget && $this->setRegistrar->register($setConfigPath, $blockName)) {
            $io->text(sprintf('Registered "%s" in the Innesto site set (Configuration/Sets/Innesto/config.yaml).', $blockName));
            return;
        }

        $io->warning(sprintf(
            'Add "%s" to the optionalDependencies of %s, otherwise the element stays hidden in the New Content Element wizard.',
            $blockName,
            $ownTarget ? 'Configuration/Sets/Innesto/config.yaml' : 'your own site set',
        ));
    }

    private function runFinishingPass(SymfonyStyle $io, string $elementDir, string $prompt): int
    {
        $binary = getenv('INNESTO_CLAUDE_BIN') ?: (new ExecutableFinder())->find('claude');
        if ($binary === null) {
            $io->warning([
                'claude CLI not found in PATH (in ddev it usually lives on the host, not in the container).',
                'Run the pass manually from the element directory:',
                '  cd ' . $elementDir,
                '  claude -p "$(cat ' . ElementScaffolder::PROMPT_FILE . ')" --permission-mode acceptEdits',
            ]);
            return Command::FAILURE;
        }

        $io->section('Running AI finishing pass (claude CLI) — this can take a few minutes');
        $process = new Process(
            [$binary, '-p', $prompt, '--permission-mode', 'acceptEdits'],
            $elementDir,
            null,
            null,
            600.0
        );
        $process->run(static function (string $type, string $buffer) use ($io): void {
            $io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $io->error('Finishing pass failed (exit ' . $process->getExitCode() . '). The scaffold is intact; rerun manually with ' . ElementScaffolder::PROMPT_FILE . '.');
            return Command::FAILURE;
        }
        $io->success('Finishing pass complete. Review the element, then from the project root run vendor/bin/typo3 extension:setup && vendor/bin/typo3 cache:flush.');
        return Command::SUCCESS;
    }
}
