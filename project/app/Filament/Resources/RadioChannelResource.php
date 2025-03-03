<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RadioChannel;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use App\Tables\Columns\CustomImageColumn;
use App\Filament\Resources\RadioChannelResource\Pages;

class RadioChannelResource extends Resource
{
    protected static ?string $model = RadioChannel::class;

    protected static ?string $navigationIcon = 'gmdi-folder-tt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make(RadioChannel::PHOTO)
                    ->label('Photo URL'),
                Forms\Components\TextInput::make(RadioChannel::TITLE)
                    ->label('Title'),
                Forms\Components\TextInput::make(RadioChannel::AUDIO_URL)
                    ->label('Audio URL'),
                Forms\Components\TextInput::make(RadioChannel::SUBTITLE)
                    ->label('Subtitle'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(RadioChannel::TITLE)
                    ->label('Title')
                    ->sortable()
                    ->searchable(),
                CustomImageColumn::make(RadioChannel::PHOTO)
                    ->label('Photo'),
                TextColumn::make(RadioChannel::AUDIO_URL)
                    ->label('Audio URL')
                    ->sortable()
                    ->searchable(),
                TextColumn::make(RadioChannel::SUBTITLE)
                    ->label('Subtitle')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRadioChannels::route('/'),
            'create' => Pages\CreateRadioChannel::route('/create'),
            'edit' => Pages\EditRadioChannel::route('/{record}/edit'),
        ];
    }
}