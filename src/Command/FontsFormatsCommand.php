<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fonts:formats',
    description: 'List available export formats'
)]
final class FontsFormatsCommand extends Command
{
    public function __construct(
        private readonly ExporterRegistry $exporterRegistry
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Font Manager - Available Export Formats');

        $exporters = $this->exporterRegistry->all();

        // Group by category
        $categories = [
            'CSS' => [],
            'SCSS' => [],
            'JavaScript' => [],
            'Design System' => [],
        ];

        foreach ($exporters as $exporter) {
            $name = $exporter->getName();
            $category = match (true) {
                str_starts_with($name, 'css_') => 'CSS',
                str_starts_with($name, 'scss_') => 'SCSS',
                str_contains($name, 'javascript') || str_contains($name, 'typescript') || str_contains($name, 'tailwind') => 'JavaScript',
                default => 'Design System',
            };

            $categories[$category][] = [
                $exporter->getName(),
                $exporter->getLabel(),
                $exporter->getFileExtension(),
                implode(', ', $exporter->getDependencies()) ?: '-',
            ];
        }

        foreach ($categories as $category => $items) {
            if ([] === $items) {
                continue;
            }

            $io->section($category);

            $table = new Table($output);
            $table->setHeaders(['Name', 'Label', 'Extension', 'Dependencies']);
            $table->setRows($items);
            $table->render();
        }

        $io->newLine();
        $io->info(sprintf('Total: %d export format(s)', count($exporters)));

        $io->note([
            'Use "fonts:format:info <name>" to see usage instructions',
            'Use "fonts:export --format=<name>" to export specific format',
        ]);

        return Command::SUCCESS;
    }
}
