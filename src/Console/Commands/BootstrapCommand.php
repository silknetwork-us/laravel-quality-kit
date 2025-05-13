<?php

declare(strict_types=1);

namespace SilkNetwork\Console\Commands;

use Illuminate\Console\Command;

class BootstrapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'silknetwork:bootstrap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish configuration and generate IDE helper files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check for Aspell & English dictionary
        $this->info('Checking for Aspell & English dictionary... This is for Peck');
        exec('which aspell', $whichOutput, $whichCode);
        if ($whichCode !== 0) {
            $this->error('Aspell is not installed. Please install aspell and aspell-en.');

            return Command::FAILURE;
        }
        exec('aspell dump dicts', $dictsOutput, $dictsCode);
        if ($dictsCode !== 0 || ! in_array('en', $dictsOutput, true)) {
            $this->error('English dictionary not found for Aspell. Please install aspell-en.');

            return Command::FAILURE;
        }

        $this->info('Publishing configuration files...');
        $this->call('vendor:publish', [
            '--provider' => 'SilkNetwork\\LaravelQualityKitProvider',
            '--tag' => 'phpstan-config',
            '--force' => true,
        ]);

        $this->info('Publishing Rector configuration file...');
        $this->call('vendor:publish', [
            '--provider' => 'SilkNetwork\\LaravelQualityKitProvider',
            '--tag' => 'rector-config',
            '--force' => true,
        ]);

        $this->info('Publishing Pint configuration file...');
        $this->call('vendor:publish', [
            '--provider' => 'SilkNetwork\\LaravelQualityKitProvider',
            '--tag' => 'pint-config',
            '--force' => true,
        ]);

        $this->info('Generating IDE helper files...');
        $this->call('silknetwork:generate-ide-helpers');

        $this->info('Bootstrap complete.');

        return Command::SUCCESS;
    }
}
