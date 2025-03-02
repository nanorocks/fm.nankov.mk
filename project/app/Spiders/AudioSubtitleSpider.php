<?php

namespace App\Spiders;

use RoachPHP\Roach;
use RoachPHP\Http\Response;
use App\Models\RadioChannel;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\Configuration\Overrides;
use App\Pipelines\SaveAudioAndSubtitleRadioStationPipeline;

class AudioSubtitleSpider extends BasicSpider
{

    public array $itemProcessors = [
        SaveAudioAndSubtitleRadioStationPipeline::class,
    ];

    public array $startUrls = [];

    public function parse(Response $response): \Generator
    {
        $audioUrl = $response->filter('audio source')->attr('src');
        $alt = $this->context['alt'];
        $subtitle = $alt;

        yield $this->item([
            'audio_url' => $audioUrl,
            'subtitle' => $subtitle,
            'alt' => $alt,
        ]);
    }

    public function scrape(string $url, RadioChannel $radioChannel)
    {
        Roach::startSpider(
            self::class,
            new Overrides(startUrls: [$url]),
            context: ['alt' => $radioChannel->alt]
        );
    }
}