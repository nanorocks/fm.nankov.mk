<?php

namespace App\Filament\Resources\RadioChannelResource\Pages;

use App\Filament\Resources\RadioChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRadioChannel extends EditRecord
{
    protected static string $resource = RadioChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
