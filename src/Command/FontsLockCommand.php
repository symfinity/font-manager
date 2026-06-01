<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Enum\BuildToolType;
use Symfinity\FontManager\Model\Font;
use Symfinity\FontManager\Model\FontCollection;
use Symfinity\FontManager\Service\BuildToolDetector;
use Symfinity\FontManager\Service\ExporterOrchestrator;
use Symfinity\FontManager\Service\FontLockManager;
use Symfinity\FontManager\Service\FormatAutoDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'fonts:lock',
    description: 'Scan templates and lock all used fonts for production'
)]
final class FontsLockCommand extends Command
{
    public function __construct(
        private readonly FontLockManager $lockManager,
        private readonly ExporterOrchestrator $orchestrator,
        private readonly BuildToolDetector $buildToolDetector,
        private readonly FormatAutoDetector $formatAutoDetector,
        private readonly ParameterBagInterface $params,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'template-dirs',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Template directories to scan',
                []
            )
            ->addOption('no-export', null, InputOption::VALUE_NONE, 'Skip automatic export after locking')
            ->setHelp(
                'The <info>%command.name%</info> command scans Twig templates for font_manager() function calls, ' .
                'downloads all referenced fonts, and creates a manifest file for production use.' . "\n\n" .
                'Example: <info>php %command.full_name%</info>'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Lock Fonts');

        $templateDirsArg = $input->getArgument('template-dirs');
        $templateDirs = is_array($templateDirsArg) ? $templateDirsArg : [];

        // Default to common template directories
        if ([] === $templateDirs) {
            $defaultDirs = [
                $this->projectDir . '/templates',
                $this->projectDir . '/views',
            ];
            $templateDirs = array_filter($defaultDirs, 'is_dir');

            if ([] === $templateDirs) {
                $io->error('No template directories found. Please specify template directories as arguments.');

                return Command::FAILURE;
            }
        }

        $templateDirs = array_filter($templateDirs, 'is_string');

        $io->section('Scanning templates');
        $io->listing(array_map(fn (string $dir): string => "<info>{$dir}</info>", $templateDirs));

        $fonts = $this->lockManager->scanTemplates($templateDirs);

        if ([] === $fonts) {
            $io->warning('No font_manager() function calls found in templates.');

            return Command::SUCCESS;
        }

        $io->section('Found fonts');
        $fontList = [];
        /** @var array<array-key, mixed> $fonts */
        foreach ($fonts as $name => $config) {
            if (!is_array($config)) {
                continue;
            }
            /** @var array{weights?: array<int|string>, styles?: array<string>} $config */
            $weights = implode(', ', $config['weights'] ?? []);
            $styles = implode(', ', $config['styles'] ?? []);
            $fontList[] = sprintf('<info>%s</info> (weights: %s, styles: %s)', $name, $weights, $styles);
        }
        $io->listing($fontList);

        $io->section('Downloading fonts');
        $io->progressStart(count($fonts));

        $this->lockManager->lockFonts($fonts, function (int $current, int $total, string $name) use ($io): void {
            $io->progressAdvance();
        });

        $io->progressFinish();

        $io->success(sprintf('Successfully locked %d fonts to %s', count($fonts), $this->lockManager->getManifestFile()));

        // Auto-export if configured and not disabled
        if (!$input->getOption('no-export') && $this->shouldAutoExport()) {
            $io->section('Exporting fonts');

            $manifest = $this->lockManager->loadManifest();
            $manifestFonts = $manifest['fonts'] ?? [];
            if (!is_array($manifestFonts)) {
                $manifestFonts = [];
            }
            $fontCollection = $this->buildFontCollection($manifestFonts);

            // Detect build tool
            $buildToolConfig = $this->params->get('font_manager.build.tool');
            $buildTool = match ($buildToolConfig) {
                'auto' => $this->buildToolDetector->detect($this->projectDir),
                'assetmapper' => BuildToolType::ASSET_MAPPER,
                'webpack' => BuildToolType::WEBPACK,
                'vite' => BuildToolType::VITE,
                default => BuildToolType::UNKNOWN,
            };

            // Get configured export formats
            $formats = $this->params->get('font_manager.export.formats');
            $autoDetect = $this->params->get('font_manager.export.auto_detect');

            // Use auto-detection if enabled
            if (true === $autoDetect) {
                $formats = $this->formatAutoDetector->detect($this->projectDir);
                $io->comment(sprintf('Auto-detected formats: %s', implode(', ', $formats)));
            }

            if (is_array($formats) && [] !== $formats) {
                $io->comment(sprintf('Detected build tool: %s', $this->buildToolDetector->getName($buildTool)));
                $io->comment(sprintf('Exporting %d format(s): %s', count($formats), implode(', ', $formats)));

                $results = $this->orchestrator->export(
                    $fontCollection,
                    $formats,
                    $this->projectDir,
                    $buildTool,
                    true
                );

                $io->success(sprintf('Exported %d file(s)', count($results)));
            }
        }

        return Command::SUCCESS;
    }

    private function shouldAutoExport(): bool
    {
        $formats = $this->params->get('font_manager.export.formats');

        return is_array($formats) && [] !== $formats;
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
