<?php

namespace App\Pipelines;

use App\Models\RadioChannel;
use RoachPHP\Support\Configurable;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\ItemPipeline\ItemInterface;

class SaveAudioAndSubtitleRadioStationPipeline implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        $attributes = [
            RadioChannel::ALT => $item->get(RadioChannel::ALT),
        ];

        $radioStation = RadioChannel::firstOrNew($attributes);

        $radioStation->fill([
            RadioChannel::AUDIO_URL => $item->get(RadioChannel::AUDIO_URL),
            RadioChannel::SUBTITLE => $item->get(RadioChannel::SUBTITLE),
        ]);

        $radioStation->save();

        return $item;
    }
}