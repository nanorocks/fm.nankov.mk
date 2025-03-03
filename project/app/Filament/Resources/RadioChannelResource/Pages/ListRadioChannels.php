<?php

namespace App\Filament\Resources\RadioChannelResource\Pages;

use App\Filament\Resources\RadioChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRadioChannels extends ListRecords
{
    protected static string $resource = RadioChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
