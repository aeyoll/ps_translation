# PS Translation Module

Translation module for PrestaShop.

## CLI Commands

### Export Theme Translations

This command allows you to export theme translations in XLIFF format and automatically copy them to the theme's `translations/` directory.

It performs the same operations as the administration interface:
1. Generates XLIFF files from templates, existing translations, and the database
2. Creates a ZIP archive
3. Extracts the archive
4. Copies XLIFF files to the theme's `translations/` directory
5. Cleans up temporary files

#### Usage

```bash
php bin/console prestashop:translations:export-theme <theme> <iso_code>
```

#### Arguments

- `theme`: Theme name (directory name in `themes/`)
- `iso_code`: Language ISO code (e.g., fr, en, es)

#### Examples

```bash
# Export French translations for the yourtheme theme
php bin/console prestashop:translations:export-theme yourtheme fr

# Export English translations
php bin/console prestashop:translations:export-theme yourtheme en
```

#### Result

XLIFF files will be copied to `themes/<theme>/translations/<locale>/`:
- `themes/yourtheme/translations/fr-FR/ShopThemeYourtheme.fr-FR.xlf`
- `themes/yourtheme/translations/fr-FR/ShopThemeActions.fr-FR.xlf`
- `themes/yourtheme/translations/fr-FR/ShopThemeCheckout.fr-FR.xlf`
- etc.

**Note:** This command uses exactly the same process as the administration interface (route `/prestashop/improve/international/translations/export`), ensuring full compatibility with manual exports.

These files can then be versioned with the theme to distribute translations.

### Import Theme Translations

This command allows you to import theme translations from XLIFF files to the database.

It performs the same operations as the administration interface:
1. Finds and parses XLIFF files in the theme's `translations/` directory
2. Parses each XLIFF file using Symfony's XliffFileLoader
3. Extracts the domain from the filename (e.g., ShopThemeGlobal.fr-FR.xlf → ShopThemeGlobal)
4. Saves each translation to the database with the theme name
5. Updates existing translations or creates new ones as needed

#### Usage

```bash
php bin/console prestashop:translations:import-theme <theme> <iso_code>
```

#### Arguments

- `theme`: Theme name (directory name in `themes/`)
- `iso_code`: Language ISO code (e.g., fr, en, es)

#### Examples

```bash
# Import French translations for the yourtheme theme
php bin/console prestashop:translations:import-theme yourtheme fr

# Import English translations
php bin/console prestashop:translations:import-theme yourtheme en
```

#### Result

Translations will be imported into the database with the theme name.
