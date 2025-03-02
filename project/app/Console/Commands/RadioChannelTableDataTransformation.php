<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RadioChannel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Spiders\AudioSubtitleSpider;

class RadioChannelTableDataTransformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:radio-channel-table-data-transformation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transforms the data in the radio channel table to the required format';

    public function handle(AudioSubtitleSpider $audioSubtitleSpider)
    {
        $radioChannels = RadioChannel::all();

        foreach ($radioChannels as $channel) {
            $photoUrl = $channel->base_url . $channel->src;
            $filename = 'photos/' . basename($channel->src);

            if (!$this->photoExists($filename)) {
                $photoContents = $this->fetchPhoto($photoUrl);

                if ($photoContents) {
                    $this->storePhoto($filename, $photoContents);
                } else {
                    $this->error("Failed to fetch photo from URL: $photoUrl");
                    continue;
                }
            }

            $audioSubtitleSpider->scrape($channel->base_url . $channel->link, $channel);
 
            $this->updateChannel($channel, $filename);
        }

        $this->info('Data transformation completed successfully.');
    }

    protected function photoExists(string $filename): bool
    {
        return Storage::disk('public')->exists($filename);
    }

    protected function fetchPhoto(string $photoUrl): ?string
    {
        $response = Http::get($photoUrl);

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    protected function storePhoto(string $filename, string $photoContents): void
    {
        Storage::disk('public')->put($filename, $photoContents);
    }

    protected function updateChannel(RadioChannel $channel, string $filename): void
    {
        $channel->photo = Storage::url($filename);
        $channel->title = $channel->alt;

        $channel->save();
    }
}