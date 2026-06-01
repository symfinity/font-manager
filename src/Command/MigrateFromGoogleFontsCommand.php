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
    name: 'fonts:migrate-from-google-fonts',
    description: 'Migrate from symfinity/font-manager to font-manager'
)]
final class MigrateFromGoogleFontsCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be changed without making changes')
            ->addOption('skip-templates', null, InputOption::VALUE_NONE, 'Skip template migration')
            ->addOption('skip-config', null, InputOption::VALUE_NONE, 'Skip configuration migration')
            ->setHelp(
                <<<'HELP'
This command helps migrate from symfinity/font-manager to font-manager.

It will:
1. Convert config/packages/google_fonts.yaml to font_manager.yaml
2. Update templates: google_fonts() → font_manager()
3. Move manifest file: google-fonts.lock.json → font-manager.lock.json
4. Optionally backup old files

<info>php bin/console fonts:migrate-from-google-fonts</info>

Dry run to preview changes:
<info>php bin/console fonts:migrate-from-google-fonts --dry-run</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $skipTemplates = (bool) $input->getOption('skip-templates');
        $skipConfig = (bool) $input->getOption('skip-config');

        $io->title('Migrating from google-fonts to font-manager');

        if ($dryRun) {
            $io->note('DRY RUN - No files will be modified');
        }

        $changes = [];
        $errors = [];

        if (!$skipConfig) {
            $configResult = $this->migrateConfiguration($io, $dryRun);
            $changes = array_merge($changes, $configResult['changes']);
            $errors = array_merge($errors, $configResult['errors']);
        }

        if (!$skipTemplates) {
            $templateResult = $this->migrateTemplates($io, $dryRun);
            $changes = array_merge($changes, $templateResult['changes']);
            $errors = array_merge($errors, $templateResult['errors']);
        }

        $manifestResult = $this->migrateManifest($io, $dryRun);
        $changes = array_merge($changes, $manifestResult['changes']);
        $errors = array_merge($errors, $manifestResult['errors']);

        if ([] === $changes && [] === $errors) {
            $io->success('No google-fonts installation found or migration already complete');

            return Command::SUCCESS;
        }

        if ([] !== $changes) {
            $io->section('Summary of changes');
            foreach ($changes as $change) {
                $io->writeln(sprintf('  %s %s', $dryRun ? '→' : '✓', $change));
            }
        }

        if ([] !== $errors) {
            $io->section('Errors');
            foreach ($errors as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note('Run without --dry-run to apply these changes');
        } else {
            $io->success('Migration completed successfully!');
            $io->writeln('');
            $io->writeln('Next steps:');
            $io->writeln('1. Review the changes');
            $io->writeln('2. Test your application');
            $io->writeln('3. Run: composer remove symfinity/font-manager');
            $io->writeln('4. Delete: config/packages/google_fonts.yaml (if backup exists)');
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{changes: array<string>, errors: array<string>}
     */
    private function migrateConfiguration(SymfonyStyle $io, bool $dryRun): array
    {
        $changes = [];
        $errors = [];

        $oldConfig = $this->projectDir . '/config/packages/google_fonts.yaml';
        $newConfig = $this->projectDir . '/config/packages/font_manager.yaml';

        if (!$this->filesystem->exists($oldConfig)) {
            return ['changes' => [], 'errors' => []];
        }

        $io->section('Configuration Migration');

        if ($this->filesystem->exists($newConfig)) {
            $errors[] = "Configuration file already exists: {$newConfig}";

            return ['changes' => $changes, 'errors' => $errors];
        }

        $content = file_get_contents($oldConfig);
        if (false === $content) {
            $errors[] = "Failed to read {$oldConfig}";

            return ['changes' => $changes, 'errors' => $errors];
        }

        $newContent = str_replace('google_fonts:', 'font_manager:', $content);
        $newContent = str_replace('GOOGLE_FONTS_', 'GOOGLE_FONTS_', $newContent);

        if (!str_contains($newContent, 'default_provider:')) {
            $newContent = str_replace(
                'font_manager:',
                "font_manager:\n    default_provider: 'google'",
                $newContent
            );
        }

        if (!$dryRun) {
            $backupConfig = $oldConfig . '.backup';
            $this->filesystem->copy($oldConfig, $backupConfig);
            $changes[] = "Backed up: {$oldConfig} → {$backupConfig}";

            file_put_contents($newConfig, $newContent);
            $changes[] = "Created: {$newConfig}";
        } else {
            $changes[] = "Would create: {$newConfig}";
            $changes[] = "Would backup: {$oldConfig}";
        }

        return ['changes' => $changes, 'errors' => $errors];
    }

    /**
     * @return array{changes: array<string>, errors: array<string>}
     */
    private function migrateTemplates(SymfonyStyle $io, bool $dryRun): array
    {
        $changes = [];
        $errors = [];

        $templatesDir = $this->projectDir . '/templates';

        if (!$this->filesystem->exists($templatesDir)) {
            return ['changes' => [], 'errors' => []];
        }

        $io->section('Template Migration');

        $finder = new Finder();
        $finder->files()->in($templatesDir)->name('*.twig');

        $updatedFiles = 0;

        foreach ($finder as $file) {
            $content = $file->getContents();

            if (!str_contains($content, 'google_fonts(')) {
                continue;
            }

            $newContent = str_replace('google_fonts(', 'font_manager(', $content);

            if (!$dryRun) {
                file_put_contents($file->getRealPath(), $newContent);
            }

            $changes[] = sprintf('Updated: %s', $file->getRelativePathname());
            ++$updatedFiles;
        }

        if (0 === $updatedFiles) {
            $io->writeln('<comment>No templates using google_fonts() found</comment>');
        }

        return ['changes' => $changes, 'errors' => $errors];
    }

    /**
     * @return array{changes: array<string>, errors: array<string>}
     */
    private function migrateManifest(SymfonyStyle $io, bool $dryRun): array
    {
        $changes = [];
        $errors = [];

        $oldManifest = $this->projectDir . '/var/google-fonts.lock.json';
        $newManifest = $this->projectDir . '/var/font-manager.lock.json';

        if (!$this->filesystem->exists($oldManifest)) {
            return ['changes' => [], 'errors' => []];
        }

        $io->section('Manifest Migration');

        if ($this->filesystem->exists($newManifest)) {
            $errors[] = "Manifest already exists: {$newManifest}";

            return ['changes' => $changes, 'errors' => $errors];
        }

        if (!$dryRun) {
            $this->filesystem->copy($oldManifest, $newManifest);
            $changes[] = "Copied: {$oldManifest} → {$newManifest}";
            $changes[] = 'Note: Old manifest kept as backup (delete manually after verification)';
        } else {
            $changes[] = "Would copy: {$oldManifest} → {$newManifest}";
        }

        return ['changes' => $changes, 'errors' => $errors];
    }
}
