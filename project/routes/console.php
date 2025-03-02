<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// NOTE: Add this on server: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('app:radio-web-scraper')->monthly();

Schedule::command('app:radio-channel-table-data-transformation')->monthly();