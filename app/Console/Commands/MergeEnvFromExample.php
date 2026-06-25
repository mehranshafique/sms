<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MergeEnvFromExample extends Command
{
    protected $signature = 'env:merge
                            {--example= : Path to the example env file (default: .env.example)}
                            {--env= : Path to the target env file (default: .env)}
                            {--dry-run : Show what would be added without writing}
                            {--force : Create .env from .env.example when .env is missing}';

    protected $description = 'Add missing keys from .env.example into .env without overwriting existing values';

    public function handle(): int
    {
        $examplePath = $this->option('example') ?: base_path('.env.example');
        $envPath = $this->option('env') ?: base_path('.env');

        if (!File::exists($examplePath)) {
            $this->error("Example file not found: {$examplePath}");

            return self::FAILURE;
        }

        if (!File::exists($envPath)) {
            if (!$this->option('force')) {
                $this->error('.env does not exist. Run with --force to create it from .env.example.');

                return self::FAILURE;
            }

            File::copy($examplePath, $envPath);
            $this->info("Created {$envPath} from {$examplePath}.");

            return self::SUCCESS;
        }

        $envContent = File::get($envPath);
        $exampleContent = File::get($examplePath);
        $existingKeys = $this->parseKeys($envContent);

        [$appendBlock, $addedKeys] = $this->collectMissingLines($exampleContent, $existingKeys);

        if ($addedKeys === []) {
            $this->info('.env is already up to date — no new keys from .env.example.');

            return self::SUCCESS;
        }

        $this->line('Keys to add:');
        foreach ($addedKeys as $key) {
            $this->line("  - {$key}");
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->comment('Dry run — nothing was written.');
            $this->line($appendBlock);

            return self::SUCCESS;
        }

        $backupPath = $envPath . '.backup.' . now()->format('Ymd_His');
        File::copy($envPath, $backupPath);
        $this->info("Backup created: {$backupPath}");

        $separator = rtrim($envContent) . PHP_EOL . PHP_EOL;
        $header = '# --- Added from ' . basename($examplePath) . ' on ' . now()->toDateTimeString() . ' ---' . PHP_EOL;
        File::put($envPath, $separator . $header . $appendBlock);

        $this->info('Updated .env with ' . count($addedKeys) . ' new key(s). Existing values were not changed.');

        return self::SUCCESS;
    }

    /** @return array<string, true> */
    private function parseKeys(string $content): array
    {
        $keys = [];

        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key] = explode('=', $line, 2);
            $keys[trim($key)] = true;
        }

        return $keys;
    }

    /**
     * @param  array<string, true>  $existingKeys
     * @return array{0: string, 1: list<string>}
     */
    private function collectMissingLines(string $exampleContent, array $existingKeys): array
    {
        $pendingComments = [];
        $appendLines = [];
        $addedKeys = [];

        foreach (preg_split('/\R/', $exampleContent) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($appendLines !== [] || $pendingComments !== []) {
                    $appendLines[] = '';
                }
                continue;
            }

            if (str_starts_with($trimmed, '#')) {
                $pendingComments[] = $line;
                continue;
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key] = explode('=', $trimmed, 2);
            $key = trim($key);

            if (isset($existingKeys[$key])) {
                $pendingComments = [];
                continue;
            }

            foreach ($pendingComments as $commentLine) {
                $appendLines[] = $commentLine;
            }
            $pendingComments = [];

            $appendLines[] = $line;
            $addedKeys[] = $key;
            $existingKeys[$key] = true;
        }

        return [rtrim(implode(PHP_EOL, $appendLines)) . PHP_EOL, $addedKeys];
    }
}
