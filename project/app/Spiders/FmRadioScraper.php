<?php

namespace App\Spiders;

use RoachPHP\Roach;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use App\Pipelines\SaveRadioStationPipeline;

class FmRadioScraper extends BasicSpider
{
    public array $itemProcessors = [
        SaveRadioStationPipeline::class,
    ];
    
    protected function initialRequests(): array
    {
        return [
            new Request(
                'GET',
                config('app.radio_station_url'),
                [$this, 'parse']
            ),
        ];
    }

    public function parse(Response $response): \Generator
    {
        $stations = $response->filter('a.radio-card')->each(function ($node) {
            return [
                'link'      => $node->attr('href'),
                'alt'       => $node->attr('data-name'),
                'src'       => $node->attr('data-logo'),
                'audio_url' => $node->attr('data-stream'),
                'subtitle'  => $node->attr('data-categories'),
                'base_url'  => config('app.radio_station_url'),
            ];
        });

        foreach ($stations as $station) {
            yield $this->item($station);
        }
    }

    public function scrape()
    {
        Roach::startSpider(self::class);
    }
}