# Installation & Usage Guide

This guide explains how to integrate the Laravel Quality Kit into your Laravel projects. This kit bundles PHPStan, Larastan, Rector, Laravel Pint, Peck, and Laravel IDE Helper to enhance your code quality.

## Installation

### Option 1: Install via Composer (Recommended)

#### 1. Add the Repository to Your Project's `composer.json` (If using a private repository or specific fork)

Since we're not published, you might need to add it to your `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/silknetwork-us/laravel-quality-kit.git"
    }
]
```

#### 2. Install the Package

Require the package using Composer:

```bash
composer require silknetwork-us/laravel-quality-kit:dev-main --dev
```

#### 3. Bootstrap the Kit

Run the `silknetwork:bootstrap` command. This is the primary setup command and will:
*   Publish configuration files:
    *   `phpstan.neon` (for PHPStan) to your project root.
    *   `rector.php` (for Rector) to your project root.
    *   `pint.json` (for Laravel Pint) to your project root.
*   Generate IDE helper files using `silknetwork:generate-ide-helpers`.
*   Verify that Aspell and the English dictionary (`aspell-en`) are installed, which Peck uses for code spelling checks. If not found, it will show an error.

```bash
php artisan silknetwork:bootstrap
```

**Note on `peck.json`**: The `silknetwork:bootstrap` command currently publishes configurations for PHPStan, Rector, and Pint. If you need to customize Peck, you may need to publish its configuration separately:
```bash
php artisan vendor:publish --provider="SilkNetwork\LaravelQualityKitProvider" --tag="peck-config"
```
This will publish `peck.json` to your project root.

#### 4. (Optional) Manually Generate IDE Helper Files

If you only need to regenerate IDE helper files at any time, without re-publishing configurations, you can run:

```bash
php artisan silknetwork:generate-ide-helpers
```

If you encounter database connection issues during model helper generation, you can skip that part:

```bash
php artisan silknetwork:generate-ide-helpers --no-models
```

This command, part of the bootstrap process, performs the following:
1.  Generates the main IDE helper file (`_ide_helper.php`).
2.  Adds Eloquent mixin to the Model class.
3.  Generates PHPDocs for models (`_ide_helper_models.php`) - unless `--no-models` is specified.
4.  Generates a meta file for IDE support (`.phpstorm.meta.php`).


### Customizing Configurations

After running `php artisan silknetwork:bootstrap` (or manual copying), the configuration files (`phpstan.neon`, `rector.php`, `pint.json`, `peck.json`) will be in your project root. You can edit these files to tailor the tools to your specific needs.

For example, to change PHPStan's strictness level:
Edit `phpstan.neon`:
```yaml
parameters:
    level: 7 # Default is often 5 or 6, 7+ is stricter
    paths:
        - app
        - tests
    # ... other configurations
```

## IDE Integration

### VSCode

Recommended extensions:
1.  **PHP Intelephense**: [Marketplace](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client)
2.  **PHPStan**: [Marketplace](https://marketplace.visualstudio.com/items?itemName=sanderronde.phpstan-vscode)
3.  **Error Lens**: [Marketplace](https://marketplace.visualstudio.com/items?itemName=usernamehw.errorlens)
4.  **PHP CS Fixer** (for Pint, if you want IDE integration for it): [Marketplace](https://marketplace.visualstudio.com/items?itemName=junstyle.php-cs-fixer) - Configure it to use `pint.json`.

Add/update your VSCode `settings.json`:
```json
{
    "intelephense.environment.includePaths": [
        "_ide_helper.php",
        "_ide_helper_models.php",
        ".phpstorm.meta.php"
    ],
    "phpstan.configFile": "./phpstan.neon",
    // For PHP CS Fixer (Pint)
    "php-cs-fixer.config": ".pint.json", // or pint.json if you rename it
    "php-cs-fixer.executablePath": "./vendor/bin/pint",
    "php-cs-fixer.onsave": true, // Optional: format on save
    "[php]": {
        "editor.defaultFormatter": "junstyle.php-cs-fixer"
    }
}
```

### PhpStorm

PhpStorm has excellent built-in support for PHPStan, PHP CS Fixer (Pint), and the IDE helper files.
1.  **IDE Helper Files**: Automatically recognized.
2.  **PHPStan**: Enable PHPStan integration in `Settings/Preferences > PHP > Quality Tools > PHPStan`. Point it to your `vendor/bin/phpstan` and `phpstan.neon`.
3.  **Pint (PHP CS Fixer)**: Enable in `Settings/Preferences > Editor > Code Style > PHP`. Add a new configuration, choose "PHP CS Fixer", and point it to `vendor/bin/pint` and your `pint.json`. You can set it to format on save.

## Troubleshooting

### Database Connection Issues (IDE Helper)
If `php artisan silknetwork:generate-ide-helpers` (or `ide-helper:models`) fails due to database issues:
1.  Use the `--no-models` flag: `php artisan silknetwork:generate-ide-helpers --no-models`
2.  Ensure your `.env` database configuration is correct.
3.  For SQLite, ensure the database file exists (e.g., `touch database/database.sqlite`).

### Missing Encryption Key
If you see "No application encryption key has been specified":
1.  Generate a key: `php artisan key:generate`
2.  This warning typically doesn't affect IDE helper generation.

## Recommended Workflow

1.  Install the kit and run `php artisan silknetwork:bootstrap`.
2.  Review and customize the published configuration files (`phpstan.neon`, `rector.php`, `pint.json`, `peck.json`).
3.  For PHPStan, start with the default level and gradually increase as you fix issues. Refer to the PHPStan guide in [README.md](README.md:1).
4.  Regularly run `composer pint-format` to maintain code style.
5.  Use `composer peck` to catch typos.
6.  Run `composer rector -- --dry-run` periodically to identify potential refactorings.
7.  Regenerate IDE helpers (`php artisan silknetwork:generate-ide-helpers`) when you make significant changes to models or service container bindings.

## Continuous Integration (CI)

Example steps for a GitHub Actions workflow:

```yaml
name: Laravel Quality Checks

on: [push, pull_request]

jobs:
  quality_checks:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2' # Match your project's PHP version
          extensions: mbstring, xml, ctype, iconv, intl, pdo_sqlite, aspell # Add aspell for Peck
          coverage: none

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Generate Application Key (if necessary for tests/helpers)
        run: php artisan key:generate

      - name: Generate IDE Helper Files (skip models if DB not available in CI)
        run: php artisan silknetwork:generate-ide-helpers --no-models

      - name: Check code style with Pint
        run: ./vendor/bin/pint # or vendor/bin/pint --test

      - name: Spellcheck with Peck
        run: ./vendor/bin/peck # or vendor/bin/peck

      - name: PHPStan Static Analysis
        run: ./vendor/bin/phpstan # or vendor/bin/phpstan analyse

      # Optionally, run Rector in dry-run mode
      # - name: Rector Dry Run
      #   run: composer rector -- --dry-run
```

Adjust for GitLab CI or other platforms accordingly.