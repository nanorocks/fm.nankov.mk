<?php

namespace App\Console\Commands;

use App\Spiders\FmRadioScraper;
use Illuminate\Console\Command;

class RadioWebScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:radio-web-scraper';

    /**
     * The console command description.
     *
     * @var string
     */
    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Scrapes radio station data from the web and stores it in the database';

    /**
     * Execute the console command.
     */
    public function handle(FmRadioScraper $scraper)
    {
        $scraper->scrape();
    }
}