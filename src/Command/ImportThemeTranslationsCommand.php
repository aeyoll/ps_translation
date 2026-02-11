<?php

namespace PrestaShop\Module\Translation\Command;

use Exception;
use PrestaShop\Module\Translation\Service\ThemeTranslationImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportThemeTranslationsCommand extends Command
{
    protected static $defaultName = 'prestashop:translations:import-theme';
    protected static $defaultDescription = 'Import theme translations from XLIFF files to the database';

    private ThemeTranslationImporter $translationImporter;

    public function __construct(ThemeTranslationImporter $translationImporter)
    {
        parent::__construct();
        $this->translationImporter = $translationImporter;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument(
                'theme',
                InputArgument::REQUIRED,
                'Theme name (directory name in themes/)'
            )
            ->addArgument(
                'iso_code',
                InputArgument::REQUIRED,
                'Language ISO code (e.g., fr, en, es)'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command imports theme translations from XLIFF files to the database.

This command reads XLIFF files from the theme's translations directory and saves them to the database,
allowing translations to be version-controlled and deployed via git.

Usage:
  <info>php %command.full_name% <theme> <iso_code></info>

Examples:
  <info>php bin/console prestashop:translations:import-theme yourtheme fr</info>
  <info>php bin/console prestashop:translations:import-theme classic en</info>

The command will:
1. Find all XLIFF files in themes/<theme>/translations/<locale>/
2. Parse each XLIFF file using Symfony's XliffFileLoader
3. Extract the domain from the filename (e.g., ShopThemeGlobal.fr-FR.xlf → ShopThemeGlobal)
4. Save each translation to the database with the theme name
5. Update existing translations or create new ones as needed
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $themeName = $input->getArgument('theme');
        $isoCode = $input->getArgument('iso_code');

        $io->title(sprintf('Importing translations for theme "%s" (ISO code: %s)', $themeName, $isoCode));

        try {
            $io->section('Step 1: Finding and parsing XLIFF files...');
            $result = $this->translationImporter->importTranslations($themeName, $isoCode);

            $io->success(sprintf('Found %d XLIFF file(s) (locale: %s)', count($result['processed_files']), $result['locale']));

            $io->section('Step 2: Importing translations to database...');

            foreach ($result['processed_files'] as $fileInfo) {
                $io->writeln(sprintf(
                    '  - %s (domain: %s) → %d translation(s)',
                    $fileInfo['filename'],
                    $fileInfo['domain'],
                    $fileInfo['translations']
                ));
            }

            $io->newLine();
            $io->success(sprintf(
                'Successfully imported %d translation(s) from %d file(s) for theme "%s"',
                $result['total_translations'],
                $result['total_files'],
                $themeName
            ));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Failed to import theme translations: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('<error>' . $e->getTraceAsString() . '</error>');
            }

            return Command::FAILURE;
        }
    }
}
