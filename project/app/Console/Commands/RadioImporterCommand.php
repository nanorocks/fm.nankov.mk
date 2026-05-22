<?php

namespace App\Console\Commands;

use App\Services\RadioBrowserImporter;
use Illuminate\Console\Command;

class RadioImporterCommand extends Command
{
    protected $signature = 'app:radio-importer
        {--name= : Filter by station name (substring match)}
        {--country= : Country name, e.g. Serbia}
        {--countrycode= : ISO country code, e.g. RS}
        {--tag= : Tag filter, e.g. jazz}
        {--limit= : Override the default per-call limit (max 5000)}
        {--order= : radio-browser order (clickcount, votes, name, ...)}';

    protected $description = 'Imports radio stations from radio-browser.info into the local catalog';

    public function handle(RadioBrowserImporter $importer): int
    {
        $filters = array_filter([
            'name' => $this->option('name'),
            'country' => $this->option('country'),
            'countrycode' => $this->option('countrycode'),
            'tag' => $this->option('tag'),
            'limit' => $this->option('limit'),
            'order' => $this->option('order'),
        ], fn ($value) => $value !== null && $value !== '');

        $count = $importer->import($filters);

        $this->info("Imported / upserted {$count} stations.");

        return self::SUCCESS;
    }
}
