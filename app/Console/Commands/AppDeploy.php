<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppDeploy extends Command
{
    protected $signature = 'app:deploy
                            {--env-merge : Add missing keys from .env.example into .env first}
                            {--skip-migrate : Skip database migrations}
                            {--skip-seed : Skip RolePermissionSeeder}
                            {--skip-cache : Skip config/route/view cache}
                            {--seed-templates : Also run SmsTemplateSeeder}';

    protected $description = 'Run standard post-deploy tasks (migrate, permissions, cache)';

    public function handle(): int
    {
        $this->info('Digitex SMS — post-deploy');

        if ($this->option('env-merge')) {
            $this->runStep('env:merge', 'Merging new keys from .env.example into .env');
        }

        if (!$this->option('skip-migrate')) {
            $this->runStep('migrate --force', 'Running migrations');
        }

        if (!$this->option('skip-seed')) {
            $this->runStep('db:seed --class=RolePermissionSeeder --force', 'Syncing roles & permissions');
        }

        if ($this->option('seed-templates')) {
            $this->runStep('db:seed --class=SmsTemplateSeeder --force', 'Syncing SMS templates');
        }

        $this->runStep('permission:cache-reset', 'Resetting permission cache');

        if (!$this->option('skip-cache')) {
            $this->runStep('config:cache', 'Caching configuration');
            $this->runStep('route:cache', 'Caching routes');
            $this->runStep('view:cache', 'Caching views');
        }

        $this->newLine();
        $this->info('Deploy tasks completed.');
        $this->line('Reminder: ensure queue worker is running in production (`php artisan queue:work`).');

        return self::SUCCESS;
    }

    private function runStep(string $command, string $label): void
    {
        $this->newLine();
        $this->comment($label . '...');

        $exitCode = Artisan::call($command, [], $this->output);

        if ($exitCode !== self::SUCCESS) {
            $this->error("Failed: php artisan {$command}");
        }
    }
}
