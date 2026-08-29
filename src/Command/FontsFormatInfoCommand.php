<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Exception\ExporterException;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fonts:format:info',
    description: 'Show usage information for an export format'
)]
final class FontsFormatInfoCommand extends Command
{
    public function __construct(
        private readonly ExporterRegistry $exporterRegistry
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('format', InputArgument::REQUIRED, 'Format name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $formatName = $input->getArgument('format');
        if (!is_string($formatName)) {
            $io->error('Invalid format name');

            return Command::FAILURE;
        }

        try {
            $exporter = $this->exporterRegistry->get($formatName);
        } catch (ExporterException $e) {
            $io->error(sprintf('Format "%s" not found', $formatName));
            $io->note('Use "fonts:formats" to see available formats');

            return Command::FAILURE;
        }

        $io->title(sprintf('Format: %s', $exporter->getLabel()));

        $io->definitionList(
            ['Name' => $exporter->getName()],
            ['Extension' => $exporter->getFileExtension()],
            ['Default Filename' => $exporter->getDefaultFilename() . $exporter->getFileExtension()],
            ['Dependencies' => [] !== $exporter->getDependencies()
                ? implode(', ', $exporter->getDependencies())
                : 'None'],
        );

        $io->section('Usage Instructions');
        $io->block($exporter->getUsageInstructions(), null, 'fg=white;bg=blue', ' ', true);

        $io->section('Export Examples');
        $io->writeln([
            sprintf('  # Export only %s', $exporter->getName()),
            sprintf('  php bin/console fonts:export --format=%s', $exporter->getName()),
            '',
            '  # Export with dry-run',
            sprintf('  php bin/console fonts:export --format=%s --dry-run', $exporter->getName()),
        ]);

        return Command::SUCCESS;
    }
}
