<?php

namespace App\Services;

use App\Models\RadioChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RadioBrowserImporter
{
    private const DEFAULT_LIMIT = 500;

    private const MAX_LIMIT = 5000;

    private const BASE_URL_MARKER = 'https://www.radio-browser.info/';

    /**
     * @param  array<string, scalar|null>  $filters
     */
    public function import(array $filters): int
    {
        $base = rtrim((string) config('app.radio_browser_api_url'), '/');
        $query = $this->buildQuery($filters);

        $response = Http::timeout(20)
            ->retry(2, 250)
            ->acceptJson()
            ->withUserAgent('fm.nankov.mk/1.0')
            ->get("{$base}/json/stations/search", $query)
            ->throw();

        $imported = 0;

        foreach ($response->json() as $entry) {
            if (! is_array($entry) || ! $this->isUsable($entry)) {
                continue;
            }

            $this->upsert($entry);
            $imported++;
        }

        return $imported;
    }

    /**
     * @param  array<string, scalar|null>  $filters
     * @return array<string, scalar>
     */
    private function buildQuery(array $filters): array
    {
        $passthrough = ['name', 'country', 'countrycode', 'tag', 'order'];

        $query = [];
        foreach ($passthrough as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query[$key] = (string) $filters[$key];
            }
        }

        if ($query === []) {
            $query = [
                'hidebroken' => 'true',
                'order' => 'clickcount',
                'reverse' => 'true',
            ];
        }

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : self::DEFAULT_LIMIT;
        $query['limit'] = (string) max(1, min($limit, self::MAX_LIMIT));

        return $query;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function isUsable(array $entry): bool
    {
        if (empty($entry['stationuuid'])) {
            return false;
        }

        if (empty($entry['url_resolved']) && empty($entry['url'])) {
            return false;
        }

        if (array_key_exists('lastcheckok', $entry) && (int) $entry['lastcheckok'] !== 1) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function upsert(array $entry): void
    {
        $alt = 'rb:'.$entry['stationuuid'];

        $channel = RadioChannel::firstOrNew([RadioChannel::ALT => $alt]);

        $homepage = $entry['homepage'] ?? '';
        $favicon = $entry['favicon'] ?? '';
        $audioUrl = $entry['url_resolved'] ?? '';

        if (empty($audioUrl)) {
            $audioUrl = $entry['url'] ?? '';
        }

        $channel->fill([
            RadioChannel::LINK => $homepage !== '' ? $homepage : self::BASE_URL_MARKER,
            RadioChannel::SRC => $favicon !== '' ? $favicon : '',
            RadioChannel::BASE_URL => self::BASE_URL_MARKER,
            RadioChannel::AUDIO_URL => $audioUrl,
            RadioChannel::SUBTITLE => $this->buildSubtitle($entry),
            RadioChannel::TITLE => $channel->title ?: ($entry['name'] ?? $alt),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function buildSubtitle(array $entry): string
    {
        $parts = [];

        if (! empty($entry['country'])) {
            $parts[] = (string) $entry['country'];
        }

        $codec = trim((string) ($entry['codec'] ?? ''));
        $bitrate = (int) ($entry['bitrate'] ?? 0);
        if ($codec !== '' || $bitrate > 0) {
            $parts[] = trim($codec.' '.($bitrate > 0 ? $bitrate.'kbps' : ''));
        }

        $tags = trim((string) ($entry['tags'] ?? ''));
        if ($tags !== '') {
            $first = collect(explode(',', $tags))
                ->map(fn (string $t) => trim($t))
                ->filter()
                ->take(3)
                ->implode(', ');

            if ($first !== '') {
                $parts[] = $first;
            }
        }

        return Str::limit(implode(' · ', array_filter($parts)), 180, '');
    }
}
