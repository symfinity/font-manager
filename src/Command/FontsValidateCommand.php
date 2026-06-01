<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Provider\LocalFontsProvider;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fonts:validate',
    description: 'Validate local font files exist'
)]
final class FontsValidateCommand extends Command
{
    public function __construct(
        private readonly ProviderRegistry $providerRegistry
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Validating Local Fonts');

        try {
            $provider = $this->providerRegistry->getProvider('local');

            if (!$provider instanceof LocalFontsProvider) {
                $io->error('Local fonts provider not found or not configured properly.');

                return Command::FAILURE;
            }

            $errors = $provider->validateFonts();

            if ([] === $errors) {
                $io->success('All local font files found!');

                return Command::SUCCESS;
            }

            $io->error(sprintf('Found %d missing font files:', count($errors)));

            $rows = [];
            foreach ($errors as $error) {
                $rows[] = [
                    $error['font'],
                    $error['variant'],
                    $error['file'],
                    $error['path'],
                ];
            }

            $io->table(['Font', 'Variant', 'File', 'Expected Path'], $rows);

            return Command::FAILURE;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
