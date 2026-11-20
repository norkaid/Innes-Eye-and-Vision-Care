<?php

namespace App\Console\Commands;

use App\Services\ContentTransfer;
use Illuminate\Console\Command;
use RuntimeException;

class ImportContent extends Command
{
    protected $signature = 'innes:import-content {file : The export file (.json.gz) to import} {--force : Do not ask for confirmation}';

    protected $description = 'Import website content from an export file. Adds new items and overwrites items with the same web address; deletes nothing';

    public function handle(ContentTransfer $transfer): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm('Items with the same web address on this site will be overwritten. Continue?', true)) {
            return self::FAILURE;
        }

        try {
            $result = $transfer->import($transfer->readFile((string) file_get_contents($path)));
        } catch (RuntimeException $e) {
            $this->error('Nothing was imported. '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Content', 'Added', 'Updated'], collect($result)->map(fn ($c, $label) => [$label, $c['created'], $c['updated']])->values()->all());
        $this->info('Content imported.');

        return self::SUCCESS;
    }
}
