<?php

declare(strict_types=1);

namespace SilkNetwork\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateIdeHelperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silknetwork:generate-ide-helpers {--no-models : Skip generating model helpers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IDE helper files for better static analysis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating IDE helper files for better static analysis...');
        $this->warn('Note: Errors about missing database files or encryption keys are expected and can be safely ignored.');
        $this->warn('These errors do not affect the generation of the helper files for PHPStan analysis.');

        $success = true;

        // Generate the main IDE helper file
        try {
            $this->call('ide-helper:generate');
        } catch (\Exception $e) {
            $this->warn('Could not generate main IDE helper file: '.$e->getMessage());
            $this->warn('This is not critical and can be ignored.');
            $success = false;
        }

        // Add Eloquent mixin
        try {
            $this->call('ide-helper:eloquent');
        } catch (\Exception $e) {
            $this->warn('Could not add Eloquent mixin: '.$e->getMessage());
            $this->warn('This is not critical and can be ignored.');
            $success = false;
        }

        // Generate PHPDocs for models
        if (! $this->option('no-models')) {
            try {
                $this->call('ide-helper:models', [
                    '--write-mixin' => true,
                    '--reset' => true,
                    '--no-interaction' => true,
                ]);
            } catch (\Exception $e) {
                $this->warn('Could not generate model helpers: '.$e->getMessage());
                $this->warn('This is not critical and can be ignored.');
                $this->info('If you have database connection issues, you can run with --no-models option.');
                $success = false;
            }
        } else {
            $this->info('Skipping model helper generation as requested.');
        }

        // Generate meta file for PhpStorm
        try {
            $this->call('ide-helper:meta');
        } catch (\Exception $e) {
            $this->warn('Could not generate meta file: '.$e->getMessage());
            $this->warn('This is not critical and can be ignored.');
            $success = false;
        }

        // Check if any helper files were generated
        $hasHelperFiles = File::exists(base_path('_ide_helper.php')) ||
                         File::exists(base_path('_ide_helper_models.php')) ||
                         File::exists(base_path('.phpstorm.meta.php'));

        if ($hasHelperFiles) {
            $this->info('IDE helper files generated successfully!');
            $this->info('These files will help PHPStan better understand your Laravel code.');

            // Add note about IDE support
            $this->info('');
            $this->info('These files work with both PhpStorm and VSCode:');
            $this->info('- For VSCode: Install the PHP Intelephense extension');
            $this->info('- For PhpStorm: No additional setup required');

            return Command::SUCCESS;
        } else {
            $this->error('No IDE helper files were generated. Please check the errors above.');

            return Command::FAILURE;
        }
    }
}
