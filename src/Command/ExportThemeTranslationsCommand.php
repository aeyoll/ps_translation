<?php

namespace PrestaShop\Module\Translation\Command;

use Exception;
use PrestaShop\Module\Translation\Service\ThemeTranslationExporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExportThemeTranslationsCommand extends Command
{
    protected static $defaultName = 'prestashop:translations:export-theme';
    protected static $defaultDescription = 'Export theme translations to XLIFF files and copy them to the theme directory';

    private ThemeTranslationExporter $translationExporter;

    public function __construct(ThemeTranslationExporter $translationExporter)
    {
        parent::__construct();
        $this->translationExporter = $translationExporter;
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
The <info>%command.name%</info> command exports theme translations to XLIFF files and copies them to the theme's translations directory.

This command performs the same operations as the admin interface:
1. Generates XLIFF translation files from theme templates, existing translations, and database
2. Creates a ZIP archive
3. Extracts the archive
4. Copies the XLIFF files to the theme's translations directory
5. Cleans up temporary files

Usage:
  <info>php %command.full_name% <theme> <iso_code></info>

Examples:
  <info>php bin/console prestashop:translations:export-theme yourtheme fr</info>
  <info>php bin/console prestashop:translations:export-theme yourtheme en</info>
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $themeName = $input->getArgument('theme');
        $isoCode = $input->getArgument('iso_code');

        $io->title(sprintf('Exporting translations for theme "%s" (ISO code: %s)', $themeName, $isoCode));

        try {
            $io->section('Step 1: Generating XLIFF files and creating archive...');
            $result = $this->translationExporter->exportAndCopyTranslations($themeName, $isoCode);

            $io->success(sprintf('Archive created successfully (locale: %s)', $result['locale']));

            $io->section('Step 2: Extracting archive...');
            $io->writeln(sprintf('Extracted %d file(s)', count($result['extracted_files'])));

            foreach ($result['extracted_files'] as $fileInfo) {
                $io->writeln('  - ' . $fileInfo['filename']);
            }

            $io->section('Step 3: Copying files to theme translations directory...');
            $io->writeln(sprintf('Copied %d file(s)', count($result['copied_files'])));

            foreach ($result['copied_files'] as $fileInfo) {
                $io->writeln('  - ' . $fileInfo['filename'] . ' → ' . $fileInfo['destination']);
            }

            $io->section('Step 4: Cleaning up temporary files...');
            $io->success('Cleanup completed');

            $io->newLine();
            $io->success(sprintf(
                'Theme translations exported successfully! %d XLIFF file(s) copied to theme directory.',
                count($result['copied_files'])
            ));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Failed to export theme translations: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('<error>' . $e->getTraceAsString() . '</error>');
            }

            return Command::FAILURE;
        }
    }
}
