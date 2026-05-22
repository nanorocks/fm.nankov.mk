<?php

namespace App\Pipelines;

use App\Models\RadioChannel;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use RoachPHP\Support\Configurable;

class SaveAudioAndSubtitleRadioStationPipeline implements ItemProcessorInterface
{
    use Configurable;

    public function processItem(ItemInterface $item): ItemInterface
    {
        $radioStation = RadioChannel::firstOrNew([
            RadioChannel::ALT => $item->get(RadioChannel::ALT),
        ]);

        $updates = array_filter([
            RadioChannel::AUDIO_URL => $item->get(RadioChannel::AUDIO_URL),
            RadioChannel::SUBTITLE => $item->get(RadioChannel::SUBTITLE),
        ], fn ($value): bool => $value !== null);

        if ($updates === [] && $radioStation->exists) {
            return $item;
        }

        $radioStation->fill($updates);
        $radioStation->save();

        return $item;
    }
}
