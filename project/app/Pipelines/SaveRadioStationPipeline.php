<?php

namespace App\Pipelines;

use App\Models\RadioChannel;
use RoachPHP\Support\Configurable;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\ItemPipeline\ItemInterface;

class SaveRadioStationPipeline implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        $attributes = [
            RadioChannel::ALT => $item->get(RadioChannel::ALT),
        ];

        $radioStation = RadioChannel::firstOrNew($attributes);

        $radioStation->fill([
            RadioChannel::LINK => $item->get(RadioChannel::LINK),
            RadioChannel::SRC => $item->get(RadioChannel::SRC),
            RadioChannel::ALT => $item->get(RadioChannel::ALT),
            RadioChannel::BASE_URL => $item->get(RadioChannel::BASE_URL),
        ]);

        $radioStation->save();

        return $item;
    }
}