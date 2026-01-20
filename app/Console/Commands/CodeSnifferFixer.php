<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CodeSnifferFixer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:fixer {--dry-run} {--config=.php-cs-fixer.php}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run PHP-CS-Fixer on app directories';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $paths = [
            './app', './bootstrap', './database',
            './public', './routes', './tests'
        ];

        $command = './vendor/bin/php-cs-fixer fix ' . implode(' ', $paths);
        if ($this->option('dry-run')) {
            $command .= ' --dry-run';
        }
        if ($this->option('config')) {
            $command .= ' --config=' . $this->option('config');
        }

        $output = shell_exec($command . ' 2>&1');
        
        $this->info($output ?: 'Files fixed successfully.');
        
        return 0;
    }
}
