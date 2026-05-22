<?php

namespace App\Console\Commands;

use App\Models\RadioChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RadioChannelTableDataTransformation extends Command
{
    protected $signature = 'app:radio-channel-table-data-transformation';

    protected $description = 'Downloads station logos to local storage and marks channels ready';

    public function handle(): void
    {
        $channels = RadioChannel::all();
        $bar = $this->output->createProgressBar($channels->count());
        $bar->start();

        foreach ($channels as $channel) {
            if (empty($channel->src)) {
                $bar->advance();

                continue;
            }

            // src is already an absolute URL since the new scraper
            $photoUrl = str_starts_with($channel->src, 'http')
                ? $channel->src
                : $channel->base_url.ltrim($channel->src, '/');

            $filename = 'photos/'.basename($channel->src);

            if (! Storage::disk('public')->exists($filename)) {
                $contents = $this->fetchPhoto($photoUrl);

                if ($contents) {
                    Storage::disk('public')->put($filename, $contents);
                } else {
                    $this->newLine();
                    $this->warn("Could not fetch photo: {$photoUrl}");
                    $bar->advance();

                    continue;
                }
            }

            $channel->photo = $filename; // mutator adds /storage/ prefix
            $channel->title = $channel->title ?: $channel->alt;
            $channel->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Photo download complete.');
    }

    private function fetchPhoto(string $url): ?string
    {
        $response = Http::timeout(10)->withHeaders([
            'User-Agent' => 'Mozilla/5.0',
        ])->get($url);

        return $response->successful() ? $response->body() : null;
    }
}
