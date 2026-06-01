<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Exporter\ExporterRegistry;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use Symfinity\FontManager\Service\BuildToolDetector;
use Symfinity\FontManager\Service\ExporterOrchestrator;
use Symfinity\FontManager\Service\FontLockManager;
use Symfinity\FontManager\Service\FormatAutoDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fonts:export',
    description: 'Export fonts in configured formats'
)]
final class FontsExportCommand extends Command
{
    public function __construct(
        private readonly ExporterRegistry $exporterRegistry,
        private readonly ExporterOrchestrator $orchestrator,
        private readonly FontLockManager $lockManager,
        private readonly BuildToolDetector $buildToolDetector,
        private readonly FormatAutoDetector $formatAutoDetector,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', 'f', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Export formats to generate')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be exported without writing files')
            ->addOption('build-tool', 'b', InputOption::VALUE_REQUIRED, 'Build tool (auto, assetmapper, webpack, vite)', 'auto');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Font Manager - Export Fonts');

        // Get locked fonts
        $manifest = $this->lockManager->loadManifest();

        if (!isset($manifest['fonts']) || !is_array($manifest['fonts']) || [] === $manifest['fonts']) {
            $io->error('No fonts found in manifest. Run fonts:lock first.');

            return Command::FAILURE;
        }

        // Build font collection from manifest
        $fontCollection = $this->buildFontCollection($manifest['fonts']);

        $io->info(sprintf('Found %d font(s) in manifest', $fontCollection->count()));

        // Detect or use specified build tool
        $buildToolOption = $input->getOption('build-tool');
        $buildTool = match ($buildToolOption) {
            'auto' => $this->buildToolDetector->detect($this->projectDir),
            'assetmapper' => BuildToolType::ASSET_MAPPER,
            'webpack' => BuildToolType::WEBPACK,
            'vite' => BuildToolType::VITE,
            default => BuildToolType::UNKNOWN,
        };

        $io->info(sprintf('Build tool: %s', $this->buildToolDetector->getName($buildTool)));

        // Get formats to export
        $formatsOption = $input->getOption('format');
        $formats = is_array($formatsOption) ? $formatsOption : [];
        if ([] === $formats) {
            // Try auto-detection
            $autoDetected = $this->formatAutoDetector->detect($this->projectDir);
            if ([] !== $autoDetected) {
                $formats = $autoDetected;
                $io->comment(sprintf('Auto-detected formats: %s', implode(', ', $formats)));
            } else {
                // Fallback: Use all available formats
                $formats = $this->exporterRegistry->getNames();
            }
        }

        $io->info(sprintf('Exporting %d format(s)', count($formats)));

        // Validate dependencies
        $validation = $this->orchestrator->validateDependencies($formats);

        if ([] !== $validation['invalid']) {
            $io->warning('Some formats have missing dependencies:');
            foreach ($validation['invalid'] as $format => $deps) {
                $io->writeln(sprintf('  - %s: missing %s', $format, implode(', ', $deps)));
            }

            // Add missing dependencies
            foreach ($validation['invalid'] as $format => $deps) {
                foreach ($deps as $dep) {
                    if (!in_array($dep, $formats, true)) {
                        $formats[] = $dep;
                    }
                }
            }

            $io->info('Added missing dependencies automatically');
        }

        // Export
        $dryRun = $input->getOption('dry-run');
        $results = $this->orchestrator->export(
            $fontCollection,
            $formats,
            $this->projectDir,
            $buildTool,
            !$dryRun
        );

        // Show results
        $io->section('Export Results');

        $totalSize = 0;
        foreach ($results as $result) {
            $status = $result['written'] ? '✓' : '○';
            $size = number_format($result['size'] / 1024, 2);
            $io->writeln(sprintf(
                '%s %s → %s (%s KB)',
                $status,
                $result['exporter'],
                $result['path'],
                $size
            ));
            $totalSize += $result['size'];
        }

        $io->newLine();
        $io->success(sprintf(
            'Exported %d file(s) (%s KB total)',
            count($results),
            number_format($totalSize / 1024, 2)
        ));

        if ($dryRun) {
            $io->note('Dry run - no files were written');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $manifestFonts
     */
    private function buildFontCollection(array $manifestFonts): FontCollection
    {
        $collection = new FontCollection();

        foreach ($manifestFonts as $name => $fontData) {
            if (!is_array($fontData)) {
                continue;
            }

            $fontName = $fontData['name'] ?? $name;
            $font = new Font(
                name: is_string($fontName) ? $fontName : 'Unknown',
                weights: is_array($fontData['weights'] ?? null) ? array_map('intval', $fontData['weights']) : [400],
                styles: is_array($fontData['styles'] ?? null) ? array_map('strval', $fontData['styles']) : ['normal'],
                monospace: is_bool($fontData['monospace'] ?? null) ? $fontData['monospace'] : false,
                semantic: is_string($fontData['semantic'] ?? null) ? $fontData['semantic'] : null,
                files: is_array($fontData['files'] ?? null) ? $fontData['files'] : []
            );

            $collection->add($font);
        }

        return $collection;
    }
}
