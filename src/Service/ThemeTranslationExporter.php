<?php

namespace PrestaShop\Module\Translation\Service;

use Exception;
use PrestaShop\PrestaShop\Core\Language\LanguageRepositoryInterface;
use PrestaShop\PrestaShop\Core\Translation\Export\TranslationCatalogueExporter;
use PrestaShop\PrestaShop\Core\Translation\Storage\Provider\Definition\ProviderDefinitionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class ThemeTranslationExporter
{
    private TranslationCatalogueExporter $catalogueExporter;
    private LanguageRepositoryInterface $languageRepository;
    private Filesystem $filesystem;
    private string $themesDir;

    public function __construct(
        TranslationCatalogueExporter $catalogueExporter,
        LanguageRepositoryInterface $languageRepository,
        Filesystem $filesystem,
        string $themesDir
    ) {
        $this->catalogueExporter = $catalogueExporter;
        $this->languageRepository = $languageRepository;
        $this->filesystem = $filesystem;
        $this->themesDir = $themesDir;
    }

    public function exportAndCopyTranslations(string $themeName, string $isoCode): array
    {
        $language = $this->languageRepository->getOneByIsoCode($isoCode);

        if (!$language) {
            throw new Exception(sprintf('Language with ISO code "%s" not found', $isoCode));
        }

        $locale = $language->getLocale();

        $selections = [
            [
                'type' => ProviderDefinitionInterface::TYPE_THEMES,
                'selected' => $themeName,
            ],
        ];

        $zipPath = $this->catalogueExporter->export($selections, $locale);

        if (!$this->filesystem->exists($zipPath)) {
            throw new Exception(sprintf('Failed to create translation archive at "%s"', $zipPath));
        }

        $extractedFiles = $this->extractArchive($zipPath);

        $copiedFiles = $this->copyTranslationsToTheme($themeName, $extractedFiles, $locale);

        $this->filesystem->remove($zipPath);

        return [
            'zip_path' => $zipPath,
            'extracted_files' => $extractedFiles,
            'copied_files' => $copiedFiles,
            'locale' => $locale,
        ];
    }

    protected function extractArchive(string $zipPath): array
    {
        $zip = new ZipArchive();
        $extractedFiles = [];

        if ($zip->open($zipPath) !== true) {
            throw new Exception(sprintf('Failed to open archive "%s"', $zipPath));
        }

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ps_theme_translations_' . uniqid();
        $this->filesystem->mkdir($tempDir);

        if (!$zip->extractTo($tempDir)) {
            $zip->close();

            throw new Exception(sprintf('Failed to extract archive to "%s"', $tempDir));
        }

        $zip->close();

        $finder = new Finder();
        $finder->files()->in($tempDir)->name('*.xlf');

        foreach ($finder as $file) {
            $extractedFiles[] = [
                'path' => $file->getRealPath(),
                'filename' => $file->getFilename(),
                'temp_dir' => $tempDir,
            ];
        }

        return $extractedFiles;
    }

    protected function copyTranslationsToTheme(string $themeName, array $extractedFiles, string $locale): array
    {
        $themeTranslationsDir = $this->themesDir . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR . $locale;

        if (!$this->filesystem->exists($themeTranslationsDir)) {
            $this->filesystem->mkdir($themeTranslationsDir, 0755);
        }

        $copiedFiles = [];

        foreach ($extractedFiles as $fileInfo) {
            $sourcePath = $fileInfo['path'];
            $filename = $fileInfo['filename'];
            $destinationPath = $themeTranslationsDir . DIRECTORY_SEPARATOR . $filename;

            $this->filesystem->copy($sourcePath, $destinationPath, true);

            $copiedFiles[] = [
                'source' => $sourcePath,
                'destination' => $destinationPath,
                'filename' => $filename,
            ];
        }

        if (!empty($extractedFiles) && isset($extractedFiles[0]['temp_dir'])) {
            $tempDir = $extractedFiles[0]['temp_dir'];

            if ($this->filesystem->exists($tempDir)) {
                $this->filesystem->remove($tempDir);
            }
        }

        return $copiedFiles;
    }
}
