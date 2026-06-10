<?php

declare(strict_types=1);

namespace Dirnbauer\Innesto\Command;

use Dirnbauer\Innesto\Registry\ElementScaffolder;
use Dirnbauer\Innesto\Registry\FinishingPromptBuilder;
use Dirnbauer\Innesto\Registry\RegistryClient;
use Dirnbauer\Innesto\Registry\SetRegistrar;
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

#[AsCommand(
    name: 'innesto:add',
    description: 'Fetch a shadcn registry item and scaffold it as a Content Blocks element'
)]
final class AddRegistryItemCommand extends Command
{
    public function __construct(
        private readonly RegistryClient $registryClient,
        private readonly ElementScaffolder $scaffolder,
        private readonly FinishingPromptBuilder $promptBuilder,
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

        $blockName = 'innesto/' . $elementKey;
        if ($input->getOption('target') === null) {
            $setConfigPath = ExtensionManagementUtility::extPath('innesto') . 'Configuration/Sets/Innesto/config.yaml';
            if ($this->setRegistrar->register($setConfigPath, $blockName)) {
                $io->text(sprintf('Registered "%s" in the Innesto site set (Configuration/Sets/Innesto/config.yaml).', $blockName));
            } else {
                $io->warning(sprintf('Could not register "%s" in Configuration/Sets/Innesto/config.yaml — add it to optionalDependencies manually, otherwise the element stays hidden in the New Content Element wizard.', $blockName));
            }
        } else {
            $io->warning(sprintf('Custom target: add "%s" to the optionalDependencies of your site set, otherwise the element stays hidden in the New Content Element wizard on sites that restrict content blocks per set (Desiderio does).', $blockName));
        }

        $elementDir = rtrim($target, '/') . '/' . $elementKey;
        $prompt = $this->promptBuilder->build($item, $elementKey, $elementDir);
        file_put_contents($elementDir . '/AI_PROMPT.md', $prompt);
        $io->text('AI finishing prompt written to ' . $elementKey . '/AI_PROMPT.md');

        if ($input->getOption('ai')) {
            $exitCode = $this->runFinishingPass($io, $elementDir, $prompt);
            if ($exitCode !== Command::SUCCESS) {
                return $exitCode;
            }
        } else {
            $io->text([
                'Next steps (or rerun with --ai):',
                '  1. Run the finishing pass: claude -p "$(cat AI_PROMPT.md)" --permission-mode acceptEdits',
                '  2. Review the result, then: vendor/bin/typo3 extension:setup && cache:flush',
            ]);
        }
        return Command::SUCCESS;
    }

    private function runFinishingPass(SymfonyStyle $io, string $elementDir, string $prompt): int
    {
        $binary = getenv('INNESTO_CLAUDE_BIN') ?: (new ExecutableFinder())->find('claude');
        if ($binary === null) {
            $io->warning([
                'claude CLI not found in PATH (in ddev it usually lives on the host, not in the container).',
                'Run the pass manually from the element directory:',
                '  cd ' . $elementDir,
                '  claude -p "$(cat AI_PROMPT.md)" --permission-mode acceptEdits',
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
            $io->error('Finishing pass failed (exit ' . $process->getExitCode() . '). The scaffold is intact; rerun manually with AI_PROMPT.md.');
            return Command::FAILURE;
        }
        $io->success('Finishing pass complete. Review the element, then run extension:setup && cache:flush.');
        return Command::SUCCESS;
    }
}
