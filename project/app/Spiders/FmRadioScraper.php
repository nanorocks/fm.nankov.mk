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
        $stations = $response->filter('.col-md-4 .col-img')->each(function ($node) {
            $link = $node->filter('a')->attr('href');
            $img = $node->filter('img');
            $src = $img->attr('src');
            $alt = $img->attr('alt');

            return [
                'link' => $link,
                'src' => $src,
                'alt' => $alt,
                'base_url' => config('app.radio_station_url'),
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