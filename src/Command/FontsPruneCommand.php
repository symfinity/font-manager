<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'fonts:prune',
    description: 'Remove unused locked fonts'
)]
final class FontsPruneCommand extends Command
{
    public function __construct(
        private readonly string $manifestFile,
        private readonly string $fontsDir,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
            ->setHelp(
                'The <info>%command.name%</info> command removes font files not referenced in the manifest.' . "\n\n" .
                'Example: <info>php %command.full_name%</info>' . "\n" .
                'Example: <info>php %command.full_name% --dry-run</info>'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Prune Unused Fonts');

        $dryRun = $input->getOption('dry-run');

        if (!$this->filesystem->exists($this->manifestFile)) {
            $io->warning('No manifest file found. Nothing to prune.');

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

        // Collect all referenced files
        $referencedFiles = [];
        foreach ($manifest['fonts'] ?? [] as $fontConfig) {
            if (!is_array($fontConfig)) {
                continue;
            }
            foreach ($fontConfig['files'] ?? [] as $file) {
                $referencedFiles[$file] = true;
            }
            // Also keep CSS files
            if (isset($fontConfig['css'])) {
                $cssFile = basename((string) $fontConfig['css']);
                $referencedFiles[$cssFile] = true;
            }
        }

        if (!$this->filesystem->exists($this->fontsDir)) {
            $io->info('Fonts directory does not exist. Nothing to prune.');

            return Command::SUCCESS;
        }

        // Find all font files
        $finder = new Finder();
        $finder->files()->in($this->fontsDir)->name('/\.(woff2?|ttf|eot|otf|css)$/i');

        $toDelete = [];
        foreach ($finder as $file) {
            $filename = $file->getFilename();
            if (!isset($referencedFiles[$filename])) {
                $toDelete[] = $file->getPathname();
            }
        }

        if ([] === $toDelete) {
            $io->success('No unused fonts found.');

            return Command::SUCCESS;
        }

        $io->section('Unused Fonts');
        $io->listing($toDelete);

        if ($dryRun) {
            $io->note(sprintf('Dry run: %d files would be deleted', count($toDelete)));

            return Command::SUCCESS;
        }

        if (!$io->confirm(sprintf('Delete %d unused font files?', count($toDelete)), false)) {
            $io->info('Cancelled.');

            return Command::SUCCESS;
        }

        foreach ($toDelete as $file) {
            $this->filesystem->remove($file);
        }

        $io->success(sprintf('Removed %d unused font files', count($toDelete)));

        return Command::SUCCESS;
    }
}
