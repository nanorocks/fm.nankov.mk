<?php

namespace App\Console\Commands;

use App\Models\RadioChannel;
use Illuminate\Console\Command;

class RadioChannelsStatus extends Command
{
    protected $signature = 'app:radio-channels-status {--limit=20 : Maximum number of problem rows to list}';

    protected $description = 'Report how many radio channels are visible on the public frontend and which ones are missing audio_url';

    public function handle(): int
    {
        $total = RadioChannel::count();

        $visible = RadioChannel::query()
            ->where(RadioChannel::PUBLISHED, true)
            ->whereNotNull(RadioChannel::AUDIO_URL)
            ->count();

        $publishedNoAudio = RadioChannel::query()
            ->where(RadioChannel::PUBLISHED, true)
            ->whereNull(RadioChannel::AUDIO_URL)
            ->count();

        $unpublishedWithAudio = RadioChannel::query()
            ->where(RadioChannel::PUBLISHED, false)
            ->whereNotNull(RadioChannel::AUDIO_URL)
            ->count();

        $unpublishedNoAudio = RadioChannel::query()
            ->where(RadioChannel::PUBLISHED, false)
            ->whereNull(RadioChannel::AUDIO_URL)
            ->count();

        $this->info("Total channels: {$total}");
        $this->line('');
        $this->table(
            ['State', 'Count'],
            [
                ['published + has audio (visible on /)', $visible],
                ['published + missing audio', $publishedNoAudio],
                ['unpublished + has audio', $unpublishedWithAudio],
                ['unpublished + missing audio', $unpublishedNoAudio],
            ],
        );

        $limit = (int) $this->option('limit');

        $missingAudio = RadioChannel::query()
            ->whereNull(RadioChannel::AUDIO_URL)
            ->orderBy(RadioChannel::ALT)
            ->limit($limit)
            ->get([RadioChannel::ALT, RadioChannel::LINK, RadioChannel::PUBLISHED]);

        if ($missingAudio->isEmpty()) {
            $this->info('No channels are missing audio_url.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->warn("Channels with NULL audio_url (showing up to {$limit}):");
        $this->table(
            ['alt', 'link', 'published'],
            $missingAudio->map(fn (RadioChannel $channel): array => [
                $channel->{RadioChannel::ALT},
                $channel->{RadioChannel::LINK},
                $channel->{RadioChannel::PUBLISHED} ? 'yes' : 'no',
            ])->all(),
        );

        return self::SUCCESS;
    }
}
