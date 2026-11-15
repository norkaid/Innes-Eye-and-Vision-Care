<?php

namespace App\Console\Commands;

use App\Services\ContentTransfer;
use Illuminate\Console\Command;

class ExportContent extends Command
{
    protected $signature = 'innes:export-content {file? : Where to save the export (default: storage/app/innes-content-<date>.json.gz)} {--include-demo : Also export items that are still demo content}';

    protected $description = 'Export website content (settings, images, branches, services, products, articles) to one file';

    public function handle(ContentTransfer $transfer): int
    {
        $path = $this->argument('file') ?: storage_path('app/innes-content-'.now()->format('Y-m-d-His').'.json.gz');
        file_put_contents($path, $transfer->exportFile((bool) $this->option('include-demo')));
        $this->info('Content exported to '.$path.' ('.round(filesize($path) / 1024 / 1024, 1).' MB).');

        return self::SUCCESS;
    }
}
