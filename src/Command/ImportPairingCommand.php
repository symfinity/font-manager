<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Exception\InvalidFonttrioRegistryException;
use Symfinity\FontManager\Import\FontManagerConfigWriter;
use Symfinity\FontManager\Import\FontPairingImportPort;
use Symfinity\FontManager\Import\PairingConfigMerger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'fonts:import-pairing',
    description: 'Import a Fonttrio pairing preset into symfinity_font_manager config'
)]
final class ImportPairingCommand extends Command
{
    public function __construct(
        private readonly FontPairingImportPort $importPort,
        private readonly PairingConfigMerger $configMerger,
        private readonly FontManagerConfigWriter $configWriter,
        private readonly ParameterBagInterface $params,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::OPTIONAL, 'Pairing source (@fonttrio/slug, HTTPS URL, catalog id, or fixture path)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and show merge diff without writing config')
            ->addOption('all-catalog', null, InputOption::VALUE_NONE, 'Import every pairing listed in pairings.catalog')
            ->setHelp(
                <<<'HELP'
Import a Fonttrio pairing preset into <info>config/packages/symfinity_font_manager.yaml</info>.

Examples:
  <info>php bin/console fonts:import-pairing @fonttrio/editorial</info>
  <info>php bin/console fonts:import-pairing https://www.fonttrio.xyz/r/editorial.json</info>
  <info>php bin/console fonts:import-pairing editorial</info> (catalog id)
  <info>php bin/console fonts:import-pairing @fonttrio/editorial --dry-run</info>

After import, run <info>fonts:lock</info> to download fonts and export css_variables.
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Font Pairing');

        $configPath = $this->projectDir . '/config/packages/symfinity_font_manager.yaml';
        $existingConfig = $this->configWriter->read($configPath);

        if ($input->getOption('all-catalog')) {
            return $this->importAllCatalog($io, $existingConfig, $configPath, (bool) $input->getOption('dry-run'));
        }

        $sourceArg = $input->getArgument('source');
        $source = is_string($sourceArg) ? trim($sourceArg) : '';

        if ('' === $source) {
            $io->error('Missing source. Provide @fonttrio/{slug}, HTTPS URL, catalog id, or use --all-catalog.');

            return Command::FAILURE;
        }

        $source = $this->resolveSource($source, $existingConfig);

        try {
            $result = $this->importPort->import($source);
        } catch (InvalidFonttrioRegistryException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $merged = $this->configMerger->merge($existingConfig, $result);

        if ($input->getOption('dry-run')) {
            $io->section('Dry run — merged config preview');
            $io->writeln(Yaml::dump(['font_manager' => $merged], 6, 2));
            $io->success(sprintf('Pairing "%s" parsed (%d fonts). No files written.', $result->getId(), count($result->getFonts())));

            return Command::SUCCESS;
        }

        $this->configWriter->write($configPath, $merged);

        $io->success(sprintf(
            'Imported pairing "%s" with %d fonts into %s',
            $result->getId(),
            count($result->getFonts()),
            $configPath
        ));

        $io->listing(array_keys($result->getFonts()));
        $io->note('Run fonts:lock to download fonts and export css_variables.');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $existingConfig
     */
    private function resolveSource(string $source, array $existingConfig): string
    {
        if (str_starts_with($source, '@fonttrio/') || str_starts_with($source, 'http')) {
            return $source;
        }

        if (is_file($source)) {
            return $source;
        }

        $catalog = $existingConfig['pairings']['catalog'] ?? [];
        if (is_array($catalog) && isset($catalog[$source]['source']) && is_string($catalog[$source]['source'])) {
            return $catalog[$source]['source'];
        }

        if (!str_contains($source, '/') && !str_contains($source, '.')) {
            return '@fonttrio/' . $source;
        }

        return $source;
    }

    /**
     * @param array<string, mixed> $existingConfig
     */
    private function importAllCatalog(SymfonyStyle $io, array $existingConfig, string $configPath, bool $dryRun): int
    {
        $catalog = $existingConfig['pairings']['catalog'] ?? [];
        if (!is_array($catalog) || [] === $catalog) {
            $pairingsParam = $this->params->get('font_manager.pairings');
            if (is_array($pairingsParam)) {
                $catalog = $pairingsParam['catalog'] ?? [];
            }
        }

        if (!is_array($catalog) || [] === $catalog) {
            $io->error('No pairings.catalog entries configured.');

            return Command::FAILURE;
        }

        $merged = $existingConfig;
        foreach ($catalog as $id => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $source = $entry['source'] ?? null;
            if (!is_string($source)) {
                $io->warning(sprintf('Skipping catalog entry "%s" — missing source.', (string) $id));

                continue;
            }

            try {
                $result = $this->importPort->import($source);
                $merged = $this->configMerger->merge($merged, $result);
                $io->writeln(sprintf('  ✓ %s', $id));
            } catch (InvalidFonttrioRegistryException $exception) {
                $io->error(sprintf('Catalog entry "%s" failed: %s', (string) $id, $exception->getMessage()));

                return Command::FAILURE;
            }
        }

        if ($dryRun) {
            $io->section('Dry run — merged config preview');
            $io->writeln(Yaml::dump(['font_manager' => $merged], 6, 2));

            return Command::SUCCESS;
        }

        $this->configWriter->write($configPath, $merged);
        $io->success(sprintf('Imported %d catalog pairing(s).', count($catalog)));

        return Command::SUCCESS;
    }
}
