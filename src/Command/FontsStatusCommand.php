<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'fonts:status',
    description: 'Show status of locked fonts'
)]
final class FontsStatusCommand extends Command
{
    public function __construct(
        private readonly string $manifestFile,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Fonts Status');

        if (!$this->filesystem->exists($this->manifestFile)) {
            $io->warning('No fonts locked yet. Run fonts:lock to lock fonts for production.');

            return Command::SUCCESS;
        }

        $content = file_get_contents($this->manifestFile);
        if (false === $content) {
            $io->error('Failed to read manifest file');

            return Command::FAILURE;
        }

        $manifest = json_decode($content, true);
        if (!is_array($manifest)) {
            $io->error('Invalid manifest file');

            return Command::FAILURE;
        }

        $io->section('Manifest Info');
        $io->definitionList(
            ['Locked' => $manifest['locked'] ? 'Yes' : 'No'],
            ['Generated' => $manifest['generated_at'] ?? 'Unknown'],
            ['Fonts' => count($manifest['fonts'] ?? [])],
            ['Manifest File' => $this->manifestFile]
        );

        $fonts = $manifest['fonts'] ?? [];
        if ([] !== $fonts) {
            $io->section('Locked Fonts');

            $rows = [];
            foreach ($fonts as $name => $config) {
                if (!is_array($config)) {
                    continue;
                }
                $rows[] = [
                    $name,
                    implode(', ', $config['weights'] ?? []),
                    implode(', ', $config['styles'] ?? []),
                    count($config['files'] ?? []),
                    $config['provider'] ?? 'unknown',
                ];
            }

            $io->table(['Font', 'Weights', 'Styles', 'Files', 'Provider'], $rows);
        }

        return Command::SUCCESS;
    }
}
