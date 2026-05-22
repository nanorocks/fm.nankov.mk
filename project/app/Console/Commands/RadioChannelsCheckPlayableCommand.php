<?php

namespace App\Console\Commands;

use App\Models\RadioChannel;
use Illuminate\Console\Command;

class RadioChannelsCheckPlayableCommand extends Command
{
    protected $signature = 'app:radio-channels-check-playable
        {--dry-run : Report results without updating the database}
        {--only-published : Restrict check to currently-published channels}
        {--only-unpublished : Restrict check to currently-unpublished channels (re-test dead streams)}
        {--timeout=8 : Per-stream timeout in seconds}';

    protected $description = 'Probes each channel audio_url and toggles published based on whether the stream responds';

    public function handle(): int
    {
        $timeout = max(2, (int) $this->option('timeout'));
        $connectTimeout = min($timeout, 5);
        $dryRun = (bool) $this->option('dry-run');

        $query = RadioChannel::query()
            ->whereNotNull(RadioChannel::AUDIO_URL)
            ->where(RadioChannel::AUDIO_URL, '!=', '');

        if ($this->option('only-published')) {
            $query->where(RadioChannel::PUBLISHED, true);
        } elseif ($this->option('only-unpublished')) {
            $query->where(RadioChannel::PUBLISHED, false);
        }

        $channels = $query->get();

        if ($channels->isEmpty()) {
            $this->info('No channels to check.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($channels->count());
        $bar->start();

        $playable = 0;
        $broken = 0;
        $changed = 0;
        $brokenSamples = [];

        foreach ($channels as $channel) {
            $isPlayable = $this->probe($channel->audio_url, $timeout, $connectTimeout);

            if ($isPlayable) {
                $playable++;
            } else {
                $broken++;
                if (count($brokenSamples) < 8) {
                    $brokenSamples[] = "  - {$channel->title} ({$channel->audio_url})";
                }
            }

            if ((bool) $channel->published !== $isPlayable) {
                $changed++;
                if (! $dryRun) {
                    $channel->published = $isPlayable;
                    $channel->save();
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Playable: {$playable}");
        $this->info("Broken:   {$broken}");

        if ($dryRun) {
            $this->comment("Dry run — no rows updated. Would flip published on {$changed} rows.");
        } else {
            $this->info("Updated published flag on {$changed} rows.");
        }

        if ($brokenSamples !== []) {
            $this->newLine();
            $this->line('Sample broken streams:');
            foreach ($brokenSamples as $line) {
                $this->line($line);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Probes a stream URL with curl directly. Returns true if the server responds
     * with a successful HTTP/ICY status (Icecast/Shoutcast use the non-standard
     * "ICY 200 OK" status line, which Guzzle does not parse cleanly).
     */
    private function probe(string $url, int $timeout, int $connectTimeout): bool
    {
        $ch = curl_init();
        $bodySample = '';

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_NOBODY => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP09_ALLOWED => true,
            CURLOPT_USERAGENT => 'fm.nankov.mk/1.0 (playability-check)',
            CURLOPT_HTTPHEADER => ['Icy-MetaData: 0'],
            CURLOPT_WRITEFUNCTION => function ($_, string $data) use (&$bodySample) {
                $bodySample .= $data;

                return strlen($bodySample) >= 1024 ? -1 : strlen($data);
            },
        ]);

        curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        curl_close($ch);

        $aborted = $errno === CURLE_WRITE_ERROR || $errno === CURLE_ABORTED_BY_CALLBACK;

        if ($errno !== 0 && ! $aborted) {
            return false;
        }

        if ($httpCode !== 0 && ! in_array($httpCode, [200, 206], true)) {
            return false;
        }

        if (str_starts_with($contentType, 'text/html')) {
            return false;
        }

        return $bodySample !== '';
    }
}
