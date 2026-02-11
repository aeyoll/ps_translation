<?php

namespace PrestaShop\Module\Translation\Service;

use Exception;
use PrestaShop\PrestaShop\Core\Language\LanguageRepositoryInterface;
use PrestaShopBundle\Service\TranslationService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\XliffFileLoader;

class ThemeTranslationImporter
{
    private TranslationService $translationService;
    private LanguageRepositoryInterface $languageRepository;
    private Filesystem $filesystem;
    private string $themesDir;

    public function __construct(
        TranslationService $translationService,
        LanguageRepositoryInterface $languageRepository,
        Filesystem $filesystem,
        string $themesDir
    ) {
        $this->translationService = $translationService;
        $this->languageRepository = $languageRepository;
        $this->filesystem = $filesystem;
        $this->themesDir = $themesDir;
    }

    public function importTranslations(string $themeName, string $isoCode): array
    {
        $language = $this->languageRepository->getOneByIsoCode($isoCode);

        if (!$language) {
            throw new Exception(sprintf('Language with ISO code "%s" not found', $isoCode));
        }

        $locale = $language->getLocale();

        $lang = $this->translationService->findLanguageByLocale($locale);

        $translationFolder = $this->themesDir . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . $locale;

        if (!$this->filesystem->exists($translationFolder)) {
            throw new Exception(sprintf('Translation directory not found: "%s"', $translationFolder));
        }

        $finder = new Finder();
        $finder->files()->in($translationFolder)->name('*.xlf');

        if (!$finder->hasResults()) {
            throw new Exception(sprintf('No XLF files found in "%s"', $translationFolder));
        }

        $xliffLoader = new XliffFileLoader();
        $totalTranslations = 0;
        $totalFiles = 0;
        $processedFiles = [];

        foreach ($finder as $file) {
            $domain = $this->extractDomainFromFilename($file->getFilename(), $locale);

            try {
                $catalogue = $xliffLoader->load($file->getRealPath(), $locale, $domain);

                $fileTranslations = 0;

                foreach ($catalogue->all($domain) as $key => $translation) {
                    if ($key !== $translation) {
                        $success = $this->translationService->saveTranslationMessage(
                            $lang,
                            $domain,
                            $key,
                            $translation,
                            $themeName
                        );

                        if ($success) {
                            ++$fileTranslations;
                            ++$totalTranslations;
                        }
                    }
                }

                $processedFiles[] = [
                    'filename' => $file->getFilename(),
                    'domain' => $domain,
                    'translations' => $fileTranslations,
                ];

                ++$totalFiles;
            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to process file "%s": %s', $file->getFilename(), $e->getMessage()));
            }
        }

        return [
            'total_translations' => $totalTranslations,
            'total_files' => $totalFiles,
            'processed_files' => $processedFiles,
            'locale' => $locale,
            'theme' => $themeName,
        ];
    }

    private function extractDomainFromFilename(string $filename, string $locale): string
    {
        return str_replace('.' . $locale . '.xlf', '', $filename);
    }
}
