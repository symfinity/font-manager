<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Command;

use Symfinity\FontManager\Enum\ProviderFeature;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fonts:search',
    description: 'Search for fonts by name'
)]
final class FontsSearchCommand extends Command
{
    public function __construct(
        private readonly ProviderRegistry $providerRegistry
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'Provider to use (google, bunny, local)', null)
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Maximum results', '20')
            ->setHelp(
                'The <info>%command.name%</info> command searches for fonts by name.' . "\n\n" .
                'Example: <info>php %command.full_name% roboto</info>' . "\n" .
                'Example: <info>php %command.full_name% open --limit=10</info>' . "\n" .
                'Example: <info>php %command.full_name% sans --provider=google</info>'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $query = $input->getArgument('query');
        if (!is_string($query)) {
            $io->error('Query must be a string');

            return Command::FAILURE;
        }

        $providerName = $input->getOption('provider');
        $limitOption = $input->getOption('limit');
        $limit = is_string($limitOption) ? (int) $limitOption : 20;

        try {
            // Get provider for search
            if (null !== $providerName && is_string($providerName)) {
                $provider = $this->providerRegistry->getProvider($providerName);
            } else {
                $provider = $this->providerRegistry->getDefaultProvider();

                // Bunny Fonts doesn't have search API - automatically use Google Fonts instead
                // (Bunny uses the same font catalog as Google)
                if ('bunny' === $provider->getName()) {
                    $io->note('Bunny Fonts uses the same catalog as Google Fonts. Searching via Google Fonts API...');
                    $provider = $this->providerRegistry->getProvider('google');
                }
            }

            if (!$provider->supports(ProviderFeature::SEARCH)) {
                $io->error(sprintf(
                    'Provider "%s" does not support search. Try using --provider=google',
                    $provider->getName()
                ));

                return Command::FAILURE;
            }

            $io->title(sprintf('Searching fonts via %s provider', $provider->getName()));

            $results = $provider->searchFonts($query, $limit);

            if ([] === $results) {
                $io->warning(sprintf('No fonts found matching "%s"', $query));

                return Command::SUCCESS;
            }

            $io->success(sprintf('Found %d fonts', count($results)));

            $rows = [];
            foreach ($results as $font) {
                $rows[] = [
                    $font['family'],
                    $font['category'],
                    implode(', ', array_slice($font['variants'], 0, 10)),
                ];
            }

            $io->table(['Font Family', 'Category', 'Variants (sample)'], $rows);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
